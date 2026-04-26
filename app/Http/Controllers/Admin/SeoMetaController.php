<?php

namespace App\Http\Controllers\Admin;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SeoMetaController extends AdminCrudController
{
    protected string $modelClass = SeoMeta::class;
    protected string $viewPath = 'seo-metas';
    protected string $module = 'seo_metas';
    protected string $singularLabel = 'SEO';
    protected string $pluralLabel = 'SEO Global';
    protected string $routeBase = 'admin.seo-metas';
    protected array $searchable = ['route_name', 'title', 'description', 'keywords'];
    protected string $defaultSort = 'updated_at';
    protected string $defaultDirection = 'desc';

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'route_name' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string'],
            'hashtags_text' => ['nullable', 'string'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string'],
            'canonical_url' => ['nullable', 'url'],
            'robots' => ['nullable', 'string', 'max:255'],
            'schema_type' => ['nullable', 'string', 'max:255'],
            'og_image' => ['nullable', 'image', 'max:4096'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        unset($validated['hashtags_text'], $validated['og_image']);
        $validated['hashtags'] = collect(explode(',', (string) $request->input('hashtags_text')))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
        $validated += $this->booleanData($request, ['noindex']);
        $validated['og_image_path'] = $this->storeMediaFile($request, 'og_image', 'seo', $record?->og_image_path);

        return $validated;
    }
}
