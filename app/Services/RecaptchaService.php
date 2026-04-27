<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RecaptchaService
{
    public function enabled(): bool
    {
        return (bool) (recaptcha_config()['enabled'] ?? false);
    }

    public function siteKey(): string
    {
        return (string) (recaptcha_config()['site_key'] ?? '');
    }

    public function validateOrFail(Request $request, string $action, string $field = 'recaptcha_token'): void
    {
        if (! $this->enabled()) {
            return;
        }

        $token = trim((string) $request->input($field));

        if ($token === '') {
            throw ValidationException::withMessages([
                $field => 'A verificacao de seguranca nao foi concluida. Atualize a pagina e tente novamente.',
            ]);
        }

        $result = $this->verify($token, $action, $request->ip());

        if ($result['success']) {
            return;
        }

        throw ValidationException::withMessages([
            $field => 'A verificacao de seguranca nao foi confirmada. Tente novamente.',
        ]);
    }

    public function verify(string $token, string $action, ?string $ipAddress = null): array
    {
        $config = recaptcha_config();

        if (! ($config['enabled'] ?? false)) {
            return [
                'success' => true,
                'score' => 1.0,
                'action' => $action,
                'errors' => [],
                'skipped' => true,
            ];
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post((string) $config['verify_url'], array_filter([
                'secret' => (string) $config['secret_key'],
                'response' => $token,
                'remoteip' => $ipAddress,
            ], fn ($value): bool => filled($value)));

        $payload = $response->json() ?: [];
        $score = (float) ($payload['score'] ?? 0);
        $matchedAction = (string) ($payload['action'] ?? '');

        return [
            'success' => (bool) ($payload['success'] ?? false)
                && $matchedAction === $action
                && $score >= (float) ($config['minimum_score'] ?? 0.5),
            'score' => $score,
            'action' => $matchedAction,
            'errors' => (array) ($payload['error-codes'] ?? []),
            'payload' => $payload,
        ];
    }
}
