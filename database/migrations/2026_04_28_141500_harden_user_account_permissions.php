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

        $superAdmin = Role::query()->firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $administrator = Role::query()->firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $associatedLawyer = Role::query()->firstOrCreate(['name' => 'Advogado Associado', 'guard_name' => 'web']);

        $superAdmin->syncPermissions(Permission::query()->pluck('name')->all());

        $administrator->syncPermissions(
            Permission::query()
                ->where('name', '!=', 'system-files.manage')
                ->pluck('name')
                ->all()
        );

        $associatedLawyer->syncPermissions([
            'admin.access',
            'calendar.manage',
            'clients.manage',
            'legal-cases.manage',
            'legal-case-updates.manage',
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
    }
};
