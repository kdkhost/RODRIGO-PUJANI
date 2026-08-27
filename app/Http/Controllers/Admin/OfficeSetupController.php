<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationCredential;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OfficeSetupController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $fields = $this->officeFields();
        $office = collect($fields)->mapWithKeys(
            fn (array $meta, string $key): array => [$key => setting('site.'.$key, $meta['default'] ?? '')]
        )->all();

        $required = [
            $office['company_legal_name'], $office['company_document'], $office['company_phone'],
            $office['company_email'], $office['address_zip'], $office['address_street'],
            $office['address_number'], $office['address_city'], $office['address_state'],
            $user?->name, $user?->email, $user?->phone, $user?->document_number,
            $user?->professional_title, $user?->oab_number, $user?->oab_state,
        ];
        $completed = collect($required)->filter(fn (mixed $value): bool => filled($value))->count();

        $ai = IntegrationCredential::query()->where('service', 'legal_ai')->first();
        $mail = smtp_config();
        $heartbeatPath = storage_path('app/system/scheduler-heartbeat.json');
        $schedulerReady = File::exists($heartbeatPath)
            && now()->diffInMinutes(Carbon::createFromTimestamp(File::lastModified($heartbeatPath)), true) <= 5;

        return view('admin.office-setup.edit', [
            'pageTitle' => 'Configuração inicial',
            'office' => $office,
            'responsible' => $user,
            'completion' => (int) round(($completed / count($required)) * 100),
            'readiness' => [
                ['label' => 'Dados do escritório', 'ready' => filled($office['company_legal_name']) && filled($office['company_document']), 'url' => '#office-data'],
                ['label' => 'Responsável e OAB', 'ready' => filled($user?->oab_number) && filled($user?->oab_state), 'url' => '#responsible-data'],
                ['label' => 'SMTP', 'ready' => (bool) ($mail['enabled'] ?? false) && filled($mail['host'] ?? null), 'url' => route('admin.system-settings.show', 'mail')],
                ['label' => 'Google Calendar', 'ready' => (bool) config('google-calendar.enabled') && filled(config('google-calendar.client_id')), 'url' => route('admin.google-calendar.index')],
                ['label' => 'IA e transcrição', 'ready' => (bool) $ai?->enabled && filled($ai?->secret), 'url' => route('admin.legal-ai.index')],
                ['label' => 'Assinatura eletrônica', 'ready' => (bool) config('signatures.enabled'), 'url' => route('admin.documentation.index').'#assinaturas'],
                ['label' => 'Agendador e filas', 'ready' => $schedulerReady, 'url' => route('admin.documentation.index').'#infraestrutura'],
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_legal_name' => ['required', 'string', 'max:180'],
            'company_trade_name' => ['required', 'string', 'max:120'],
            'company_document' => ['required', 'string', 'max:24'],
            'company_oab_registration' => ['nullable', 'string', 'max:60'],
            'company_phone' => ['required', 'string', 'max:30'],
            'company_whatsapp' => ['nullable', 'string', 'max:30'],
            'company_email' => ['required', 'email', 'max:255'],
            'company_secondary_email' => ['nullable', 'email', 'max:255'],
            'business_hours' => ['required', 'string', 'max:180'],
            'address_zip' => ['required', 'string', 'max:12'],
            'address_street' => ['required', 'string', 'max:255'],
            'address_number' => ['required', 'string', 'max:20'],
            'address_complement' => ['nullable', 'string', 'max:120'],
            'address_district' => ['required', 'string', 'max:120'],
            'address_city' => ['required', 'string', 'max:120'],
            'address_state' => ['required', 'string', 'size:2'],
            'responsible_name' => ['required', 'string', 'max:255'],
            'responsible_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()?->id)],
            'responsible_phone' => ['required', 'string', 'max:30'],
            'responsible_whatsapp' => ['nullable', 'string', 'max:30'],
            'responsible_document' => ['required', 'string', 'max:32'],
            'professional_title' => ['required', 'string', 'max:120'],
            'oab_number' => ['required', 'string', 'max:30'],
            'oab_state' => ['required', 'string', 'size:2'],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            foreach ($this->officeFields() as $key => $meta) {
                $value = trim((string) ($validated[$key] ?? ''));
                if ($key === 'address_state') {
                    $value = strtoupper($value);
                }

                Setting::query()->updateOrCreate(['key' => 'site.'.$key], [
                    'group' => 'site', 'label' => $meta['label'], 'type' => 'text',
                    'value' => $value, 'json_value' => null, 'is_public' => true,
                    'sort_order' => $meta['sort'],
                ]);
            }

            $address = collect([
                $validated['address_street'].', '.$validated['address_number'],
                $validated['address_complement'] ?? null,
                $validated['address_district'],
                $validated['address_city'].'/'.strtoupper((string) $validated['address_state']),
                'CEP '.$validated['address_zip'],
            ])->filter()->implode(' · ');

            foreach ([
                'site.company_address' => ['Endereço', $address, 17],
                'site.company_cep' => ['CEP', $validated['address_zip'], 18],
            ] as $key => [$label, $value, $sort]) {
                Setting::query()->updateOrCreate(['key' => $key], [
                    'group' => 'site', 'label' => $label, 'type' => 'text',
                    'value' => $value, 'json_value' => null, 'is_public' => true,
                    'sort_order' => $sort,
                ]);
            }

            Setting::query()->updateOrCreate(['key' => 'branding.brand_name'], [
                'group' => 'branding', 'label' => 'Nome da marca', 'type' => 'text',
                'value' => trim((string) $validated['company_trade_name']), 'json_value' => null,
                'is_public' => true, 'sort_order' => 520,
            ]);

            $request->user()?->forceFill([
                'name' => trim((string) $validated['responsible_name']),
                'email' => strtolower(trim((string) $validated['responsible_email'])),
                'phone' => $validated['responsible_phone'],
                'whatsapp' => $validated['responsible_whatsapp'] ?? null,
                'document_number' => $validated['responsible_document'],
                'professional_title' => $validated['professional_title'],
                'oab_number' => $validated['oab_number'],
                'oab_state' => strtoupper((string) $validated['oab_state']),
                'timezone' => $validated['timezone'],
            ])->save();
        });

        foreach (['site_settings.map.v2', 'site_settings.all.v2', 'branding.config.v1'] as $key) {
            Cache::forget($key);
        }

        activity_log('office-setup', 'updated', $request->user(), [
            'company_configured' => true,
            'professional_registration_configured' => true,
        ], 'Configuração inicial do escritório atualizada.');

        return response()->json([
            'message' => 'Dados do escritório e do responsável atualizados com sucesso.',
            'redirect' => route('admin.office-setup.edit'),
        ]);
    }

    private function officeFields(): array
    {
        return [
            'company_legal_name' => ['label' => 'Razão social', 'sort' => 1],
            'company_trade_name' => ['label' => 'Nome do escritório', 'sort' => 2],
            'company_document' => ['label' => 'CNPJ', 'sort' => 3],
            'company_oab_registration' => ['label' => 'Registro da sociedade na OAB', 'sort' => 4],
            'company_phone' => ['label' => 'Telefone', 'sort' => 5],
            'company_whatsapp' => ['label' => 'WhatsApp', 'sort' => 6],
            'company_email' => ['label' => 'E-mail principal', 'sort' => 7],
            'company_secondary_email' => ['label' => 'E-mail secundário', 'sort' => 8],
            'business_hours' => ['label' => 'Horário de atendimento', 'sort' => 9],
            'address_zip' => ['label' => 'CEP', 'sort' => 10],
            'address_street' => ['label' => 'Logradouro', 'sort' => 11],
            'address_number' => ['label' => 'Número', 'sort' => 12],
            'address_complement' => ['label' => 'Complemento', 'sort' => 13],
            'address_district' => ['label' => 'Bairro', 'sort' => 14],
            'address_city' => ['label' => 'Cidade', 'sort' => 15],
            'address_state' => ['label' => 'UF', 'sort' => 16],
        ];
    }
}
