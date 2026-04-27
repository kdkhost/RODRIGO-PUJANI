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
        'security.recaptcha_enabled' => ['label' => 'Ativar reCAPTCHA v3', 'type' => 'boolean', 'public' => false, 'sort' => 540],
        'security.recaptcha_site_key' => ['label' => 'Site key do reCAPTCHA', 'type' => 'text', 'public' => false, 'sort' => 541],
        'security.recaptcha_secret_key' => ['label' => 'Secret key do reCAPTCHA', 'type' => 'text', 'public' => false, 'sort' => 542],
        'security.recaptcha_min_score' => ['label' => 'Score mínimo do reCAPTCHA', 'type' => 'text', 'public' => false, 'sort' => 543],
    ];

    public function index(): View
    {
        return view('admin.system-settings.index', [
            'pageTitle' => 'Configurações do sistema',
            'branding' => branding_config(),
            'recaptcha' => recaptcha_config(),
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
            'recaptcha_enabled' => ['nullable', 'boolean'],
            'recaptcha_site_key' => ['nullable', 'string', 'max:255'],
            'recaptcha_secret_key' => ['nullable', 'string', 'max:255'],
            'recaptcha_min_score' => ['nullable', 'numeric', 'min:0.1', 'max:1'],
        ]);

        $currentLogo = (string) setting('branding.logo_path', '');
        $currentFavicon = (string) setting('branding.favicon_path', '');

        $payload = [
            'branding.brand_name' => $validated['brand_name'],
            'branding.brand_short_name' => $validated['brand_short_name'] ?? '',
            'branding.admin_subtitle' => $validated['admin_subtitle'] ?? '',
            'branding.logo_path' => $request->boolean('remove_logo') ? '' : $currentLogo,
            'branding.favicon_path' => $request->boolean('remove_favicon') ? '' : $currentFavicon,
            'branding.admin_footer_text' => $validated['admin_footer_text'] ?? '',
            'branding.admin_footer_meta' => $validated['admin_footer_meta'] ?? '',
            'security.recaptcha_enabled' => $request->boolean('recaptcha_enabled') ? '1' : '0',
            'security.recaptcha_site_key' => trim((string) ($validated['recaptcha_site_key'] ?? '')),
            'security.recaptcha_secret_key' => trim((string) ($validated['recaptcha_secret_key'] ?? '')),
            'security.recaptcha_min_score' => number_format((float) ($validated['recaptcha_min_score'] ?? 0.5), 1, '.', ''),
        ];

        if ($request->boolean('remove_logo') && ! $request->hasFile('logo')) {
            PublicUpload::delete($currentLogo);
        }

        if ($request->boolean('remove_favicon') && ! $request->hasFile('favicon')) {
            PublicUpload::delete($currentFavicon);
        }

        if ($request->hasFile('logo')) {
            $payload['branding.logo_path'] = $this->storeUpload($request->file('logo'), 'branding/logo', $currentLogo);
        }

        if ($request->hasFile('favicon')) {
            $payload['branding.favicon_path'] = $this->storeUpload($request->file('favicon'), 'branding/favicon', $currentFavicon);
        }

        foreach (self::SETTINGS as $key => $meta) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => str_contains($key, 'security.') ? 'security' : 'branding',
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
}
