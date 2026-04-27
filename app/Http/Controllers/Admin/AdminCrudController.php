<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PublicUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\View\View;

abstract class AdminCrudController extends Controller
{
    protected string $modelClass;
    protected string $viewPath;
    protected string $module;
    protected string $singularLabel;
    protected string $pluralLabel;
    protected string $routeBase;
    protected array $searchable = [];
    protected string $defaultSort = 'id';
    protected string $defaultDirection = 'desc';
    protected int $perPage = 10;

    public function index(Request $request): View|JsonResponse
    {
        $query = $this->indexQuery($request);
        $search = trim((string) $request->string('search'));

        if ($search !== '' && $this->searchable !== []) {
            $query->where(function (Builder $builder) use ($search): void {
                foreach ($this->searchable as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $builder->{$method}($column, 'like', "%{$search}%");
                }
            });
        }

        $sort = $request->string('sort')->toString() ?: $this->defaultSort;
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : $this->defaultDirection;

        $items = $query
            ->orderBy($sort, $direction)
            ->paginate($request->integer('per_page', $this->perPage))
            ->withQueryString();

        $payload = [
            'items' => $items,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'routeBase' => $this->routeBase,
            'singularLabel' => $this->singularLabel,
            'pluralLabel' => $this->pluralLabel,
            'tableView' => $this->tableView(),
        ] + $this->indexData($request);

        if ($request->ajax()) {
            return response()->json([
                'html' => view($this->tableView(), $payload)->render(),
            ]);
        }

        return view($this->indexView(), $payload + [
            'pageTitle' => $this->pluralLabel,
            'tableUrl' => route($this->routeBase.'.index'),
            'createUrl' => route($this->routeBase.'.create'),
            'toolbarId' => 'admin-toolbar-'.$this->viewPath,
            'tableId' => 'admin-resource-table',
        ]);
    }

    public function create(): JsonResponse
    {
        return $this->formResponse($this->newModel(), 'Cadastrar '.$this->singularLabel);
    }

    public function store(Request $request): JsonResponse
    {
        $record = $this->newModel();
        $data = $this->mutateData($request->validate($this->rules($request, $record)), $request, $record);

        $record->fill($data);
        $record->save();

        $this->afterSave($record, $request, true);
        $this->clearSiteCaches();
        activity_log($this->module, 'created', $record, $record->toArray(), $this->singularLabel.' criada.');

        return response()->json([
            'message' => 'Cadastro realizado com sucesso.',
            'tableTarget' => '#admin-resource-table',
        ]);
    }

    public function edit(string $record): JsonResponse
    {
        return $this->formResponse($this->resolveRecord($record), 'Editar '.$this->singularLabel);
    }

    public function update(Request $request, string $record): JsonResponse
    {
        $entity = $this->resolveRecord($record);
        $data = $this->mutateData($request->validate($this->rules($request, $entity)), $request, $entity);

        $entity->fill($data);
        $entity->save();

        $this->afterSave($entity, $request, false);
        $this->clearSiteCaches();
        activity_log($this->module, 'updated', $entity, $entity->toArray(), $this->singularLabel.' atualizada.');

        return response()->json([
            'message' => 'Registro atualizado com sucesso.',
            'tableTarget' => '#admin-resource-table',
        ]);
    }

    public function destroy(string $record): JsonResponse
    {
        $entity = $this->resolveRecord($record);

        $this->beforeDelete($entity);
        $entity->delete();

        $this->clearSiteCaches();
        activity_log($this->module, 'deleted', $entity, [], $this->singularLabel.' removida.');

        return response()->json([
            'message' => 'Registro removido com sucesso.',
        ]);
    }

    protected function indexQuery(Request $request): Builder
    {
        return $this->newQuery();
    }

    protected function indexData(Request $request): array
    {
        return [];
    }

    protected function formData(?Model $record = null): array
    {
        return [];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        return $validated;
    }

    protected function afterSave(Model $record, Request $request, bool $created): void
    {
    }

    protected function beforeDelete(Model $record): void
    {
    }

    protected function formResponse(Model $record, string $title): JsonResponse
    {
        return response()->json([
            'title' => $title,
            'html' => view($this->formView(), [
                'record' => $record,
                'routeBase' => $this->routeBase,
                'singularLabel' => $this->singularLabel,
            ] + $this->formData($record))->render(),
        ]);
    }

    protected function resolveRecord(string $record): Model
    {
        return $this->newQuery()->findOrFail($record);
    }

    protected function newQuery(): Builder
    {
        return $this->modelClass::query();
    }

    protected function newModel(): Model
    {
        $class = $this->modelClass;

        return new $class();
    }

    protected function tableView(): string
    {
        return 'admin.'.$this->viewPath.'._table';
    }

    protected function formView(): string
    {
        return 'admin.'.$this->viewPath.'._form';
    }

    protected function indexView(): string
    {
        return 'admin.shared.index';
    }

    protected function booleanData(Request $request, array $fields): array
    {
        $data = [];

        foreach ($fields as $field) {
            $data[$field] = $request->boolean($field);
        }

        return $data;
    }

    protected function storeMediaFile(
        Request $request,
        string $field,
        string $directory,
        ?string $currentPath = null,
        bool $registerAsset = true,
    ): ?string
    {
        if (! $request->hasFile($field)) {
            return $currentPath;
        }

        return PublicUpload::store($request->file($field), $directory, $currentPath, auth()->id(), $registerAsset);
    }

    protected function deleteMediaFile(?string $path): void
    {
        PublicUpload::delete($path);
    }

    protected function syncSeoMeta(Model $record, Request $request): void
    {
        if (! method_exists($record, 'seoMeta')) {
            return;
        }

        $seo = $record->seoMeta()->firstOrNew();
        $seo->fill([
            'title' => $request->input('seo_title'),
            'description' => $request->input('seo_description'),
            'keywords' => $request->input('seo_keywords'),
            'hashtags' => collect(explode(',', (string) $request->input('seo_hashtags')))
                ->map(fn ($item) => trim($item))
                ->filter()
                ->values()
                ->all(),
            'og_title' => $request->input('seo_og_title'),
            'og_description' => $request->input('seo_og_description'),
            'canonical_url' => $request->input('seo_canonical_url'),
            'robots' => $request->input('seo_robots', 'index,follow'),
            'schema_type' => $request->input('seo_schema_type', 'WebPage'),
            'noindex' => $request->boolean('seo_noindex'),
        ]);

        $seo->og_image_path = $this->storeMediaFile($request, 'seo_og_image', 'seo', $seo->og_image_path);
        $seo->save();
    }

    protected function clearSiteCaches(): void
    {
        foreach ([
            'site_settings.all',
            'site_settings.all.v2',
            'site_settings.map',
            'site_settings.map.v2',
            'site_pages.menu',
            'site_pages.menu.v2',
            'site_pages.public',
            'site_pages.public.v2',
            'system_maintenance.settings',
            'preloader.settings.v1',
        ] as $key) {
            Cache::forget($key);
        }
    }

    protected function uniqueRule(string $table, string $column, ?Model $record = null): Unique
    {
        return Rule::unique($table, $column)->ignore($record?->getKey());
    }
}
