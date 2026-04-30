<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuthAppearanceController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ClientPortalController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\DocumentationController;
use App\Http\Controllers\Admin\LegalCaseController;
use App\Http\Controllers\Admin\LegalCaseUpdateController;
use App\Http\Controllers\Admin\LegalDocumentController;
use App\Http\Controllers\Admin\LegalTaskController;
use App\Http\Controllers\Admin\MediaAssetController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PageSectionController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PreloaderController;
use App\Http\Controllers\Admin\PracticeAreaController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeoMetaController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SystemFileController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Portal\ClientPortalController as PortalClientPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

$crud = function (string $uri, string $name, string $controller, string $permission): void {
    Route::middleware('permission:'.$permission)->group(function () use ($uri, $name, $controller): void {
        Route::get($uri, [$controller, 'index'])->name($name.'.index');
        Route::get($uri.'/create', [$controller, 'create'])->name($name.'.create');
        Route::post($uri, [$controller, 'store'])->name($name.'.store');
        Route::get($uri.'/{record}/edit', [$controller, 'edit'])->name($name.'.edit');
        Route::match(['put', 'patch'], $uri.'/{record}', [$controller, 'update'])->name($name.'.update');
        Route::delete($uri.'/{record}', [$controller, 'destroy'])->name($name.'.destroy');
    });
};

Route::get('/instalar', [InstallController::class, 'index'])->name('install.index');
Route::post('/instalar', [InstallController::class, 'store'])->name('install.store');

