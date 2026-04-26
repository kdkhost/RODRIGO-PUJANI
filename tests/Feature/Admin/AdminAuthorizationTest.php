<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_module_permission_cannot_open_settings(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->givePermissionTo(['admin.access', 'pages.manage']);

        $response = $this->actingAs($user)->get(route('admin.settings.index'));

        $response->assertForbidden();
    }

    public function test_user_with_settings_permission_can_open_settings(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->givePermissionTo(['admin.access', 'settings.manage']);

        $response = $this->actingAs($user)->get(route('admin.settings.index'));

        $response->assertOk();
    }

    public function test_system_files_route_requires_specific_permission(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->givePermissionTo(['admin.access']);

        $this->actingAs($user)
            ->get(route('admin.system-files.index'))
            ->assertForbidden();

        $user->givePermissionTo('system-files.manage');

        $this->actingAs($user)
            ->get(route('admin.system-files.index'))
            ->assertOk();
    }
}
