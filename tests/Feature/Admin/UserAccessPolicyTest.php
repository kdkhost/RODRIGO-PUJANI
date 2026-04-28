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
            'is_active' => true,
        ]);

        $this->actingAs($administrator)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('usuario@demo.test')
            ->assertDontSee('superadmin@demo.test');

        $this->actingAs($administrator)
            ->getJson(route('admin.users.edit', $superAdmin))
            ->assertNotFound();

        $this->actingAs($administrator)
            ->deleteJson(route('admin.users.destroy', $superAdmin))
            ->assertNotFound();

        $this->actingAs($administrator)
            ->deleteJson(route('admin.users.destroy', $managedUser))
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
        $this->assertDatabaseMissing('users', ['id' => $managedUser->id]);
    }

    public function test_administrator_can_impersonate_regular_users_but_not_super_admins(): void
    {
        $this->seed(PermissionsSeeder::class);

        $administrator = User::factory()->create(['is_active' => true]);
        $administrator->assignRole('Administrador');

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

    public function test_non_super_admin_cannot_assign_super_admin_role(): void
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
                'role_names' => ['Super Admin'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_names.0']);
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
