<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends AdminCrudController
{
    protected string $modelClass = User::class;
    protected string $viewPath = 'users';
    protected string $module = 'users';
    protected string $singularLabel = 'Usuário';
    protected string $pluralLabel = 'Usuários';
    protected string $routeBase = 'admin.users';
    protected array $searchable = ['name', 'email', 'phone'];
    protected string $defaultSort = 'name';
    protected string $defaultDirection = 'asc';

    protected function indexQuery(Request $request): Builder
    {
        return User::query()->with('roles');
    }

    protected function formData(?Model $record = null): array
    {
        return [
            'roles' => Role::query()->orderBy('name')->get(),
        ];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        $passwordRule = $record?->exists ? ['nullable', 'confirmed', 'min:8'] : ['required', 'confirmed', 'min:8'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $this->uniqueRule('users', 'email', $record)],
            'phone' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'password' => $passwordRule,
            'role_names' => ['nullable', 'array'],
            'role_names.*' => ['string', 'exists:roles,name'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        unset($validated['avatar'], $validated['role_names']);
        $validated += $this->booleanData($request, ['is_active']);
        $validated['avatar_path'] = $this->storeMediaFile($request, 'avatar', 'avatars', $record?->avatar_path);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        return $validated;
    }

    protected function afterSave(Model $record, Request $request, bool $created): void
    {
        $record->syncRoles($request->input('role_names', []));
    }
}
