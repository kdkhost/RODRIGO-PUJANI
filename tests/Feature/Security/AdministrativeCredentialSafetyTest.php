<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\InstallerService;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class AdministrativeCredentialSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_seeder_does_not_replace_an_existing_administrator_password(): void
    {
        $originalPassword = Str::password(48);
        $replacementPassword = Str::password(48);
        $variables = [
            'APP_ADMIN_EMAIL' => 'security-regression@example.test',
            'APP_ADMIN_NAME' => 'Security Regression',
            'APP_ADMIN_PASSWORD' => $replacementPassword,
        ];
        $previous = $this->setEnvironmentVariables($variables);

        try {
            $admin = User::factory()->create([
                'email' => $variables['APP_ADMIN_EMAIL'],
                'password' => Hash::make($originalPassword),
                'is_active' => true,
            ]);

            $this->seed(PermissionsSeeder::class);
            $admin->refresh();

            $this->assertTrue(Hash::check($originalPassword, $admin->password));
            $this->assertFalse(Hash::check($variables['APP_ADMIN_PASSWORD'], $admin->password));
            $this->assertTrue($admin->hasRole('Super Admin'));
        } finally {
            $this->restoreEnvironmentVariables($previous);
        }
    }

    public function test_console_installer_refuses_to_run_without_an_explicit_admin_password(): void
    {
        $previous = $this->setEnvironmentVariables(['APP_ADMIN_PASSWORD' => null]);
        $installer = Mockery::mock(InstallerService::class);
        $installer->shouldNotReceive('install');
        $this->app->instance(InstallerService::class, $installer);

        try {
            $this->assertSame(1, Artisan::call('system:install'));
        } finally {
            $this->restoreEnvironmentVariables($previous);
        }
    }

    public function test_installer_erases_the_bootstrap_password_after_admin_creation(): void
    {
        $installer = file_get_contents(app_path('Services/InstallerService.php'));
        $consoleRoutes = file_get_contents(base_path('routes/console.php'));
        $seeder = file_get_contents(database_path('seeders/PermissionsSeeder.php'));

        $this->assertStringContainsString("'APP_ADMIN_PASSWORD' => ''", $installer);
        $this->assertStringContainsString("setEnvironmentValue('APP_ADMIN_PASSWORD', '')", $installer);
        $this->assertStringContainsString("\$adminPassword = env('APP_ADMIN_PASSWORD');", $consoleRoutes);
        $this->assertStringNotContainsString("env('APP_ADMIN_PASSWORD',", $consoleRoutes);
        $this->assertStringNotContainsString('if (filled($configuredPassword))', $seeder);
    }

    private function setEnvironmentVariables(array $variables): array
    {
        $repository = Env::getRepository();
        $previous = [];

        foreach ($variables as $key => $value) {
            $previous[$key] = Env::get($key);

            if ($value === null) {
                $repository->clear($key);
            } else {
                $repository->set($key, $value);
            }
        }

        return $previous;
    }

    private function restoreEnvironmentVariables(array $variables): void
    {
        $repository = Env::getRepository();

        foreach ($variables as $key => $value) {
            if ($value === null) {
                $repository->clear($key);
            } else {
                $repository->set($key, $value);
            }
        }
    }
}
