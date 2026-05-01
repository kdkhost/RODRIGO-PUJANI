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

if (! function_exists('seo_config')) {
    function seo_config(): array
    {
        try {
            return Cache::rememberForever('seo.config.v1', function (): array {
                $ogImagePath = (string) setting('seo.og_image_path', '');
                
                return [
                    'title_suffix' => (string) setting('seo.title_suffix', ' - Pujani Advogados'),
                    'meta_description' => (string) setting('seo.meta_description', 'Escritório de advocacia premium especializado em soluções jurídicas personalizadas.'),
                    'meta_keywords' => (string) setting('seo.meta_keywords', 'advogado, jurídico, processos, justiça, consultoria'),
                    'hashtags' => (string) setting('seo.hashtags', '#pujaniadvogados #advocacia #justiça #direito'),
                    'author' => (string) setting('seo.author', 'Rodrigo Pujani'),
                    'og_image_path' => $ogImagePath,
                    'og_image_url' => site_asset_url($ogImagePath),
                    'google_analytics_id' => (string) setting('seo.google_analytics_id', ''),
                    'google_site_verification' => (string) setting('seo.google_site_verification', ''),
                    'bing_site_verification' => (string) setting('seo.bing_site_verification', ''),
                ];
            });
        } catch (Throwable) {
            return [
                'title_suffix' => ' - Pujani Advogados',
                'meta_description' => 'Escritório de advocacia premium especializado em soluções jurídicas personalizadas.',
                'meta_keywords' => 'advogado, jurídico, processos, justiça, consultoria',
                'hashtags' => '#pujaniadvogados #advocacia #justiça #direito',
                'author' => 'Rodrigo Pujani',
                'og_image_path' => '',
                'og_image_url' => null,
                'google_analytics_id' => '',
                'google_site_verification' => '',
                'bing_site_verification' => '',
            ];
        }
    }
}

