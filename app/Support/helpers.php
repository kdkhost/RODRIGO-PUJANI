<?php

use App\Models\ActivityLog;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

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
                        : asset('storage/'.ltrim($logoPath, '/'));
                }

                return [
                    'enabled' => filter_var(setting('preloader.enabled', '0'), FILTER_VALIDATE_BOOLEAN),
                    'scope' => (string) setting('preloader.scope', 'all'),
                    'style' => (string) setting('preloader.style', 'spinner'),
                    'brand' => (string) setting('preloader.brand', config('app.name')),
                    'message' => (string) setting('preloader.message', 'Carregando experiencia segura...'),
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
