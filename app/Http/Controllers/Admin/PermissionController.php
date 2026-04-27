<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

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

    protected function indexQuery(Request $request): Builder
    {
        return Permission::query();
    }

    protected function indexData(Request $request): array
    {
        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['name', 'guard_name']);

        $modules = $permissions
            ->groupBy(fn (Permission $permission): string => $this->permissionParts($permission->name)['module_key']);

        $actions = $permissions
            ->groupBy(fn (Permission $permission): string => $this->permissionParts($permission->name)['action_key']);

        return [
            'permissionDictionaries' => [
                'modules' => $this->moduleLabels(),
                'actions' => $this->actionLabels(),
            ],
            'permissionStats' => [
                'total' => $permissions->count(),
                'modules' => $modules->count(),
                'guards' => $permissions->pluck('guard_name')->filter()->unique()->count(),
                'sensitive' => $permissions->filter(fn (Permission $permission): bool => Str::contains($permission->name, [
                    'delete',
                    'destroy',
                    'impersonate',
                    'permissions',
                    'roles',
                    'settings',
                    'system-files',
                    'users',
                ]))->count(),
            ],
            'moduleHighlights' => $modules
                ->map(fn (Collection $items, string $module): array => [
                    'key' => $module,
                    'label' => $this->humanizeModule($module),
                    'count' => $items->count(),
                ])
                ->sortByDesc('count')
                ->take(6)
                ->values(),
            'actionHighlights' => $actions
                ->map(fn (Collection $items, string $action): array => [
                    'key' => $action,
                    'label' => $this->humanizeAction($action),
                    'count' => $items->count(),
                ])
                ->sortByDesc('count')
                ->take(6)
                ->values(),
        ];
    }

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

    protected function afterSave(Model $record, Request $request, bool $created): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function beforeDelete(Model $record): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function indexView(): string
    {
        return 'admin.permissions.index';
    }

    private function permissionParts(string $permission): array
    {
        [$module, $action] = array_pad(explode('.', $permission, 2), 2, 'manage');

        return [
            'module_key' => $module,
            'action_key' => $action,
        ];
    }

    private function moduleLabels(): array
    {
        return [
            'admin' => 'Administração',
            'analytics' => 'Análises',
            'calendar' => 'Agenda',
            'clients' => 'Clientes',
            'contact-messages' => 'Mensagens',
            'legal-cases' => 'Processos',
            'legal-documents' => 'Documentos jurídicos',
            'legal-tasks' => 'Tarefas e prazos',
            'media-assets' => 'Mídias',
            'page-sections' => 'Seções',
            'pages' => 'Páginas',
            'permissions' => 'Permissões',
            'practice-areas' => 'Áreas de atuação',
            'preloader' => 'Pré-carregador',
            'roles' => 'Funções',
            'seo-metas' => 'SEO',
            'settings' => 'Configurações',
            'system-files' => 'Arquivos do sistema',
            'team-members' => 'Equipe',
            'testimonials' => 'Depoimentos',
            'users' => 'Usuários',
        ];
    }

    private function actionLabels(): array
    {
        return [
            'access' => 'Acessar',
            'create' => 'Criar',
            'delete' => 'Excluir',
            'edit' => 'Editar',
            'impersonate' => 'Assumir acesso',
            'index' => 'Listar',
            'manage' => 'Gerenciar',
            'move' => 'Mover',
            'restore' => 'Restaurar',
            'store' => 'Salvar',
            'update' => 'Atualizar',
            'view' => 'Visualizar',
        ];
    }

    private function humanizeModule(string $module): string
    {
        return $this->moduleLabels()[$module]
            ?? Str::of($module)->replace(['-', '.'], ' ')->headline()->toString();
    }

    private function humanizeAction(string $action): string
    {
        return $this->actionLabels()[$action]
            ?? Str::of($action)->replace(['-', '.'], ' ')->headline()->toString();
    }
}