if (! function_exists('smtp_config')) {
    function smtp_config(): array
    {
        try {
            return Cache::rememberForever('mail.config.v1', function (): array {
                return [
                    'enabled' => filter_var(setting('mail.enabled', '0'), FILTER_VALIDATE_BOOLEAN),
                    'mailer' => (string) setting('mail.mailer', env('MAIL_MAILER', 'smtp')),
                    'host' => (string) setting('mail.host', env('MAIL_HOST', '127.0.0.1')),
                    'port' => (int) setting('mail.port', env('MAIL_PORT', 587)),
                    'encryption' => (string) setting('mail.encryption', env('MAIL_ENCRYPTION', 'tls')),
                    'username' => (string) setting('mail.username', env('MAIL_USERNAME', '')),
                    'password' => (string) setting('mail.password', env('MAIL_PASSWORD', '')),
                    'from_address' => (string) setting('mail.from_address', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
                    'from_name' => (string) setting('mail.from_name', env('MAIL_FROM_NAME', config('app.name'))),
                    'template_header' => (string) setting('mail.template_header', 'Olá, {{name}}.'),
                    'template_footer' => (string) setting('mail.template_footer', 'Equipe {{app_name}}'),
                    'template_reset_subject' => (string) setting('mail.template_reset_subject', 'Redefinição de senha'),
                    'template_reset_body' => (string) setting('mail.template_reset_body', "Recebemos uma solicitação para redefinir sua senha.\n\nClique no botão abaixo para continuar."),
                    'template_generic_subject' => (string) setting('mail.template_generic_subject', 'Notificação do sistema'),
                    'template_generic_body' => (string) setting('mail.template_generic_body', "Olá, {{name}}.\n\nVocê recebeu uma nova notificação do sistema."),
                ];
            });
        } catch (Throwable) {
            return [
                'enabled' => false,
                'mailer' => env('MAIL_MAILER', 'smtp'),
                'host' => env('MAIL_HOST', '127.0.0.1'),
                'port' => (int) env('MAIL_PORT', 587),
                'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'username' => env('MAIL_USERNAME', ''),
                'password' => env('MAIL_PASSWORD', ''),
                'from_address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'from_name' => env('MAIL_FROM_NAME', config('app.name')),
                'template_header' => 'Olá, {{name}}.',
                'template_footer' => 'Equipe {{app_name}}',
                'template_reset_subject' => 'Redefinição de senha',
                'template_reset_body' => "Recebemos uma solicitação para redefinir sua senha.\n\nClique no botão abaixo para continuar.",
                'template_generic_subject' => 'Notificação do sistema',
                'template_generic_body' => "Olá, {{name}}.\n\nVocê recebeu uma nova notificação do sistema.",
            ];
        }
    }
}

if (! function_exists('pwa_config')) {
    function pwa_config(): array
    {
        try {
            return Cache::rememberForever('pwa.config.v1', function (): array {
                $faviconPath = (string) setting('branding.favicon_path', '');
                $icon192Path = trim((string) setting('pwa.icon_192', ''));
                $icon512Path = trim((string) setting('pwa.icon_512', ''));
                $defaultIcon192 = $icon192Path !== '' ? $icon192Path : 'pwa/icon-192.png';
                $defaultIcon512 = $icon512Path !== '' ? $icon512Path : 'pwa/icon-512.png';
                $enabled = filter_var(setting('pwa.enabled', '1'), FILTER_VALIDATE_BOOLEAN);
                $installEnabled = $enabled && filter_var(setting('pwa.installation_enabled', '1'), FILTER_VALIDATE_BOOLEAN);

                return [
                    'enabled' => $enabled,
                    'installation_enabled' => $installEnabled,
                    'install_prompt_enabled' => $installEnabled && filter_var(setting('pwa.install_prompt_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
                    'footer_install_enabled' => $installEnabled && filter_var(setting('pwa.footer_install_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
                    'mobile_install_enabled' => $installEnabled && filter_var(setting('pwa.mobile_install_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
                    'app_name' => (string) setting('pwa.app_name', config('app.name')),
                    'short_name' => (string) setting('pwa.short_name', 'Pujani'),
                    'description' => (string) setting('pwa.description', 'Portal institucional e administrativo da Pujani Advogados.'),
                    'theme_color' => (string) setting('pwa.theme_color', '#0B0C10'),
                    'background_color' => (string) setting('pwa.background_color', '#0B0C10'),
                    'start_path' => (string) setting('pwa.start_path', '/'),
                    'scope' => (string) setting('pwa.scope', '/'),
                    'display' => (string) setting('pwa.display', 'standalone'),
                    'orientation' => (string) setting('pwa.orientation', 'portrait'),
                    'icon_192_path' => $icon192Path,
                    'icon_192_url' => site_asset_url($icon192Path) ?: site_asset_url($defaultIcon192) ?: site_asset_url($faviconPath),
                    'icon_512_path' => $icon512Path,
                    'icon_512_url' => site_asset_url($icon512Path) ?: site_asset_url($defaultIcon512) ?: site_asset_url($faviconPath),
                    'popup_badge' => (string) setting('pwa.popup_badge', 'Aplicativo disponível'),
                    'popup_title' => (string) setting('pwa.popup_title', 'Instale o app do escritório'),
                    'popup_description' => (string) setting('pwa.popup_description', 'Adicione o site à tela inicial para abrir mais rápido, com aparência de aplicativo e suporte offline.'),
                    'popup_primary_label' => (string) setting('pwa.popup_primary_label', 'Instalar agora'),
                    'popup_secondary_label' => (string) setting('pwa.popup_secondary_label', 'Agora não'),
                    'footer_label' => (string) setting('pwa.footer_label', 'Instalar aplicativo'),
                    'mobile_menu_label' => (string) setting('pwa.mobile_menu_label', 'Instalar aplicativo'),
                    'offline_title' => (string) setting('pwa.offline_title', 'Você está offline.'),
                    'offline_message' => (string) setting('pwa.offline_message', 'Não foi possível carregar o conteúdo agora. Quando a conexão voltar, a navegação será retomada normalmente.'),
                    'offline_button_label' => (string) setting('pwa.offline_button_label', 'Tentar novamente'),
                    'prompt_storage_key' => 'site-pwa-promo-dismissed-v1',
                ];
            });
        } catch (Throwable) {
            return [
                'enabled' => true,
                'installation_enabled' => true,
                'install_prompt_enabled' => true,
                'footer_install_enabled' => true,
                'mobile_install_enabled' => true,
                'app_name' => config('app.name'),
                'short_name' => 'Pujani',
                'description' => 'Portal institucional e administrativo da Pujani Advogados.',
                'theme_color' => '#0B0C10',
                'background_color' => '#0B0C10',
                'start_path' => '/',
                'scope' => '/',
                'display' => 'standalone',
                'orientation' => 'portrait',
                'icon_192_path' => '',
                'icon_192_url' => null,
                'icon_512_path' => '',
                'icon_512_url' => null,
                'popup_badge' => 'Aplicativo disponível',
                'popup_title' => 'Instale o app do escritório',
                'popup_description' => 'Adicione o site à tela inicial para abrir mais rápido, com aparência de aplicativo e suporte offline.',
                'popup_primary_label' => 'Instalar agora',
                'popup_secondary_label' => 'Agora não',
                'footer_label' => 'Instalar aplicativo',
                'mobile_menu_label' => 'Instalar aplicativo',
                'offline_title' => 'Você está offline.',
                'offline_message' => 'Não foi possível carregar o conteúdo agora. Quando a conexão voltar, a navegação será retomada normalmente.',
                'offline_button_label' => 'Tentar novamente',
                'prompt_storage_key' => 'site-pwa-promo-dismissed-v1',
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
