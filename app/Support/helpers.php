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
