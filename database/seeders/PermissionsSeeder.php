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
            'clients.manage',
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
            'legal-cases.manage',
            'legal-case-updates.manage',
            'legal-tasks.manage',
            'legal-documents.manage',
            'client-portal.manage',
            'preloader.manage',
            'impersonate.users',
        ];

        foreach ($permissions as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::query()->firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $administrator = Role::query()->firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $associatedLawyer = Role::query()->firstOrCreate(['name' => 'Advogado Associado', 'guard_name' => 'web']);
        $editor = Role::query()->firstOrCreate(['name' => 'Editor', 'guard_name' => 'web']);

        $superAdmin->syncPermissions(Permission::all());
        $administrator->syncPermissions(
            Permission::query()
                ->where('name', '!=', 'system-files.manage')
                ->pluck('name')
                ->all()
        );
        $editor->syncPermissions([
            'admin.access',
            'clients.manage',
            'pages.manage',
            'page-sections.manage',
            'practice-areas.manage',
            'team-members.manage',
            'testimonials.manage',
            'contact-messages.manage',
            'media-assets.manage',
            'seo-metas.manage',
            'calendar.manage',
            'legal-cases.manage',
            'legal-case-updates.manage',
            'legal-tasks.manage',
            'legal-documents.manage',
            'client-portal.manage',
        ]);

        $associatedLawyer->syncPermissions([
            'admin.access',
            'calendar.manage',
            'clients.manage',
            'legal-cases.manage',
            'legal-case-updates.manage',
            'legal-tasks.manage',
            'legal-documents.manage',
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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
