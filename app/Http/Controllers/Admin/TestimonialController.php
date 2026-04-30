<?php

namespace App\Http\Controllers\Admin;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestimonialController extends AdminCrudController
{
    protected string $modelClass = Testimonial::class;
    protected string $viewPath = 'testimonials';
    protected string $module = 'testimonials';
    protected string $singularLabel = 'Depoimento';
    protected string $pluralLabel = 'Depoimentos';
    protected string $routeBase = 'admin.testimonials';
    protected array $searchable = ['author_name', 'author_role', 'company', 'content'];
    protected string $defaultSort = 'sort_order';
    protected string $defaultDirection = 'asc';

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'author_name' => ['required', 'string', 'max:255'],
            'author_role' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'image' => ['nullable', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        unset($validated['image']);
        $validated += $this->booleanData($request, ['is_featured', 'is_active']);
        $validated['image_path'] = $this->storeMediaFile($request, 'image', 'testimonials', $record?->image_path);

        return $validated;
    }

    public function toggleActive(Request $request, string $record): JsonResponse
    {
        /** @var Testimonial $entity */
        $entity = $this->resolveRecord($record);

        $entity->forceFill([
            'is_active' => ! $entity->is_active,
        ])->save();

        activity_log(
            $this->module,
            $entity->is_active ? 'activated' : 'deactivated',
            $entity,
            ['is_active' => $entity->is_active],
            $entity->is_active ? 'Depoimento ativado.' : 'Depoimento desativado.'
        );

        return response()->json([
            'message' => $entity->is_active ? 'Depoimento ativado com sucesso.' : 'Depoimento desativado com sucesso.',
            'tableTarget' => '#admin-resource-table',
        ]);
    }
}
