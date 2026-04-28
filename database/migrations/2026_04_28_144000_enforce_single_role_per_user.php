<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('model_has_roles') || ! Schema::hasTable('roles')) {
            return;
        }

        $this->removeDuplicatedRoles();

        if (! Schema::hasIndex('model_has_roles', 'model_has_roles_single_role_per_model_unique')) {
            Schema::table('model_has_roles', function (Blueprint $table): void {
                $table->unique(['model_id', 'model_type'], 'model_has_roles_single_role_per_model_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('model_has_roles')) {
            return;
        }

        if (Schema::hasIndex('model_has_roles', 'model_has_roles_single_role_per_model_unique')) {
            Schema::table('model_has_roles', function (Blueprint $table): void {
                $table->dropUnique('model_has_roles_single_role_per_model_unique');
            });
        }
    }

    private function removeDuplicatedRoles(): void
    {
        $priority = [
            'Super Admin' => 10,
            'Administrador' => 20,
            'Advogado Associado' => 30,
            'Editor' => 40,
        ];

        DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select([
                'model_has_roles.role_id',
                'model_has_roles.model_type',
                'model_has_roles.model_id',
                'roles.name',
            ])
            ->orderBy('model_has_roles.model_type')
            ->orderBy('model_has_roles.model_id')
            ->get()
            ->groupBy(fn ($role): string => $role->model_type.'|'.$role->model_id)
            ->filter(fn ($roles): bool => $roles->count() > 1)
            ->each(function ($roles) use ($priority): void {
                $keep = $roles
                    ->sortBy(fn ($role): int => $priority[$role->name] ?? 1000)
                    ->first();

                DB::table('model_has_roles')
                    ->where('model_type', $keep->model_type)
                    ->where('model_id', $keep->model_id)
                    ->where('role_id', '!=', $keep->role_id)
                    ->delete();
            });
    }
};
