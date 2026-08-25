<?php

namespace Tests\Feature\Security;

use App\Services\SystemFileManagerService;
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
