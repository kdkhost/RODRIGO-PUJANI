<?php

namespace Tests\Feature\Admin;

use App\Models\MediaAsset;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
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

    public function test_settings_permission_can_update_auth_appearance_texts(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create([
            'is_active' => true,
        ]);
        $admin->givePermissionTo(['admin.access', 'settings.manage']);

        $this->actingAs($admin)
            ->putJson(route('admin.auth-appearance.update'), [
                'panel_eyebrow' => 'Portal Seguro',
                'panel_title' => 'Acesso estrategico',
                'panel_description' => 'Conteudo personalizado pelo administrativo.',
                'metric_1_title' => 'Clientes',
                'metric_1_subtitle' => 'Atendimento',
                'metric_2_title' => 'Agenda',
                'metric_2_subtitle' => 'Integrada',
                'metric_3_title' => 'Portal',
                'metric_3_subtitle' => 'Instalavel',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Tela de login atualizada com sucesso.');

        $this->assertDatabaseHas('settings', [
            'key' => 'auth.metric_1_title',
            'value' => 'Clientes',
        ]);

        auth()->logout();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Portal Seguro')
            ->assertSee('Clientes')
            ->assertSee('Instalavel');
    }

    public function test_administrator_cannot_open_system_files_even_with_permission(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->assignRole('Administrador');
        $user->givePermissionTo('system-files.manage');

        $this->actingAs($user)
            ->get(route('admin.system-files.index'))
            ->assertForbidden();
    }

    public function test_super_admin_must_confirm_password_before_opening_system_files(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->assignRole('Super Admin');

        $this->actingAs($user)
            ->get(route('admin.system-files.index'))
            ->assertRedirect(route('admin.system-files.confirm'));
    }

    public function test_super_admin_can_confirm_password_and_open_system_files(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
            'password' => 'password',
        ]);
        $user->assignRole('Super Admin');

        $this->actingAs($user)
            ->post(route('admin.system-files.confirm.store'), [
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.system-files.index'));

        $this->actingAs($user)
            ->get(route('admin.system-files.index'))
            ->assertOk()
            ->assertSee('Arquivo .env')
            ->assertSee('Cofre técnico');
    }

    public function test_admin_user_can_update_user_without_validation_type_error(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['admin.access', 'users.manage']);
        $managedUser = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->putJson(route('admin.users.update', $managedUser), [
                'name' => 'Usuario Atualizado',
                'email' => $managedUser->email,
                'phone' => '(11) 99999-9999',
                'timezone' => 'America/Sao_Paulo',
                'is_active' => '1',
            ])
            ->assertOk()
            ->assertJsonStructure(['message', 'tableTarget']);

        $this->assertSame('Usuario Atualizado', $managedUser->refresh()->name);
    }

    public function test_media_asset_upload_creates_only_one_record(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['admin.access', 'media-assets.manage']);

        $this->actingAs($admin)
            ->postJson(route('admin.media-assets.store'), [
                'directory' => 'testing',
                'file' => UploadedFile::fake()->create('documento.pdf', 20, 'application/pdf'),
                'is_public' => '1',
            ])
            ->assertOk();

        $asset = MediaAsset::query()->firstOrFail();

        $this->assertSame(1, MediaAsset::query()->count());
        $this->assertStringStartsWith('uploads/testing/', $asset->path);
        $this->assertFileExists(public_path($asset->path));

        File::delete(public_path($asset->path));
        File::deleteDirectory(public_path('uploads/testing'));
    }
}
