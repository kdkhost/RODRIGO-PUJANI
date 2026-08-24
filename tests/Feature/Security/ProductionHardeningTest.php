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

    public function test_site_layout_uses_encoding_safe_testimonial_quote(): void
    {
        $layout = file_get_contents(resource_path('views/site/layouts/app.blade.php'));
        $mojibakeQuote = "\u{00E2}\u{20AC}\u{0153}";

        $this->assertStringContainsString("content:'\\201C'", $layout);
        $this->assertStringNotContainsString("content:'{$mojibakeQuote}'", $layout);
    }

    public function test_environment_file_is_not_available_through_system_file_manager(): void
    {
        $this->expectException(ValidationException::class);

        app(SystemFileManagerService::class)->describe('env');
    }
}
