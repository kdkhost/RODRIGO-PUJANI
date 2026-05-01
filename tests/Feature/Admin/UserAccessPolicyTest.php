<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_cannot_see_or_manage_super_admin_users(): void
    {
        $this->seed(PermissionsSeeder::class);

        $superAdmin = User::factory()->create([
            'name' => 'Super Admin Protegido',
            'email' => 'superadmin@demo.test',
            'is_active' => true,
        ]);
        $superAdmin->assignRole('Super Admin');

        $administrator = User::factory()->create(['is_active' => true]);
        $administrator->assignRole('Administrador');

        $managedUser = User::factory()->create([
            'name' => 'Usuario Gerenciado',
            'email' => 'usuario@demo.test',
            'avatar_path' => 'uploads/avatars/usuario-demo.webp',
            'is_active' => true,
        ]);

        $this->actingAs($administrator)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('usuario@demo.test')
            ->assertSee('admin-user-list-avatar', false)
            ->assertSee('uploads/avatars/usuario-demo.webp', false)
            ->assertDontSee('superadmin@demo.test');

        $this->actingAs($administrator)
            ->getJson(route('admin.users.edit', $superAdmin))
            ->assertNotFound();

        $this->actingAs($administrator)
            ->deleteJson(route('admin.users.destroy', $superAdmin))
            ->assertNotFound();

        $this->actingAs($administrator)
            ->deleteJson(route('admin.users.destroy', $managedUser))
            ->assertForbidden();

        $this->actingAs($administrator)
            ->deleteJson(route('admin.users.destroy', $managedUser), [
                'password' => 'senha-incorreta',
            ])
            ->assertForbidden();

        $this->actingAs($administrator)
            ->deleteJson(route('admin.users.destroy', $managedUser), [
                'password' => 'password',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
        $this->assertDatabaseHas('users', ['id' => $managedUser->id]);
    }

    public function test_administrator_can_impersonate_regular_users_but_not_super_admins(): void
    {
        $this->seed(PermissionsSeeder::class);

        $administrator = User::query()->find(User::PRIVILEGED_USER_MANAGER_ID)
            ?? User::factory()->create([
                'id' => User::PRIVILEGED_USER_MANAGER_ID,
                'is_active' => true,
            ]);
        $administrator->forceFill(['is_active' => true])->save();
        $administrator->syncRoles(['Administrador']);
        $administrator->givePermissionTo(['admin.access', 'users.manage']);

        $regularUser = User::factory()->create(['is_active' => true]);
        $regularUser->assignRole('Advogado Associado');

        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($administrator)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.impersonate', $superAdmin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($administrator);

        $this->actingAs($administrator)
            ->post(route('admin.users.impersonate', $regularUser))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($regularUser);
    }

    public function test_associated_lawyer_cannot_open_user_management(): void
    {
        $this->seed(PermissionsSeeder::class);

        $lawyer = User::factory()->create(['is_active' => true]);
        $lawyer->assignRole('Advogado Associado');

        $this->actingAs($lawyer)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_non_root_user_cannot_create_super_admin_account(): void
    {
        $this->seed(PermissionsSeeder::class);

        $administrator = User::factory()->create(['is_active' => true]);
        $administrator->assignRole('Administrador');

        $this->actingAs($administrator)
            ->postJson(route('admin.users.store'), [
                'name' => 'Tentativa Elevacao',
                'email' => 'elevacao@demo.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_active' => '1',
                'role_name' => 'Super Admin',
            ])
            ->assertForbidden();
    }

    public function test_user_form_uses_single_role_select(): void
    {
        $this->seed(PermissionsSeeder::class);

        $superAdmin = User::query()->find(User::PROTECTED_ROOT_USER_ID)
            ?? User::factory()->create([
                'id' => User::PROTECTED_ROOT_USER_ID,
                'is_active' => true,
            ]);
        $superAdmin->forceFill(['is_active' => true])->save();
        $superAdmin->syncRoles(['Super Admin']);

        $response = $this->actingAs($superAdmin)
            ->getJson(route('admin.users.create'))
            ->assertOk();

        $html = $response->json('html');

        $this->assertStringContainsString('name="role_name"', $html);
        $this->assertStringNotContainsString('name="role_names[]"', $html);
        $this->assertStringNotContainsString('multiple', $html);
    }

    public function test_user_save_keeps_only_one_role(): void
    {
        $this->seed(PermissionsSeeder::class);

        $superAdmin = User::query()->find(User::PROTECTED_ROOT_USER_ID)
            ?? User::factory()->create([
                'id' => User::PROTECTED_ROOT_USER_ID,
                'is_active' => true,
            ]);
        $superAdmin->forceFill(['is_active' => true])->save();
        $superAdmin->syncRoles(['Super Admin']);

        $this->actingAs($superAdmin)
            ->postJson(route('admin.users.store'), [
                'name' => 'Usuario de Papel Unico',
                'email' => 'papel.unico@demo.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_active' => '1',
                'role_name' => 'Editor',
            ])
            ->assertOk();

        $user = User::query()->where('email', 'papel.unico@demo.test')->firstOrFail();

        $this->assertSame(['Editor'], $user->getRoleNames()->all());

        $this->actingAs($superAdmin)
            ->putJson(route('admin.users.update', $user), [
                'name' => 'Usuario de Papel Unico',
                'email' => 'papel.unico@demo.test',
                'is_active' => '1',
                'role_name' => 'Administrador',
            ])
            ->assertOk();

        $this->assertSame(['Administrador'], $user->refresh()->getRoleNames()->all());
    }

    public function test_administrator_can_toggle_regular_user_status(): void
    {
        $this->seed(PermissionsSeeder::class);

        $administrator = User::factory()->create(['is_active' => true]);
        $administrator->assignRole('Administrador');

        $regularUser = User::factory()->create(['is_active' => true]);
        $regularUser->assignRole('Advogado Associado');

        $this->actingAs($administrator)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('data-toggle-url', false)
            ->assertSee(route('admin.users.toggle-active', $regularUser), false);

        $this->actingAs($administrator)
            ->patchJson(route('admin.users.toggle-active', $regularUser))
            ->assertOk()
            ->assertJsonPath('tableTarget', '#admin-resource-table');

        $this->assertFalse($regularUser->refresh()->is_active);

        $this->actingAs($administrator)
            ->patchJson(route('admin.users.toggle-active', $regularUser))
            ->assertOk();

        $this->assertTrue($regularUser->refresh()->is_active);
    }

    public function test_protected_users_cannot_have_status_changed(): void
    {
        $this->seed(PermissionsSeeder::class);

        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole('Super Admin');

        $administrator = User::factory()->create(['is_active' => true]);
        $administrator->assignRole('Administrador');

        $this->actingAs($administrator)
            ->patchJson(route('admin.users.toggle-active', $superAdmin))
            ->assertNotFound();

        $this->actingAs($administrator)
            ->patchJson(route('admin.users.toggle-active', $administrator))
            ->assertForbidden();
    }

    public function test_super_admin_cannot_delete_own_profile(): void
    {
        $this->seed(PermissionsSeeder::class);

        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($superAdmin);
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }
}
