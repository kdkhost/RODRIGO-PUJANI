<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\Setting;
use App\Services\InstallerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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
        View::composer('*', function ($view): void {
            try {
                if (! app(InstallerService::class)->isInstalled() || ! Schema::hasTable('settings')) {
                    $view->with('siteSettings', collect());
                    $view->with('publicPages', collect());

                    return;
                }

                $publicPages = Schema::hasTable('pages')
                    ? Cache::rememberForever('site_pages.menu', fn () => Page::query()
                        ->where('show_in_menu', true)
                        ->where('status', 'published')
                        ->orderBy('sort_order')
                        ->get())
                    : collect();

                $view->with('siteSettings', Cache::rememberForever('site_settings.all', fn () => Setting::query()
                    ->orderBy('group')
                    ->orderBy('sort_order')
                    ->get()
                    ->keyBy('key')));
                $view->with('publicPages', $publicPages);
            } catch (Throwable) {
                $view->with('siteSettings', collect());
                $view->with('publicPages', collect());
            }
        });
    }
}
