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
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()')
            ->assertHeader('Content-Security-Policy-Report-Only');
    }

    public function test_environment_file_is_not_available_through_system_file_manager(): void
    {
        $this->expectException(ValidationException::class);

        app(SystemFileManagerService::class)->describe('env');
    }
}
