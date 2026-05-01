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
        $actor = auth()->user();

        return [
            'roles' => Role::query()
                ->when(
                    ! $actor?->canAssignSuperAdminRole(),
                    fn (Builder $query) => $query->where('name', '!=', 'Super Admin')
                )
                ->orderBy('name')
                ->get(),
            'timezones' => timezone_identifiers_list(),
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
                ! $request->user()?->canAssignSuperAdminRole(),
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
            'timezone' => ['nullable', 'string', Rule::in(timezone_identifiers_list())],
            'pref_receive_internal_messages' => ['nullable', 'boolean'],
            'pref_receive_whatsapp_messages' => ['nullable', 'boolean'],
            'password' => $passwordRule,
            'role_name' => $roleRule,
            'role_names' => ['prohibited'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        unset($validated['avatar'], $validated['role_name'], $validated['role_names']);
        $validated += $this->booleanData($request, ['is_active', 'pref_receive_internal_messages', 'pref_receive_whatsapp_messages']);

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

    public function toggleActive(Request $request, User $user): JsonResponse
    {
        /** @var User $entity */
        $entity = User::query()
            ->visibleTo($request->user())
            ->with('roles')
            ->findOrFail($user->id);

        if (! $entity->canHaveStatusChangedBy($request->user())) {
            return response()->json([
                'message' => 'Este usuário é protegido e não pode ter o status alterado.',
            ], 403);
        }

        $entity->forceFill([
            'is_active' => ! $entity->is_active,
        ])->save();

        activity_log(
            $this->module,
            $entity->is_active ? 'activated' : 'deactivated',
            $entity,
            ['is_active' => $entity->is_active],
            $entity->is_active ? 'Usuário ativado.' : 'Usuário desativado.'
        );

        return response()->json([
            'message' => $entity->is_active ? 'Usuário ativado com sucesso.' : 'Usuário desativado com sucesso.',
            'tableTarget' => '#admin-resource-table',
        ]);
    }

    public function destroy(string $record): JsonResponse
    {
        $request = request();

        /** @var User $entity */
        $entity = $this->resolveRecord($record);

        if (! $entity->canBeDeletedBy($request->user())) {
            return response()->json([
                'message' => 'Este usuário é protegido e não pode ser excluído.',
            ], 403);
        }

        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ], [
            'password.required' => 'Informe a senha do administrador para excluir este usuário.',
            'password.current_password' => 'A senha informada não confere com o administrador autenticado.',
        ]);

        return parent::destroy($record);
    }

    public function create(): JsonResponse
    {
        abort_unless(
            in_array((int) auth()->id(), [User::PROTECTED_ROOT_USER_ID, User::PRIVILEGED_USER_MANAGER_ID], true),
            403,
            'Apenas os usuarios autorizados podem cadastrar novos usuarios.'
        );

        return parent::create();
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(
            in_array((int) auth()->id(), [User::PROTECTED_ROOT_USER_ID, User::PRIVILEGED_USER_MANAGER_ID], true),
            403,
            'Apenas os usuarios autorizados podem cadastrar novos usuarios.'
        );

        return parent::store($request);
    }

    protected function indexData(Request $request): array
    {
        return [
            'canCreate' => in_array((int) auth()->id(), [User::PROTECTED_ROOT_USER_ID, User::PRIVILEGED_USER_MANAGER_ID], true),
        ];
    }

    protected function resolveRecord(string $record): Model
    {
        return User::query()
            ->visibleTo(auth()->user())
            ->with('roles')
            ->findOrFail($record);
    }
}
