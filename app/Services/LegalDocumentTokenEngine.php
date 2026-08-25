<?php

namespace App\Services;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LegalDocumentTokenEngine
{
    private const TOKEN_PATTERN = '/\{\{\s*([^{}]+?)\s*\}\}/u';

    private const TOKENS = [
        'client.name' => 'Cliente: nome ou razão social',
        'client.trade_name' => 'Cliente: nome fantasia',
        'client.document_number' => 'Cliente: CPF ou CNPJ',
        'client.email' => 'Cliente: e-mail',
        'client.phone' => 'Cliente: telefone',
        'client.whatsapp' => 'Cliente: WhatsApp',
        'client.birth_date' => 'Cliente: data de nascimento',
        'client.profession' => 'Cliente: profissão ou segmento',
        'client.address_zip' => 'Cliente: CEP',
        'client.address_line' => 'Cliente: endereço completo',
        'client.address_city' => 'Cliente: cidade',
        'client.address_state' => 'Cliente: UF',
        'case.title' => 'Processo: título',
        'case.process_number' => 'Processo: número CNJ',
        'case.internal_code' => 'Processo: código interno',
        'case.practice_area' => 'Processo: área de atuação',
        'case.counterparty' => 'Processo: parte contrária',
        'case.court_name' => 'Processo: tribunal',
        'case.court_division' => 'Processo: vara ou unidade',
        'case.court_city' => 'Processo: cidade do tribunal',
        'case.court_state' => 'Processo: UF do tribunal',
        'case.status' => 'Processo: status',
        'case.phase' => 'Processo: fase',
        'case.filing_date' => 'Processo: data de distribuição',
        'case.claim_amount' => 'Processo: valor da causa',
        'case.contract_value' => 'Processo: valor contratado',
        'case.success_fee_percent' => 'Processo: honorário de êxito',
        'generator.name' => 'Geração: usuário responsável',
        'generator.email' => 'Geração: e-mail do responsável',
        'system.app_name' => 'Sistema: nome da aplicação',
        'system.current_date' => 'Sistema: data da geração',
        'system.current_datetime' => 'Sistema: data e hora da geração',
        'system.current_year' => 'Sistema: ano da geração',
    ];

    public function allowedTokens(): array
    {
        return self::TOKENS;
    }

