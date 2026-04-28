<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends AdminCrudController
{
    protected string $modelClass = User::class;
    protected string $viewPath = 'users';
    protected string $module = 'users';
    protected string $singularLabel = 'Usuário';
    protected string $pluralLabel = 'Usuários';
    protected string $routeBase = 'admin.users';
    protected array $searchable = ['name', 'email', 'phone', 'document_number', 'address_city', 'address_state'];
    protected string $defaultSort = 'name';
    protected string $defaultDirection = 'asc';

    protected function indexQuery(Request $request): Builder
    {
        return User::query()
            ->visibleTo($request->user())
            ->with('roles');
    }

    protected function formData(?Model $record = null): array
    {
        return [
            'roles' => Role::query()
                ->when(
                    ! auth()->user()?->isSuperAdmin(),
                    fn (Builder $query) => $query->where('name', '!=', 'Super Admin')
                )
                ->orderBy('name')
                ->get(),
        ];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        $passwordRule = $record?->exists ? ['nullable', 'confirmed', 'min:8'] : ['required', 'confirmed', 'min:8'];
        $roleRule = [
            $record?->exists ? 'nullable' : 'required',
            'string',
            'exists:roles,name',
            Rule::when(
                ! $request->user()?->isSuperAdmin(),
                Rule::notIn(['Super Admin'])
            ),
        ];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $this->uniqueRule('users', 'email', $record)],
            'phone' => ['nullable', 'string', 'max:30'],
            'document_number' => ['nullable', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'address_zip' => ['nullable', 'string', 'max:12'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:20'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'address_district' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state' => ['nullable', 'string', 'size:2'],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'password' => $passwordRule,
            'role_name' => $roleRule,
            'role_names' => ['prohibited'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        unset($validated['avatar'], $validated['role_name'], $validated['role_names']);
        $validated += $this->booleanData($request, ['is_active']);

        if ($record instanceof User && $record->exists && $record->isSuperAdmin()) {
            $validated['is_active'] = true;
        }

        $validated['address_state'] = filled($validated['address_state'] ?? null)
            ? strtoupper((string) $validated['address_state'])
            : null;
        $validated['avatar_path'] = $this->storeMediaFile($request, 'avatar', 'avatars', $record?->avatar_path);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        return $validated;
    }

    protected function afterSave(Model $record, Request $request, bool $created): void
    {
        $role = $request->input('role_name');

        if ($record instanceof User && $record->isSuperAdmin()) {
            $record->syncRoles(['Super Admin']);

            return;
        }

        if (blank($role)) {
            return;
        }

        $record->syncRoles([(string) $role]);
    }

    public function destroy(string $record): JsonResponse
    {
        /** @var User $entity */
        $entity = $this->resolveRecord($record);

        if (! $entity->canBeDeletedBy(auth()->user())) {
            return response()->json([
                'message' => 'Este usuário é protegido e não pode ser excluído.',
            ], 403);
        }

        return parent::destroy($record);
    }

    protected function resolveRecord(string $record): Model
    {
        return User::query()
            ->visibleTo(auth()->user())
            ->with('roles')
            ->findOrFail($record);
    }
}
