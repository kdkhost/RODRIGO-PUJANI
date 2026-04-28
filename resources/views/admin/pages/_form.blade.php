@php
    $isEdit = $record->exists;
@endphp
<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Título</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $record->title) }}">
            <div class="invalid-feedback d-block" data-error-for="title"></div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control" value="{{ old('slug', $record->slug) }}">
            <div class="invalid-feedback d-block" data-error-for="slug"></div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Template</label>
            <select name="template" class="form-select">
                @foreach(['home', 'about', 'practice-areas', 'results', 'team', 'testimonials', 'contact', 'legal', 'default'] as $template)
                    <option value="{{ $template }}" @selected(old('template', $record->template ?: 'default') === $template)>{{ $template }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                @foreach(['draft' => 'Rascunho', 'published' => 'Publicado'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $record->status ?: 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Ordem</label>
            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $record->sort_order ?? 0) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Título do Menu</label>
            <input type="text" name="menu_title" class="form-control" value="{{ old('menu_title', $record->menu_title) }}">
        </div>
        <div class="col-md-3 form-check mt-5">
            <input type="checkbox" name="is_home" id="is_home" class="form-check-input" value="1" @checked(old('is_home', $record->is_home))>
            <label class="form-check-label" for="is_home">Página inicial</label>
        </div>
        <div class="col-md-3 form-check mt-5">
            <input type="checkbox" name="show_in_menu" id="show_in_menu" class="form-check-input" value="1" @checked(old('show_in_menu', $record->show_in_menu ?? true))>
            <label class="form-check-label" for="show_in_menu">Exibir no menu</label>
        </div>
        <div class="col-md-6">
            <label class="form-label">Hero Título</label>
            <input type="text" name="hero_title" class="form-control" value="{{ old('hero_title', $record->hero_title) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Hero CTA</label>
            <input type="text" name="hero_cta_label" class="form-control" value="{{ old('hero_cta_label', $record->hero_cta_label) }}">
        </div>
        <div class="col-12">
            <label class="form-label">Hero Subtítulo</label>
            <textarea name="hero_subtitle" class="form-control" rows="2">{{ old('hero_subtitle', $record->hero_subtitle) }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Link CTA</label>
            <input type="text" name="hero_cta_url" class="form-control" value="{{ old('hero_cta_url', $record->hero_cta_url) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Publicação</label>
            <input type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at', optional($record->published_at)->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="col-12">
            <label class="form-label">Imagem de Capa</label>
            <input
                type="file"
                name="cover_image"
                class="form-control"
                data-filepond
                data-accepted="image/png,image/jpeg,image/webp,image/svg+xml"
                data-current-url="{{ $record->cover_path ? site_asset_url($record->cover_path) : '' }}"
                data-current-name="{{ $record->cover_path ? basename($record->cover_path) : '' }}"
            >
            @if($record->cover_path)
                <div class="mt-2 small text-muted">Imagem atual: <a href="{{ site_asset_url($record->cover_path) }}" target="_blank" rel="noopener">{{ $record->cover_path }}</a></div>
            @endif
        </div>
        <div class="col-12">
            <label class="form-label">Resumo</label>
            <textarea name="excerpt" class="form-control" rows="2">{{ old('excerpt', $record->excerpt) }}</textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Conteúdo</label>
            <textarea name="body" class="form-control" data-editor="summernote">{{ old('body', $record->body) }}</textarea>
        </div>
        <div class="col-12"><hr><h6>SEO</h6></div>
        <div class="col-md-6"><input type="text" name="seo_title" class="form-control" placeholder="SEO título" value="{{ old('seo_title', $record->seoMeta?->title) }}"></div>
        <div class="col-md-6"><input type="text" name="seo_keywords" class="form-control" placeholder="Palavras-chave" value="{{ old('seo_keywords', $record->seoMeta?->keywords) }}"></div>
        <div class="col-12"><textarea name="seo_description" class="form-control" rows="2" placeholder="Descrição SEO">{{ old('seo_description', $record->seoMeta?->description) }}</textarea></div>
        <div class="col-md-6"><input type="text" name="seo_hashtags" class="form-control" placeholder="#hashtag, #seo" value="{{ old('seo_hashtags', collect($record->seoMeta?->hashtags)->implode(', ')) }}"></div>
        <div class="col-md-6"><input type="url" name="seo_canonical_url" class="form-control" placeholder="Canonical URL" value="{{ old('seo_canonical_url', $record->seoMeta?->canonical_url) }}"></div>
        <div class="col-md-6"><input type="text" name="seo_og_title" class="form-control" placeholder="OG Título" value="{{ old('seo_og_title', $record->seoMeta?->og_title) }}"></div>
        <div class="col-md-6"><input type="text" name="seo_robots" class="form-control" placeholder="Robots" value="{{ old('seo_robots', $record->seoMeta?->robots ?? 'index,follow') }}"></div>
        <div class="col-12"><textarea name="seo_og_description" class="form-control" rows="2" placeholder="OG descrição">{{ old('seo_og_description', $record->seoMeta?->og_description) }}</textarea></div>
        <div class="col-md-6"><input type="text" name="seo_schema_type" class="form-control" placeholder="Schema Type" value="{{ old('seo_schema_type', $record->seoMeta?->schema_type ?? 'WebPage') }}"></div>
        <div class="col-md-3 form-check mt-5"><input type="checkbox" name="seo_noindex" id="seo_noindex" value="1" class="form-check-input" @checked(old('seo_noindex', $record->seoMeta?->noindex))><label class="form-check-label" for="seo_noindex">Noindex</label></div>
        <div class="col-12">
            <input
                type="file"
                name="seo_og_image"
                class="form-control"
                data-filepond
                data-accepted="image/png,image/jpeg,image/webp,image/svg+xml"
                data-current-url="{{ $record->seoMeta?->og_image_path ? site_asset_url($record->seoMeta->og_image_path) : '' }}"
                data-current-name="{{ $record->seoMeta?->og_image_path ? basename($record->seoMeta->og_image_path) : '' }}"
            >
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
</form>
