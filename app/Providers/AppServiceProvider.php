<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Services\InstallerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Throwable;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Paginator::defaultView('vendor.pagination.premium');


        View::composer('*', function ($view): void {

            try {
                if (! app(InstallerService::class)->isInstalled() || ! Schema::hasTable('settings')) {
                    $view->with('siteSettings', collect());
                    $view->with('publicPages', collect());

                    return;
                }

                $publicPages = Schema::hasTable('pages')
                    ? collect(Cache::rememberForever('site_pages.menu.v2', fn () => Page::query()
                        ->where('show_in_menu', true)
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
                        ->all()))
                        ->map(fn (array $page): object => (object) $page)
                    : collect();

                $settings = Cache::rememberForever('site_settings.all.v2', fn () => Setting::query()
                    ->orderBy('group')
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(fn (Setting $setting): array => [
                        $setting->key => [
                            'id' => $setting->id,
                            'group' => $setting->group,
                            'key' => $setting->key,
                            'label' => $setting->label,
                            'type' => $setting->type,
                            'value' => $setting->value,
                            'json_value' => $setting->json_value,
                            'is_public' => $setting->is_public,
                            'sort_order' => $setting->sort_order,
                        ],
                    ])
                    ->all());

                $view->with('siteSettings', collect($settings)->map(fn (array $setting): object => (object) $setting));
                $view->with('publicPages', $publicPages);

                $whatsappMultipleEnabled = ($settings['site.whatsapp_multiple_support']['value'] ?? '0') === '1';
                $whatsappTeamMembers = $whatsappMultipleEnabled && Schema::hasTable('team_members')
                    ? Cache::rememberForever('site_whatsapp.team.v1', fn () => TeamMember::query()
                        ->where('is_active', true)
                        ->whereNotNull('whatsapp')
                        ->orderBy('sort_order')
                        ->get(['id', 'name', 'role', 'whatsapp', 'image_path'])
                        ->toArray())
                    : [];

                $view->with('whatsappTeamMembers', collect($whatsappTeamMembers)->map(fn($m) => (object)$m));
            } catch (Throwable) {
                $view->with('siteSettings', collect());
                $view->with('publicPages', collect());
            }
        });
    }
}
