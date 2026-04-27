<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

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
            'clients.manage',
            'legal-cases.manage',
            'legal-tasks.manage',
            'legal-documents.manage',
            'preloader.manage',
            'impersonate.users',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::query()->firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $administrator = Role::query()->firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $editor = Role::query()->firstOrCreate(['name' => 'Editor', 'guard_name' => 'web']);
        $associatedLawyer = Role::query()->firstOrCreate(['name' => 'Advogado Associado', 'guard_name' => 'web']);

        $superAdmin->syncPermissions(Permission::query()->pluck('name')->all());

        $administrator->givePermissionTo(
            Permission::query()
                ->where('name', '!=', 'system-files.manage')
                ->pluck('name')
                ->all()
        );

        $systemFilesPermission = Permission::query()
            ->where('name', 'system-files.manage')
            ->where('guard_name', 'web')
            ->first();

        if ($systemFilesPermission) {
            $administrator->revokePermissionTo($systemFilesPermission);
        }

        $editor->givePermissionTo([
            'admin.access',
            'calendar.manage',
            'clients.manage',
            'legal-cases.manage',
            'legal-tasks.manage',
            'legal-documents.manage',
        ]);

        $associatedLawyer->syncPermissions([
            'admin.access',
            'calendar.manage',
            'clients.manage',
            'legal-cases.manage',
            'legal-tasks.manage',
            'legal-documents.manage',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()->where('name', 'Advogado Associado')->delete();
        Permission::query()->whereIn('name', [
            'clients.manage',
            'legal-cases.manage',
            'legal-tasks.manage',
            'legal-documents.manage',
        ])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
