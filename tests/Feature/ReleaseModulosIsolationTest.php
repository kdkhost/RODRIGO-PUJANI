<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class ReleaseModulosIsolationTest extends TestCase
{
    public function test_orphaned_financial_module_provider_and_routes_remain_inactive(): void
    {
        $orphanedPaths = [
            'app/Modules/Modulos/Controllers/ModuloController.php',
            'app/Modules/Modulos/Models/Modulo.php',
            'app/Modules/Modulos/Routes/web.php',
            'app/Providers/ModuleServiceProvider.php',
            'resources/views/admin/modulos/index.blade.php',
            'resources/views/layouts/admin/sidebar.blade.php',
        ];

        foreach ($orphanedPaths as $path) {
            $this->assertFileDoesNotExist(base_path($path), $path);
        }

        $providers = require base_path('bootstrap/providers.php');

        $this->assertNotContains('App\\Providers\\ModuleServiceProvider', $providers);
        $this->assertArrayNotHasKey(
            'App\\Providers\\ModuleServiceProvider',
            app()->getLoadedProviders()
        );

        $moduleRoutes = collect(Route::getRoutes())
            ->map(fn ($route): ?string => $route->getName())
            ->filter(fn (?string $name): bool => str_starts_with((string) $name, 'admin.modulos.'));

        $this->assertCount(0, $moduleRoutes);
    }

    public function test_active_admin_layout_uses_only_the_central_sidebar(): void
    {
        $layout = file_get_contents(resource_path('views/admin/layouts/app.blade.php'));

        $this->assertIsString($layout);
        $this->assertStringContainsString("@include('admin.partials.sidebar')", $layout);
        $this->assertFileExists(resource_path('views/admin/partials/sidebar.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/layouts/admin/sidebar.blade.php'));

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(resource_path('views'), RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            $this->assertIsString($contents, $file->getPathname());
            $this->assertStringNotContainsString('layouts.admin.sidebar', $contents, $file->getPathname());
        }
    }

    public function test_electronic_signature_defaults_to_disabled(): void
    {
        $environment = file_get_contents(base_path('.env.example'));
        $installer = file_get_contents(app_path('Services/InstallerService.php'));
        $configuration = file_get_contents(config_path('signatures.php'));

        $this->assertFalse((bool) config('signatures.enabled'));
        $this->assertStringContainsString('ELECTRONIC_SIGNATURE_ENABLED=false', $environment);
        $this->assertStringNotContainsString('ELECTRONIC_SIGNATURE_ENABLED=true', $environment);
        $this->assertStringContainsString("'ELECTRONIC_SIGNATURE_ENABLED' => 'false'", $installer);
        $this->assertStringContainsString("env('ELECTRONIC_SIGNATURE_ENABLED', false)", $configuration);
    }
}
