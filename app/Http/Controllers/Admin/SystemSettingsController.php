<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\ContactMessage;
use App\Models\LegalCase;
use App\Models\LegalCaseUpdate;
use App\Models\LegalTask;
use App\Models\Setting;
use App\Models\User;
use App\Support\PublicUpload;
use Database\Seeders\DemoOfficeSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SystemSettingsController extends Controller
{
    private const SETTINGS = [
        'branding.brand_name' => ['label' => 'Nome da marca', 'type' => 'text', 'public' => true, 'sort' => 520],
        'branding.brand_short_name' => ['label' => 'Sigla da marca', 'type' => 'text', 'public' => true, 'sort' => 521],
        'branding.admin_subtitle' => ['label' => 'Subtítulo do painel', 'type' => 'text', 'public' => true, 'sort' => 522],
        'branding.logo_path' => ['label' => 'Logo principal', 'type' => 'text', 'public' => true, 'sort' => 523],
        'branding.favicon_path' => ['label' => 'Favicon', 'type' => 'text', 'public' => true, 'sort' => 524],
        'branding.admin_footer_text' => ['label' => 'Rodapé do painel', 'type' => 'text', 'public' => false, 'sort' => 525],
        'branding.admin_footer_meta' => ['label' => 'Rodapé complementar', 'type' => 'text', 'public' => false, 'sort' => 526],
        'pwa.enabled' => ['label' => 'Ativar PWA', 'type' => 'boolean', 'public' => true, 'sort' => 530],
        'pwa.installation_enabled' => ['label' => 'Permitir instalação do PWA', 'type' => 'boolean', 'public' => true, 'sort' => 531],
        'pwa.install_prompt_enabled' => ['label' => 'Exibir anúncio de instalação', 'type' => 'boolean', 'public' => true, 'sort' => 532],
        'pwa.footer_install_enabled' => ['label' => 'Exibir instalação no rodapé', 'type' => 'boolean', 'public' => true, 'sort' => 533],
        'pwa.mobile_install_enabled' => ['label' => 'Exibir instalação no menu móvel', 'type' => 'boolean', 'public' => true, 'sort' => 534],
        'pwa.app_name' => ['label' => 'Nome do aplicativo', 'type' => 'text', 'public' => true, 'sort' => 535],
        'pwa.short_name' => ['label' => 'Nome curto do aplicativo', 'type' => 'text', 'public' => true, 'sort' => 536],
        'pwa.description' => ['label' => 'Descrição do PWA', 'type' => 'textarea', 'public' => true, 'sort' => 537],
        'pwa.start_path' => ['label' => 'URL inicial do PWA', 'type' => 'text', 'public' => true, 'sort' => 538],
        'pwa.scope' => ['label' => 'Escopo do PWA', 'type' => 'text', 'public' => true, 'sort' => 539],
        'pwa.display' => ['label' => 'Modo de exibição do PWA', 'type' => 'text', 'public' => true, 'sort' => 540],
        'pwa.orientation' => ['label' => 'Orientação do PWA', 'type' => 'text', 'public' => true, 'sort' => 541],
        'pwa.theme_color' => ['label' => 'Cor principal do PWA', 'type' => 'text', 'public' => true, 'sort' => 542],
        'pwa.background_color' => ['label' => 'Cor de fundo do PWA', 'type' => 'text', 'public' => true, 'sort' => 543],
        'pwa.icon_192' => ['label' => 'Ícone PWA 192', 'type' => 'text', 'public' => true, 'sort' => 544],
        'pwa.icon_512' => ['label' => 'Ícone PWA 512', 'type' => 'text', 'public' => true, 'sort' => 545],
        'pwa.popup_badge' => ['label' => 'Etiqueta do anúncio do PWA', 'type' => 'text', 'public' => true, 'sort' => 546],
        'pwa.popup_title' => ['label' => 'Título do anúncio do PWA', 'type' => 'text', 'public' => true, 'sort' => 547],
        'pwa.popup_description' => ['label' => 'Descrição do anúncio do PWA', 'type' => 'textarea', 'public' => true, 'sort' => 548],
        'pwa.popup_primary_label' => ['label' => 'Botão principal do anúncio do PWA', 'type' => 'text', 'public' => true, 'sort' => 549],
        'pwa.popup_secondary_label' => ['label' => 'Botão secundário do anúncio do PWA', 'type' => 'text', 'public' => true, 'sort' => 550],
        'pwa.footer_label' => ['label' => 'Texto do botão de instalação no rodapé', 'type' => 'text', 'public' => true, 'sort' => 551],
        'pwa.mobile_menu_label' => ['label' => 'Texto do botão de instalação no menu móvel', 'type' => 'text', 'public' => true, 'sort' => 552],
        'pwa.offline_title' => ['label' => 'Título da tela offline', 'type' => 'text', 'public' => true, 'sort' => 553],
        'pwa.offline_message' => ['label' => 'Mensagem da tela offline', 'type' => 'textarea', 'public' => true, 'sort' => 554],
        'pwa.offline_button_label' => ['label' => 'Botão da tela offline', 'type' => 'text', 'public' => true, 'sort' => 555],
        'security.recaptcha_enabled' => ['label' => 'Ativar reCAPTCHA v3', 'type' => 'boolean', 'public' => false, 'sort' => 560],
        'security.recaptcha_site_key' => ['label' => 'Site key do reCAPTCHA', 'type' => 'text', 'public' => false, 'sort' => 561],
        'security.recaptcha_secret_key' => ['label' => 'Secret key do reCAPTCHA', 'type' => 'text', 'public' => false, 'sort' => 562],
        'security.recaptcha_min_score' => ['label' => 'Score mínimo do reCAPTCHA', 'type' => 'text', 'public' => false, 'sort' => 563],
    ];

    public function index(): View
    {
        return view('admin.system-settings.index', [
            'pageTitle' => 'Configurações do sistema',
            'branding' => branding_config(),
            'pwa' => pwa_config(),
            'recaptcha' => recaptcha_config(),
            'pwaDisplayOptions' => [
                'browser' => 'Navegador',
                'minimal-ui' => 'Minimal UI',
                'standalone' => 'Standalone',
                'fullscreen' => 'Tela cheia',
            ],
            'pwaOrientationOptions' => [
                'any' => 'Qualquer orientação',
                'portrait' => 'Retrato',
                'landscape' => 'Paisagem',
                'natural' => 'Natural do dispositivo',
            ],
            'stats' => [
                'users' => User::query()->count(),
                'clients' => Client::query()->count(),
                'cases' => LegalCase::query()->count(),
                'tasks' => LegalTask::query()->count(),
                'updates' => LegalCaseUpdate::query()->count(),
                'calendar_events' => CalendarEvent::query()->count(),
                'messages' => ContactMessage::query()->count(),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:120'],
            'brand_short_name' => ['nullable', 'string', 'max:8'],
            'admin_subtitle' => ['nullable', 'string', 'max:80'],
            'admin_footer_text' => ['nullable', 'string', 'max:180'],
            'admin_footer_meta' => ['nullable', 'string', 'max:180'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg,webp,jpg,jpeg', 'max:2048'],
            'remove_favicon' => ['nullable', 'boolean'],
            'pwa_enabled' => ['nullable', 'boolean'],
            'pwa_installation_enabled' => ['nullable', 'boolean'],
            'pwa_install_prompt_enabled' => ['nullable', 'boolean'],
            'pwa_footer_install_enabled' => ['nullable', 'boolean'],
            'pwa_mobile_install_enabled' => ['nullable', 'boolean'],
            'pwa_app_name' => ['required', 'string', 'max:120'],
            'pwa_short_name' => ['required', 'string', 'max:32'],
            'pwa_description' => ['nullable', 'string', 'max:255'],
            'pwa_start_path' => ['required', 'string', 'max:255'],
            'pwa_scope' => ['required', 'string', 'max:255'],
            'pwa_display' => ['required', 'in:browser,minimal-ui,standalone,fullscreen'],
            'pwa_orientation' => ['required', 'in:any,portrait,landscape,natural'],
            'pwa_theme_color' => ['required', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'pwa_background_color' => ['required', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'pwa_icon_192' => ['nullable', 'image', 'max:4096'],
            'remove_pwa_icon_192' => ['nullable', 'boolean'],
            'pwa_icon_512' => ['nullable', 'image', 'max:6144'],
            'remove_pwa_icon_512' => ['nullable', 'boolean'],
            'pwa_popup_badge' => ['nullable', 'string', 'max:80'],
            'pwa_popup_title' => ['nullable', 'string', 'max:120'],
            'pwa_popup_description' => ['nullable', 'string', 'max:255'],
            'pwa_popup_primary_label' => ['nullable', 'string', 'max:60'],
            'pwa_popup_secondary_label' => ['nullable', 'string', 'max:60'],
            'pwa_footer_label' => ['nullable', 'string', 'max:60'],
            'pwa_mobile_menu_label' => ['nullable', 'string', 'max:60'],
            'pwa_offline_title' => ['nullable', 'string', 'max:120'],
            'pwa_offline_message' => ['nullable', 'string', 'max:255'],
            'pwa_offline_button_label' => ['nullable', 'string', 'max:60'],
            'recaptcha_enabled' => ['nullable', 'boolean'],
            'recaptcha_site_key' => ['nullable', 'string', 'max:255'],
            'recaptcha_secret_key' => ['nullable', 'string', 'max:255'],
            'recaptcha_min_score' => ['nullable', 'numeric', 'min:0.1', 'max:1'],
        ]);

        $currentLogo = (string) setting('branding.logo_path', '');
        $currentFavicon = (string) setting('branding.favicon_path', '');
        $currentPwaIcon192 = (string) setting('pwa.icon_192', '');
        $currentPwaIcon512 = (string) setting('pwa.icon_512', '');

        $payload = [
            'branding.brand_name' => $validated['brand_name'],
            'branding.brand_short_name' => $validated['brand_short_name'] ?? '',
            'branding.admin_subtitle' => $validated['admin_subtitle'] ?? '',
            'branding.logo_path' => $request->boolean('remove_logo') ? '' : $currentLogo,
            'branding.favicon_path' => $request->boolean('remove_favicon') ? '' : $currentFavicon,
            'branding.admin_footer_text' => $validated['admin_footer_text'] ?? '',
            'branding.admin_footer_meta' => $validated['admin_footer_meta'] ?? '',
            'pwa.enabled' => $request->boolean('pwa_enabled') ? '1' : '0',
            'pwa.installation_enabled' => $request->boolean('pwa_installation_enabled') ? '1' : '0',
            'pwa.install_prompt_enabled' => $request->boolean('pwa_install_prompt_enabled') ? '1' : '0',
            'pwa.footer_install_enabled' => $request->boolean('pwa_footer_install_enabled') ? '1' : '0',
            'pwa.mobile_install_enabled' => $request->boolean('pwa_mobile_install_enabled') ? '1' : '0',
            'pwa.app_name' => trim((string) $validated['pwa_app_name']),
            'pwa.short_name' => trim((string) $validated['pwa_short_name']),
            'pwa.description' => trim((string) ($validated['pwa_description'] ?? '')),
            'pwa.start_path' => trim((string) $validated['pwa_start_path']),
            'pwa.scope' => trim((string) $validated['pwa_scope']),
            'pwa.display' => $validated['pwa_display'],
            'pwa.orientation' => $validated['pwa_orientation'],
            'pwa.theme_color' => strtoupper((string) $validated['pwa_theme_color']),
            'pwa.background_color' => strtoupper((string) $validated['pwa_background_color']),
            'pwa.icon_192' => $request->boolean('remove_pwa_icon_192') ? '' : $currentPwaIcon192,
            'pwa.icon_512' => $request->boolean('remove_pwa_icon_512') ? '' : $currentPwaIcon512,
            'pwa.popup_badge' => trim((string) ($validated['pwa_popup_badge'] ?? '')),
            'pwa.popup_title' => trim((string) ($validated['pwa_popup_title'] ?? '')),
            'pwa.popup_description' => trim((string) ($validated['pwa_popup_description'] ?? '')),
            'pwa.popup_primary_label' => trim((string) ($validated['pwa_popup_primary_label'] ?? '')),
            'pwa.popup_secondary_label' => trim((string) ($validated['pwa_popup_secondary_label'] ?? '')),
            'pwa.footer_label' => trim((string) ($validated['pwa_footer_label'] ?? '')),
            'pwa.mobile_menu_label' => trim((string) ($validated['pwa_mobile_menu_label'] ?? '')),
            'pwa.offline_title' => trim((string) ($validated['pwa_offline_title'] ?? '')),
            'pwa.offline_message' => trim((string) ($validated['pwa_offline_message'] ?? '')),
            'pwa.offline_button_label' => trim((string) ($validated['pwa_offline_button_label'] ?? '')),
            'security.recaptcha_enabled' => $request->boolean('recaptcha_enabled') ? '1' : '0',
            'security.recaptcha_site_key' => trim((string) ($validated['recaptcha_site_key'] ?? '')),
            'security.recaptcha_secret_key' => trim((string) ($validated['recaptcha_secret_key'] ?? '')),
            'security.recaptcha_min_score' => number_format((float) ($validated['recaptcha_min_score'] ?? 0.5), 1, '.', ''),
        ];

        if ($request->boolean('remove_logo') && ! $request->hasFile('logo')) {
            $this->deleteManagedUpload($currentLogo);
        }

        if ($request->boolean('remove_favicon') && ! $request->hasFile('favicon')) {
            $this->deleteManagedUpload($currentFavicon);
        }

        if ($request->boolean('remove_pwa_icon_192') && ! $request->hasFile('pwa_icon_192')) {
            $this->deleteManagedUpload($currentPwaIcon192);
        }

        if ($request->boolean('remove_pwa_icon_512') && ! $request->hasFile('pwa_icon_512')) {
            $this->deleteManagedUpload($currentPwaIcon512);
        }

        if ($request->hasFile('logo')) {
            $payload['branding.logo_path'] = $this->storeUpload($request->file('logo'), 'branding/logo', $currentLogo);
        }

        if ($request->hasFile('favicon')) {
            $payload['branding.favicon_path'] = $this->storeUpload($request->file('favicon'), 'branding/favicon', $currentFavicon);
        }

        if ($request->hasFile('pwa_icon_192')) {
            $payload['pwa.icon_192'] = $this->storeUpload($request->file('pwa_icon_192'), 'branding/pwa', $currentPwaIcon192);
        }

        if ($request->hasFile('pwa_icon_512')) {
            $payload['pwa.icon_512'] = $this->storeUpload($request->file('pwa_icon_512'), 'branding/pwa', $currentPwaIcon512);
        }

        foreach (self::SETTINGS as $key => $meta) {
            $group = match (true) {
                str_starts_with($key, 'branding.') => 'branding',
                str_starts_with($key, 'pwa.') => 'pwa',
                str_starts_with($key, 'security.') => 'security',
                default => 'system',
            };

            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => $group,
                    'label' => $meta['label'],
                    'type' => $meta['type'],
                    'value' => $payload[$key] ?? '',
                    'json_value' => null,
                    'is_public' => $meta['public'],
                    'sort_order' => $meta['sort'],
                ],
            );
        }

        $this->clearCaches();

        activity_log('system-settings', 'updated', null, $payload, 'Configurações do sistema atualizadas.');

        return response()->json([
            'message' => 'Configurações do sistema atualizadas com sucesso.',
            'redirect' => route('admin.system-settings.index'),
            'closeModal' => false,
        ]);
    }

    public function seedDemoData(): JsonResponse
    {
        Artisan::call('db:seed', [
            '--class' => DemoOfficeSeeder::class,
            '--force' => true,
        ]);

        $this->clearCaches();

        activity_log('system-settings', 'seeded-demo-data', null, [
            'clients' => Client::query()->count(),
            'cases' => LegalCase::query()->count(),
            'tasks' => LegalTask::query()->count(),
            'events' => CalendarEvent::query()->count(),
        ], 'Dados de exemplo populados.');

        return response()->json([
            'message' => 'Dados de exemplo populados com sucesso.',
            'redirect' => route('admin.system-settings.index'),
            'closeModal' => false,
        ]);
    }

    private function clearCaches(): void
    {
        foreach ([
            'site_settings.all',
            'site_settings.all.v2',
            'site_settings.map',
            'site_settings.map.v2',
            'branding.config.v1',
            'pwa.config.v1',
            'recaptcha.config.v1',
            'preloader.settings.v1',
            'site_pages.menu.v2',
            'site_pages.public.v2',
        ] as $key) {
            Cache::forget($key);
        }
    }

    private function storeUpload(UploadedFile $file, string $directory, ?string $currentPath): string
    {
        return PublicUpload::store($file, $directory, $currentPath, auth()->id());
    }

    private function deleteManagedUpload(?string $path): void
    {
        $normalized = ltrim((string) $path, '/');

        if ($normalized === '' || ! Str::startsWith($normalized, 'uploads/')) {
            return;
        }

        PublicUpload::delete($normalized);
    }
}
