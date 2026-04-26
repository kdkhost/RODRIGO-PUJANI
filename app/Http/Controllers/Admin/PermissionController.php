<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends AdminCrudController
{
    protected string $modelClass = Permission::class;
    protected string $viewPath = 'permissions';
    protected string $module = 'permissions';
    protected string $singularLabel = 'Permissão';
    protected string $pluralLabel = 'Permissões';
    protected string $routeBase = 'admin.permissions';
    protected array $searchable = ['name', 'guard_name'];
    protected string $defaultSort = 'name';
    protected string $defaultDirection = 'asc';

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($record?->getKey())],
            'guard_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        $validated['guard_name'] = $validated['guard_name'] ?? 'web';

        return $validated;
    }
}
