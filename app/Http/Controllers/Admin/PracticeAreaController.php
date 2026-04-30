<?php

namespace App\Http\Controllers\Admin;

use App\Models\PracticeArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PracticeAreaController extends AdminCrudController
{
    protected string $modelClass = PracticeArea::class;
    protected string $viewPath = 'practice-areas';
    protected string $module = 'practice_areas';
    protected string $singularLabel = 'Área';
    protected string $pluralLabel = 'Áreas de Atuação';
    protected string $routeBase = 'admin.practice-areas';
    protected array $searchable = ['title', 'slug', 'highlight'];
    protected string $defaultSort = 'sort_order';
    protected string $defaultDirection = 'asc';

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', $this->uniqueRule('practice_areas', 'slug', $record)],
            'icon' => ['nullable', 'string', 'max:255'],
            'highlight' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        unset($validated['image']);
        $validated += $this->booleanData($request, ['is_featured', 'is_active']);
        $validated['image_path'] = $this->storeMediaFile($request, 'image', 'practice-areas', $record?->image_path);

        return $validated;
    }

    public function toggleActive(Request $request, string $record): JsonResponse
    {
        /** @var PracticeArea $entity */
        $entity = $this->resolveRecord($record);

        $entity->forceFill([
            'is_active' => ! $entity->is_active,
        ])->save();

        activity_log(
            $this->module,
            $entity->is_active ? 'activated' : 'deactivated',
            $entity,
            ['is_active' => $entity->is_active],
            $entity->is_active ? 'Área ativada.' : 'Área desativada.'
        );

        return response()->json([
            'message' => $entity->is_active ? 'Área ativada com sucesso.' : 'Área desativada com sucesso.',
            'tableTarget' => '#admin-resource-table',
        ]);
    }
}
