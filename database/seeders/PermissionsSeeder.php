<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'admin.access',
            'pages.manage',
            'page-sections.manage',
            'practice-areas.manage',
            'team-members.manage',
            'testimonials.manage',
            'contact-messages.manage',
            'media-assets.manage',
            'seo-metas.manage',
            'users.manage',
            'roles.manage',
            'permissions.manage',
            'settings.manage',
            'analytics.view',
            'system-files.manage',
            'calendar.manage',
            'preloader.manage',
            'impersonate.users',
        ];

        foreach ($permissions as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $superAdmin = Role::query()->firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $editor = Role::query()->firstOrCreate(['name' => 'Editor', 'guard_name' => 'web']);

        $superAdmin->syncPermissions(Permission::all());
        $editor->syncPermissions([
            'admin.access',
            'pages.manage',
            'page-sections.manage',
            'practice-areas.manage',
            'team-members.manage',
            'testimonials.manage',
            'contact-messages.manage',
            'media-assets.manage',
            'seo-metas.manage',
            'calendar.manage',
        ]);

        $admin = User::query()->updateOrCreate(
            ['email' => env('APP_ADMIN_EMAIL', 'admin@pujani.adv.br')],
            [
                'name' => env('APP_ADMIN_NAME', 'Administrador Pujani'),
                'password' => Hash::make(env('APP_ADMIN_PASSWORD', 'Admin@12345')),
                'timezone' => 'America/Sao_Paulo',
                'is_active' => true,
            ]
        );

        $admin->syncRoles([$superAdmin->name]);
    }
}