    public function extractFromVersion(string $titleTemplate, array $definition): array
    {
        $values = [$titleTemplate];
        foreach ($definition['blocks'] ?? [] as $block) {
            if (isset($block['text'])) {
                $values[] = (string) $block['text'];
            }
            foreach ($block['items'] ?? [] as $item) {
                $values[] = (string) $item;
            }
        }

        foreach ($values as $value) {
            $withoutTokens = preg_replace(self::TOKEN_PATTERN, '', $value) ?? $value;
            if (str_contains($withoutTokens, '{{') || str_contains($withoutTokens, '}}')) {
                throw ValidationException::withMessages([
                    'definition_json' => 'Existe um token com delimitadores incompletos no template.',
                ]);
            }
        }

        $tokens = collect($values)
            ->flatMap(fn (string $value): array => $this->extract($value))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $unknown = array_values(array_diff($tokens, array_keys(self::TOKENS)));
        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'definition_json' => 'Token(s) não permitido(s): '.implode(', ', $unknown).'.',
            ]);
        }

        return $tokens;
    }

    public function assertAvailableForScope(array $tokens, string $scope): void
    {
        if (! array_key_exists($scope, \App\Models\LegalDocumentTemplate::contextScopes())) {
            throw ValidationException::withMessages(['context_scope' => 'O contexto informado não é suportado.']);
        }

        if ($scope !== \App\Models\LegalDocumentTemplate::CONTEXT_CLIENT) {
            return;
        }

        $unavailable = array_values(array_filter(
            $tokens,
            fn (string $token): bool => str_starts_with($token, 'case.')
        ));

        if ($unavailable !== []) {
            throw ValidationException::withMessages([
                'definition_json' => 'Token(s) de processo incompatível(is) com o contexto somente cliente: '.implode(', ', $unavailable).'.',
            ]);
        }
    }

    public function context(?Client $client, ?LegalCase $legalCase, User $generator, CarbonInterface $generatedAt): array
    {
        $context = [
            'generator.name' => (string) $generator->name,
            'generator.email' => (string) $generator->email,
            'system.app_name' => (string) config('app.name'),
            'system.current_date' => $generatedAt->format('d/m/Y'),
            'system.current_datetime' => $generatedAt->format('d/m/Y H:i'),
            'system.current_year' => $generatedAt->format('Y'),
        ];

        if ($client) {
            $context += [
                'client.name' => (string) $client->name,
                'client.trade_name' => (string) $client->trade_name,
                'client.document_number' => (string) $client->document_number,
                'client.email' => (string) $client->email,
                'client.phone' => $this->formatPhone($client->phone),
                'client.whatsapp' => $this->formatPhone($client->whatsapp),
                'client.birth_date' => $client->birth_date?->format('d/m/Y') ?? '',
                'client.profession' => (string) $client->profession,
                'client.address_zip' => $this->formatZip($client->address_zip),
                'client.address_line' => $this->addressLine($client),
                'client.address_city' => (string) $client->address_city,
                'client.address_state' => (string) $client->address_state,
            ];
        }

        if ($legalCase) {
            $context += [
                'case.title' => (string) $legalCase->title,
                'case.process_number' => (string) $legalCase->process_number,
                'case.internal_code' => (string) $legalCase->internal_code,
                'case.practice_area' => (string) $legalCase->practice_area,
                'case.counterparty' => (string) $legalCase->counterparty,
                'case.court_name' => (string) $legalCase->court_name,
                'case.court_division' => (string) $legalCase->court_division,
                'case.court_city' => (string) $legalCase->court_city,
                'case.court_state' => (string) $legalCase->court_state,
                'case.status' => Str::of((string) $legalCase->status)->replace('_', ' ')->headline()->toString(),
                'case.phase' => Str::of((string) $legalCase->phase)->replace('_', ' ')->headline()->toString(),
                'case.filing_date' => $legalCase->filing_date?->format('d/m/Y') ?? '',
                'case.claim_amount' => $this->formatMoney($legalCase->claim_amount),
                'case.contract_value' => $this->formatMoney($legalCase->contract_value),
                'case.success_fee_percent' => filled($legalCase->success_fee_percent)
                    ? number_format((float) $legalCase->success_fee_percent, 2, ',', '.').' %'
                    : '',
            ];
        }

        return $context;
    }

    public function render(string $template, array $context): string
    {
        return preg_replace_callback(self::TOKEN_PATTERN, function (array $matches) use ($context): string {
            $token = strtolower(trim($matches[1]));
            if (! array_key_exists($token, self::TOKENS)) {
                throw ValidationException::withMessages(['template' => "Token não permitido: {$token}."]);
            }
            if (! array_key_exists($token, $context)) {
                throw ValidationException::withMessages(['context' => "O token {$token} não está disponível no contexto selecionado."]);
            }

            return (string) $context[$token];
        }, $template) ?? $template;
    }

    public function renderDefinition(array $definition, array $context): array
    {
        return [
            'blocks' => collect($definition['blocks'] ?? [])->map(function (array $block) use ($context): array {
                if (isset($block['text'])) {
                    $block['text'] = $this->render((string) $block['text'], $context);
                }
                if (isset($block['items'])) {
                    $block['items'] = array_map(
                        fn (string $item): string => $this->render($item, $context),
                        $block['items']
                    );
                }

                return $block;
            })->all(),
        ];
    }

    private function extract(string $value): array
    {
        preg_match_all(self::TOKEN_PATTERN, $value, $matches);

        return array_map(
            fn (string $token): string => strtolower(trim($token)),
            $matches[1] ?? []
        );
    }

    private function addressLine(Client $client): string
    {
        $street = trim(implode(', ', array_filter([
            $client->address_street,
            $client->address_number,
        ], fn (mixed $value): bool => filled($value))));
        $details = trim(implode(' - ', array_filter([
            $client->address_complement,
            $client->address_district,
            trim(implode('/', array_filter([$client->address_city, $client->address_state]))),
            $this->formatZip($client->address_zip),
        ], fn (mixed $value): bool => filled($value))));

        return trim(implode(' - ', array_filter([$street, $details])));
    }

    private function formatPhone(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        return match (strlen($digits)) {
            10 => preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $digits) ?? $digits,
            11 => preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $digits) ?? $digits,
            default => (string) $value,
        };
    }

    private function formatZip(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        return strlen($digits) === 8
            ? substr($digits, 0, 5).'-'.substr($digits, 5)
            : (string) $value;
    }

    private function formatMoney(mixed $value): string
    {
        return filled($value) ? 'R$ '.number_format((float) $value, 2, ',', '.') : '';
    }
}
