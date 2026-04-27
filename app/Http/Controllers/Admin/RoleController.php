<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends AdminCrudController
{
    protected string $modelClass = Role::class;
    protected string $viewPath = 'roles';
    protected string $module = 'roles';
    protected string $singularLabel = 'Função';
    protected string $pluralLabel = 'Funções';
    protected string $routeBase = 'admin.roles';
    protected array $searchable = ['name', 'guard_name'];
    protected string $defaultSort = 'name';
    protected string $defaultDirection = 'asc';

    protected function indexQuery(Request $request): Builder
    {
        return Role::query()->with('permissions');
    }

    protected function formData(?Model $record = null): array
    {
        return [
            'permissions' => Permission::query()->orderBy('name')->get(),
        ];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($record?->getKey())],
            'guard_name' => ['nullable', 'string', 'max:255'],
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string', 'exists:permissions,name'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        $validated['guard_name'] = $validated['guard_name'] ?? 'web';
        unset($validated['permission_names']);

        return $validated;
    }

    protected function afterSave(Model $record, Request $request, bool $created): void
    {
        $record->syncPermissions($request->input('permission_names', []));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function beforeDelete(Model $record): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
