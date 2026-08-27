<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\SystemFileManagerService;
use Database\Seeders\PermissionsSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_installer_routes_are_hidden_in_production(): void
    {
        $originalEnvironment = app()->environment();
        app()->detectEnvironment(fn (): string => 'production');

        try {
            $this->get(route('install.index'))->assertNotFound();
            $this->post(route('install.store'))->assertNotFound();
        } finally {
            app()->detectEnvironment(fn (): string => $originalEnvironment);
        }
    }

    public function test_web_responses_receive_compatible_security_headers(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()')
            ->assertHeader('Content-Security-Policy-Report-Only');
    }

    public function test_testimonial_cards_do_not_render_a_decorative_glyph_over_the_rating(): void
    {
        $layout = file_get_contents(resource_path('views/site/layouts/app.blade.php'));

        $this->assertStringContainsString('.testimonial-card{position:relative}', $layout);
        $this->assertStringNotContainsString('.testimonial-card::before', $layout);
    }

    public function test_public_header_and_footer_use_the_dynamic_branding_logo(): void
    {
        $layout = file_get_contents(resource_path('views/site/layouts/app.blade.php'));

        $this->assertSame(2, substr_count($layout, 'src="{{ $branding[\'logo_url\'] }}"'));
        $this->assertSame(2, substr_count($layout, "@if(\$branding['logo_url'])"));
        $this->assertStringContainsString('>PUJANI</div>', $layout);
        $this->assertStringContainsString('>PUJANI ADVOGADOS</div>', $layout);
        $this->assertSame(2, substr_count($layout, 'Símbolo {{ $branding[\'brand_name\'] }}'));
    }

    public function test_documentation_is_integrated_and_legacy_public_url_is_canonicalized(): void
    {
        $this->get('/docs.php')
            ->assertStatus(301)
            ->assertRedirect('/admin/documentation#changelog');

        $rootHtaccess = file_get_contents(base_path('.htaccess'));
        $frontController = file_get_contents(public_path('index.php'));
        $documentation = file_get_contents(resource_path('views/admin/documentation/index.blade.php'));

        $this->assertStringContainsString('^public/docs\\.php$', $rootHtaccess);
        $this->assertStringContainsString('^public/?$ / [R=301,L]', $rootHtaccess);
        $this->assertStringContainsString('^public/?(.*)$ /$1', $rootHtaccess);
        $this->assertStringContainsString("str_starts_with(\$requestPath, '/public/')", $frontController);
        $this->assertStringContainsString("'/admin/documentation#changelog'", $frontController);
        $this->assertStringContainsString("@extends('admin.layouts.app')", $documentation);
        $this->assertStringContainsString('id="changelog"', $documentation);
        $this->assertStringNotContainsString('@tailwindcss/browser', $documentation);
        $this->assertStringNotContainsString('min-h-screen', $documentation);

        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');

        $this->actingAs($admin)
            ->get(route('admin.documentation.index'))
            ->assertOk()
            ->assertSee('Centro de conhecimento')
            ->assertSee('id="changelog"', false)
            ->assertSee('admin-docs-navigation', false);
    }

    public function test_admin_sidebar_uses_only_the_native_adminlte_treeview_controller(): void
    {
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');

        $html = $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $document = new DOMDocument();
        $previousState = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousState);
        $xpath = new DOMXPath($document);
        $treeviews = $xpath->query("//ul[contains(concat(' ', normalize-space(@class), ' '), ' admin-sidebar-menu ') and @data-lte-toggle='treeview']");

        $this->assertCount(1, $treeviews);
        $treeview = $treeviews->item(0);
        $this->assertSame('true', $treeview?->attributes?->getNamedItem('data-accordion')?->nodeValue);
        $this->assertSame('180', $treeview?->attributes?->getNamedItem('data-animation-speed')?->nodeValue);

        $parentLinks = $xpath->query("./li[contains(concat(' ', normalize-space(@class), ' '), ' nav-item ')]/a[contains(concat(' ', normalize-space(@class), ' '), ' admin-sidebar-parent-link ')]", $treeview);
        $this->assertGreaterThan(1, $parentLinks->count());

        $openGroups = 0;
        foreach ($parentLinks as $link) {
            $item = $link->parentNode;
            $isOpen = str_contains(' '.preg_replace('/\s+/', ' ', (string) $item?->attributes?->getNamedItem('class')?->nodeValue).' ', ' menu-open ');
            $this->assertSame($isOpen ? 'true' : 'false', $link->attributes?->getNamedItem('aria-expanded')?->nodeValue);

            $controls = $link->attributes?->getNamedItem('aria-controls')?->nodeValue;
            $this->assertNotEmpty($controls);
            $this->assertNotNull($document->getElementById($controls));
            $openGroups += $isOpen ? 1 : 0;
        }

        $this->assertSame(1, $openGroups);

        $script = file_get_contents(resource_path('js/admin.js'));
        $methodStart = strpos($script, '    bindSidebarTreeviewState() {');
        $methodEnd = strpos($script, 'initNotificationCenter()', $methodStart);
        $treeviewStateSynchronizer = substr($script, $methodStart, $methodEnd - $methodStart);

        $this->assertSame(1, substr_count($script, "import('admin-lte')"));
        $this->assertStringNotContainsString('bindSidebarAccordion', $script);
        $this->assertStringNotContainsString("addEventListener('click'", $treeviewStateSynchronizer);
        $this->assertStringNotContainsString("classList.add('menu-open'", $treeviewStateSynchronizer);
        $this->assertStringNotContainsString("classList.remove('menu-open'", $treeviewStateSynchronizer);
        $this->assertStringNotContainsString("classList.toggle('menu-open'", $treeviewStateSynchronizer);
        $this->assertStringNotContainsString('style.display', $treeviewStateSynchronizer);
        $this->assertStringContainsString('expanded.lte.treeview', $treeviewStateSynchronizer);
        $this->assertStringContainsString('collapsed.lte.treeview', $treeviewStateSynchronizer);
    }

    public function test_admin_content_uses_the_full_available_width(): void
    {
        $stylesheet = file_get_contents(resource_path('css/admin.css'));

        $this->assertStringContainsString('.admin-premium-shell .app-content > .container-fluid', $stylesheet);
        $this->assertStringNotContainsString('max-width: 1680px', $stylesheet);
    }

    public function test_service_worker_refreshes_server_rendered_content_without_http_cache(): void
    {
        $response = $this->get('/sw.js');

        $response->assertOk();

        $script = $response->getContent();
        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertMatchesRegularExpression(
            "/const CACHE_NAME = 'pujani-pwa-[^']+-[a-f0-9]{12}';/",
            $script
        );
        $this->assertStringContainsString('precacheFreshContent', $script);
        $this->assertStringContainsString("cache: 'reload'", $script);
        $this->assertStringContainsString("cache: 'no-store'", $script);
    }

    public function test_environment_file_is_not_available_through_system_file_manager(): void
    {
        $this->expectException(ValidationException::class);

        app(SystemFileManagerService::class)->describe('env');
    }
}
