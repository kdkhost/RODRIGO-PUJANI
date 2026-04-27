<?php

use App\Models\ActivityLog;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('settings')) {
                return $default;
            }

            $settings = Cache::rememberForever('site_settings.map.v2', fn () => Setting::query()
                ->select(['key', 'type', 'value', 'json_value'])
                ->get()
                ->mapWithKeys(fn (Setting $setting): array => [
                    $setting->key => [
                        'type' => $setting->type,
                        'value' => $setting->value,
                        'json_value' => $setting->json_value,
                    ],
                ])
                ->all());

            $item = $settings[$key] ?? null;

            if (! $item) {
                return $default;
            }

            return ($item['type'] ?? null) === 'json'
                ? ($item['json_value'] ?? $default)
                : ($item['value'] ?? $default);
        } catch (Throwable) {
            return $default;
        }
    }
}

if (! function_exists('preloader_config')) {
    function preloader_config(string $surface = 'site', bool $respectScope = true): array
    {
        try {
            $config = Cache::rememberForever('preloader.settings.v1', function (): array {
                $logoPath = (string) setting('preloader.logo_path', '');
                $logoUrl = null;

                if (filled($logoPath)) {
                    $logoUrl = \Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://'])
                        ? $logoPath
                        : site_asset_url($logoPath);
                }

                return [
                    'enabled' => filter_var(setting('preloader.enabled', '0'), FILTER_VALIDATE_BOOLEAN),
                    'scope' => (string) setting('preloader.scope', 'all'),
                    'style' => (string) setting('preloader.style', 'spinner'),
                    'brand' => (string) setting('preloader.brand', config('app.name')),
                    'message' => (string) setting('preloader.message', 'Carregando experiência segura...'),
                    'background_color' => (string) setting('preloader.background_color', '#0f1318'),
                    'accent_color' => (string) setting('preloader.accent_color', '#c49a3c'),
                    'text_color' => (string) setting('preloader.text_color', '#f4ead7'),
                    'logo_path' => $logoPath,
                    'logo_url' => $logoUrl,
                    'min_duration' => max(0, min(6000, (int) setting('preloader.min_duration', '650'))),
                    'custom_css' => (string) setting('preloader.custom_css', ''),
                ];
            });

            if ($respectScope && $config['scope'] !== 'all' && $config['scope'] !== $surface) {
                $config['enabled'] = false;
            }

            return $config;
        } catch (Throwable) {
            return [
                'enabled' => false,
                'scope' => 'all',
                'style' => 'spinner',
                'brand' => config('app.name'),
                'message' => '',
                'background_color' => '#0f1318',
                'accent_color' => '#c49a3c',
                'text_color' => '#f4ead7',
                'logo_path' => '',
                'logo_url' => null,
                'min_duration' => 0,
                'custom_css' => '',
            ];
        }
    }
}

if (! function_exists('branding_config')) {
    function branding_config(): array
    {
        try {
            return Cache::rememberForever('branding.config.v1', function (): array {
                $brandName = (string) setting('branding.brand_name', config('app.name'));
                $brandShort = (string) setting('branding.brand_short_name', Str::upper(Str::substr($brandName, 0, 1)));
                $logoPath = (string) setting('branding.logo_path', '');
                $faviconPath = (string) setting('branding.favicon_path', '');

                return [
                    'brand_name' => $brandName,
                    'brand_short_name' => $brandShort !== '' ? $brandShort : 'P',
                    'admin_subtitle' => (string) setting('branding.admin_subtitle', 'Painel administrativo'),
                    'logo_path' => $logoPath,
                    'logo_url' => site_asset_url($logoPath),
                    'favicon_path' => $faviconPath,
                    'favicon_url' => site_asset_url($faviconPath) ?: site_asset_url(setting('pwa.icon_192', 'pwa/icon-192.png')),
                    'admin_footer_text' => (string) setting('branding.admin_footer_text', 'Painel administrativo premium para operacao juridica.'),
                    'admin_footer_meta' => (string) setting('branding.admin_footer_meta', 'Laravel 13 | PHP 8.4 | Multiusuario'),
                ];
            });
        } catch (Throwable) {
            return [
                'brand_name' => config('app.name'),
                'brand_short_name' => 'P',
                'admin_subtitle' => 'Painel administrativo',
                'logo_path' => '',
                'logo_url' => null,
                'favicon_path' => '',
                'favicon_url' => null,
                'admin_footer_text' => 'Painel administrativo premium para operacao juridica.',
                'admin_footer_meta' => 'Laravel 13 | PHP 8.4 | Multiusuario',
            ];
        }
    }
}

if (! function_exists('recaptcha_config')) {
    function recaptcha_config(): array
    {
        try {
            return Cache::rememberForever('recaptcha.config.v1', function (): array {
                $enabled = filter_var(setting('security.recaptcha_enabled', env('RECAPTCHA_ENABLED', '0')), FILTER_VALIDATE_BOOLEAN);
                $siteKey = trim((string) setting('security.recaptcha_site_key', env('RECAPTCHA_SITE_KEY', '')));
                $secretKey = trim((string) setting('security.recaptcha_secret_key', env('RECAPTCHA_SECRET_KEY', '')));
                $minimumScore = (float) setting('security.recaptcha_min_score', env('RECAPTCHA_MIN_SCORE', '0.5'));
                $minimumScore = max(0.1, min(1.0, $minimumScore));

                return [
                    'enabled' => $enabled && $siteKey !== '' && $secretKey !== '',
                    'site_key' => $siteKey,
                    'secret_key' => $secretKey,
                    'minimum_score' => $minimumScore,
                    'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
                ];
            });
        } catch (Throwable) {
            return [
                'enabled' => false,
                'site_key' => '',
                'secret_key' => '',
                'minimum_score' => 0.5,
                'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
            ];
        }
    }
}

if (! function_exists('site_asset_url')) {
    function site_asset_url(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        if (file_exists(public_path($normalized))) {
            return asset($normalized);
        }

        return asset('storage/'.$normalized);
    }
}

if (! function_exists('public_pages')) {
    function public_pages()
    {
        try {
            if (! Schema::hasTable('pages')) {
                return collect();
            }

            $pages = Cache::rememberForever('site_pages.public.v2', fn () => Page::query()
                ->where('status', 'published')
                ->orderBy('sort_order')
                ->get(['id', 'title', 'menu_title', 'slug', 'is_home'])
                ->map(fn (Page $page): array => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'menu_title' => $page->menu_title,
                    'slug' => $page->slug,
                    'is_home' => $page->is_home,
                ])
                ->all());

            return collect($pages)->map(fn (array $page): object => (object) $page);
        } catch (Throwable) {
            return collect();
        }
    }
}

if (! function_exists('activity_log')) {
    function activity_log(string $module, string $event, ?Model $subject = null, array $properties = [], ?string $description = null): void
    {
        try {
            if (! Schema::hasTable('activity_logs')) {
                return;
            }

            ActivityLog::query()->create([
                'user_id' => auth()->id(),
                'module' => $module,
                'event' => $event,
                'description' => $description,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'properties' => $properties,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        } catch (Throwable) {
            return;
        }
    }
}
