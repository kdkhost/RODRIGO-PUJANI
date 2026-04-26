<?php

namespace App\Http\Controllers\Admin;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PageSectionController extends AdminCrudController
{
    protected string $modelClass = PageSection::class;
    protected string $viewPath = 'page-sections';
    protected string $module = 'page_sections';
    protected string $singularLabel = 'Seção';
    protected string $pluralLabel = 'Seções';
    protected string $routeBase = 'admin.page-sections';
    protected array $searchable = ['section_key', 'title'];
    protected string $defaultSort = 'sort_order';
    protected string $defaultDirection = 'asc';

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'page_id' => ['required', 'exists:pages,id'],
            'section_key' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'data_json' => ['nullable', 'string'],
            'style_variant' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function formData(?Model $record = null): array
    {
        return [
            'pages' => Page::query()->orderBy('title')->get(),
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        $validated += $this->booleanData($request, ['is_active']);
        $validated['data'] = blank($request->input('data_json'))
            ? null
            : json_decode((string) $request->input('data_json'), true);
        unset($validated['data_json']);

        return $validated;
    }
}
