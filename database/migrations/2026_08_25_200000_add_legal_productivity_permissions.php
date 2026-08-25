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
        foreach ($this->permissions() as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::query()->where('name', 'Super Admin')->first()?->givePermissionTo($this->permissions());
        Role::query()->where('name', 'Administrador')->first()?->givePermissionTo($this->permissions());
        Role::query()->where('name', 'Editor')->first()?->givePermissionTo([
            'legal-workspace.view',
            'djen-publications.view',
            'djen-publications.review',
            'djen-monitors.manage',
            'djen-monitors.sync',
            'legal-deadlines.reminders',
            'google-calendar.manage',
            'legal-document-templates.view',
            'legal-document-templates.manage',
            'legal-document-templates.generate',
            'legal-ai.generate',
            'legal-ai.review',
            'legal-ai.approve',
            'hearing-transcriptions.manage',
            'hearing-transcriptions.approve',
            'financial.manage',
        ]);
        Role::query()->where('name', 'Advogado Associado')->first()?->givePermissionTo([
            'legal-workspace.view',
            'djen-publications.view',
            'djen-publications.review',
            'djen-monitors.sync',
            'legal-deadlines.reminders',
            'google-calendar.manage',
            'legal-document-templates.view',
            'legal-document-templates.generate',
            'legal-ai.generate',
            'legal-ai.review',
            'hearing-transcriptions.manage',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::query()->whereIn('name', $this->permissions())->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissions(): array
    {
        return [
            'legal-workspace.view',
            'djen-publications.view',
            'djen-publications.review',
            'djen-monitors.manage',
            'djen-monitors.sync',
            'legal-deadlines.reminders',
            'google-calendar.manage',
            'legal-document-templates.view',
            'legal-document-templates.manage',
            'legal-document-templates.generate',
            'legal-ai.configure',
            'legal-ai.generate',
            'legal-ai.review',
            'legal-ai.approve',
            'legal-ai.publish',
            'hearing-transcriptions.manage',
            'hearing-transcriptions.approve',
            'financial.manage',
        ];
    }
};
