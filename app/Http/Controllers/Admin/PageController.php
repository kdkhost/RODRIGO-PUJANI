<?php

namespace App\Http\Controllers\Admin;

use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PageController extends AdminCrudController
{
    protected string $modelClass = Page::class;
    protected string $viewPath = 'pages';
    protected string $module = 'pages';
    protected string $singularLabel = 'Página';
    protected string $pluralLabel = 'Páginas';
    protected string $routeBase = 'admin.pages';
    protected array $searchable = ['title', 'slug', 'menu_title'];
    protected string $defaultSort = 'sort_order';
    protected string $defaultDirection = 'asc';

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', $this->uniqueRule('pages', 'slug', $record)],
            'menu_title' => ['nullable', 'string', 'max:255'],
            'template' => ['required', 'string', 'max:80'],
            'theme_variant' => ['nullable', 'string', 'max:80'],
            'status' => ['required', 'in:draft,published'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string'],
            'hero_cta_label' => ['nullable', 'string', 'max:255'],
            'hero_cta_url' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'seo_keywords' => ['nullable', 'string'],
            'seo_hashtags' => ['nullable', 'string'],
            'seo_og_title' => ['nullable', 'string', 'max:255'],
            'seo_og_description' => ['nullable', 'string'],
            'seo_canonical_url' => ['nullable', 'url'],
            'seo_robots' => ['nullable', 'string', 'max:255'],
            'seo_schema_type' => ['nullable', 'string', 'max:255'],
            'seo_og_image' => ['nullable', 'image', 'max:4096'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        unset(
            $validated['cover_image'],
            $validated['seo_title'],
            $validated['seo_description'],
            $validated['seo_keywords'],
            $validated['seo_hashtags'],
            $validated['seo_og_title'],
            $validated['seo_og_description'],
            $validated['seo_canonical_url'],
            $validated['seo_robots'],
            $validated['seo_schema_type'],
            $validated['seo_og_image'],
        );

        $validated += $this->booleanData($request, ['is_home', 'show_in_menu']);
        $validated['cover_path'] = $this->storeMediaFile($request, 'cover_image', 'pages', $record?->cover_path);

        return $validated;
    }

    protected function afterSave(Model $record, Request $request, bool $created): void
    {
        $this->syncSeoMeta($record, $request);
    }
}
