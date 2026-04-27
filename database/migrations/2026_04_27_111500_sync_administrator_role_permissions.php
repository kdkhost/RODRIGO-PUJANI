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

        $administrator = Role::query()->firstOrCreate([
            'name' => 'Administrador',
            'guard_name' => 'web',
        ]);

        $administrator->syncPermissions(
            Permission::query()
                ->where('name', '!=', 'system-files.manage')
                ->pluck('name')
                ->all()
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        Role::query()
            ->where('name', 'Administrador')
            ->where('guard_name', 'web')
            ->delete();
    }
};
