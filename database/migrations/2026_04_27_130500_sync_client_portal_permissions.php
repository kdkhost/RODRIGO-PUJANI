<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions() as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::query()->where('name', 'Super Admin')->first();
        $administrator = Role::query()->where('name', 'Administrador')->first();
        $editor = Role::query()->where('name', 'Editor')->first();
        $associatedLawyer = Role::query()->where('name', 'Advogado Associado')->first();

        $superAdmin?->givePermissionTo($this->permissions());
        $administrator?->givePermissionTo($this->permissions());
        $editor?->givePermissionTo(['client-portal.manage', 'legal-case-updates.manage']);
        $associatedLawyer?->givePermissionTo(['legal-case-updates.manage']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()
            ->whereIn('name', $this->permissions())
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissions(): array
    {
        return [
            'client-portal.manage',
            'legal-case-updates.manage',
        ];
    }
};