Route::middleware(['check.maintenance', 'track.visit'])->group(function () {
    Route::get('/manifest.webmanifest', [SiteController::class, 'manifest'])->name('site.manifest');
    Route::get('/sw.js', [SiteController::class, 'serviceWorker'])->name('site.service-worker');
    Route::get('/pwa/limpar', [SiteController::class, 'pwaCleanup'])->name('site.pwa-cleanup');
    Route::get('/offline', [SiteController::class, 'offline'])->name('site.offline');
    Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('site.sitemap');
    Route::get('/robots.txt', [SiteController::class, 'robots'])->name('site.robots');
    Route::post('/contato/enviar', [SiteController::class, 'submitContact'])->name('site.contact.submit');
    Route::prefix('portal-cliente')->name('portal.')->group(function (): void {
        Route::get('/', [PortalClientPortalController::class, 'login'])->name('login');
        Route::post('/entrar', [PortalClientPortalController::class, 'authenticate'])->name('authenticate');
        Route::post('/sair', [PortalClientPortalController::class, 'logout'])->name('logout');

        Route::middleware('portal.client')->group(function (): void {
            Route::get('/painel', [PortalClientPortalController::class, 'dashboard'])->name('dashboard');
            Route::get('/perfil', [PortalClientPortalController::class, 'profile'])->name('profile');
            Route::put('/perfil', [PortalClientPortalController::class, 'updateProfile'])->name('profile.update');
            Route::get('/processos/{case}', [PortalClientPortalController::class, 'showCase'])->name('cases.show');
            Route::get('/documentos/{document}', [PortalClientPortalController::class, 'downloadDocument'])->name('documents.download');
        });
    });
    Route::get('/', [SiteController::class, 'home'])->name('site.home');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () use ($crud) {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::middleware('permission:analytics.view')
        ->get('/analytics', [AnalyticsController::class, 'index'])
        ->name('analytics.index');

    Route::middleware('permission:calendar.manage')
        ->prefix('calendar')
        ->name('calendar.')
        ->group(function (): void {
            Route::get('/', [CalendarController::class, 'index'])->name('index');
            Route::get('/events', [CalendarController::class, 'events'])->name('events');
            Route::get('/records', [CalendarController::class, 'records'])->name('records');
            Route::get('/create', [CalendarController::class, 'create'])->name('create');
            Route::post('/events', [CalendarController::class, 'store'])->name('store');
            Route::get('/events/{event}/edit', [CalendarController::class, 'edit'])->name('edit');
            Route::match(['put', 'patch'], '/events/{event}', [CalendarController::class, 'update'])->name('update');
            Route::patch('/events/{event}/move', [CalendarController::class, 'move'])->name('move');
            Route::delete('/events/{event}', [CalendarController::class, 'destroy'])->name('destroy');
        });

    Route::middleware('permission:preloader.manage')
        ->prefix('preloader')
        ->name('preloader.')
        ->group(function (): void {
            Route::get('/', [PreloaderController::class, 'index'])->name('index');
            Route::put('/', [PreloaderController::class, 'update'])->name('update');
        });

    Route::middleware('permission:settings.manage')
        ->prefix('system-settings')
        ->name('system-settings.')
        ->group(function (): void {
            Route::get('/', [SystemSettingsController::class, 'index'])->name('index');
            Route::put('/', [SystemSettingsController::class, 'update'])->name('update');
            Route::post('/seed-demo-data', [SystemSettingsController::class, 'seedDemoData'])->name('seed-demo-data');
        });

    Route::middleware('permission:settings.manage')
        ->prefix('auth-appearance')
        ->name('auth-appearance.')
        ->group(function (): void {
            Route::get('/', [AuthAppearanceController::class, 'index'])->name('index');
            Route::put('/', [AuthAppearanceController::class, 'update'])->name('update');
        });

    Route::middleware('permission:client-portal.manage')
        ->prefix('client-portal')
        ->name('client-portal.')
        ->group(function (): void {
            Route::get('/', [ClientPortalController::class, 'index'])->name('index');
            Route::put('/', [ClientPortalController::class, 'update'])->name('update');
        });

    Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'start'])
        ->middleware('permission:impersonate.users')
        ->name('users.impersonate');

    Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->middleware('permission:users.manage')
        ->name('users.toggle-active');

    Route::patch('/team-members/{record}/toggle-active', [TeamMemberController::class, 'toggleActive'])
        ->middleware('permission:team-members.manage')
        ->name('team-members.toggle-active');

    Route::patch('/testimonials/{record}/toggle-active', [TestimonialController::class, 'toggleActive'])
        ->middleware('permission:testimonials.manage')
        ->name('testimonials.toggle-active');

    Route::patch('/practice-areas/{record}/toggle-active', [PracticeAreaController::class, 'toggleActive'])
        ->middleware('permission:practice-areas.manage')
        ->name('practice-areas.toggle-active');

    Route::get('/contact-messages/notifications/feed', [AdminContactMessageController::class, 'notifications'])
        ->middleware('permission:contact-messages.manage')
        ->name('contact-messages.notifications');
    Route::patch('/contact-messages/{record}/mark-viewed', [AdminContactMessageController::class, 'markViewed'])
        ->middleware('permission:contact-messages.manage')
        ->name('contact-messages.mark-viewed');

    Route::middleware('role:Super Admin')
        ->prefix('system-files')
        ->name('system-files.')
        ->group(function (): void {
            Route::get('/confirm', [SystemFileController::class, 'showConfirmation'])->name('confirm');
            Route::post('/confirm', [SystemFileController::class, 'storeConfirmation'])->name('confirm.store');
            Route::get('/', [SystemFileController::class, 'index'])
                ->middleware('system-files.confirmed')
                ->name('index');
            Route::put('/{fileKey}', [SystemFileController::class, 'update'])->name('update');
            Route::post('/{fileKey}/restore', [SystemFileController::class, 'restore'])->name('restore');
        });

    $crud('pages', 'pages', PageController::class, 'pages.manage');
    $crud('page-sections', 'page-sections', PageSectionController::class, 'page-sections.manage');
    $crud('practice-areas', 'practice-areas', PracticeAreaController::class, 'practice-areas.manage');
    $crud('clients', 'clients', ClientController::class, 'clients.manage');
    $crud('legal-cases', 'legal-cases', LegalCaseController::class, 'legal-cases.manage');
    Route::middleware('permission:legal-cases.manage')
        ->post('legal-cases/{record}/sync-datajud', [LegalCaseController::class, 'syncDataJud'])
        ->name('legal-cases.sync-datajud');
    $crud('legal-case-updates', 'legal-case-updates', LegalCaseUpdateController::class, 'legal-case-updates.manage');
    $crud('legal-tasks', 'legal-tasks', LegalTaskController::class, 'legal-tasks.manage');
    $crud('legal-documents', 'legal-documents', LegalDocumentController::class, 'legal-documents.manage');
    $crud('team-members', 'team-members', TeamMemberController::class, 'team-members.manage');
    $crud('testimonials', 'testimonials', TestimonialController::class, 'testimonials.manage');
    $crud('contact-messages', 'contact-messages', AdminContactMessageController::class, 'contact-messages.manage');
    $crud('media-assets', 'media-assets', MediaAssetController::class, 'media-assets.manage');
    $crud('seo-metas', 'seo-metas', SeoMetaController::class, 'seo-metas.manage');
    $crud('users', 'users', UserController::class, 'users.manage');
    $crud('roles', 'roles', RoleController::class, 'roles.manage');
    $crud('permissions', 'permissions', PermissionController::class, 'permissions.manage');
    $crud('settings', 'settings', SettingController::class, 'settings.manage');

    Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation.index');
    Route::post('/documentation/complete-tour', [DocumentationController::class, 'completeTour'])->name('documentation.complete-tour');
    Route::post('/documentation/reset-tour', [DocumentationController::class, 'resetTour'])->name('documentation.reset-tour');
});

Route::middleware('auth')->group(function () {
    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    Route::get('/dashboard', function (): RedirectResponse {
        $user = request()->user();

        if ($user?->hasRole('Super Admin') || $user?->can('admin.access')) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('profile.edit');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['check.maintenance', 'track.visit'])
    ->get('/{slug}', [SiteController::class, 'show'])
    ->where('slug', '^(?!admin|instalar|login|logout|forgot-password|reset-password|register|dashboard|profile|verify-email|email|confirm-password|password|manifest\.webmanifest|sw\.js|offline|sitemap\.xml|robots\.txt|portal-cliente|up).*$')
    ->name('site.show');
