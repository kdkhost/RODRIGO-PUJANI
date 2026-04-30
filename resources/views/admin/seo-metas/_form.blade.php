@php
    $isEdit = $record->exists;
@endphp
<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nome da Rota</label><input type="text" name="route_name" class="form-control" value="{{ old('route_name', $record->route_name) }}"></div>
        <div class="col-md-6"><label class="form-label">Título</label><input type="text" name="title" class="form-control" value="{{ old('title', $record->title) }}"></div>
        <div class="col-12"><label class="form-label">Descrição</label><textarea name="description" class="form-control" rows="2">{{ old('description', $record->description) }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Keywords</label><input type="text" name="keywords" class="form-control" value="{{ old('keywords', $record->keywords) }}"></div>
        <div class="col-md-6"><label class="form-label">Hashtags</label><input type="text" name="hashtags_text" class="form-control" value="{{ old('hashtags_text', collect($record->hashtags)->implode(', ')) }}"></div>
        <div class="col-md-6"><label class="form-label">OG Título</label><input type="text" name="og_title" class="form-control" value="{{ old('og_title', $record->og_title) }}"></div>
        <div class="col-md-6"><label class="form-label">Canonical</label><input type="url" name="canonical_url" class="form-control" value="{{ old('canonical_url', $record->canonical_url) }}"></div>
        <div class="col-12"><label class="form-label">OG Descrição</label><textarea name="og_description" class="form-control" rows="2">{{ old('og_description', $record->og_description) }}</textarea></div>
        <div class="col-md-4"><label class="form-label">Robots</label><input type="text" name="robots" class="form-control" value="{{ old('robots', $record->robots ?? 'index,follow') }}"></div>
        <div class="col-md-4"><label class="form-label">Schema Type</label><input type="text" name="schema_type" class="form-control" value="{{ old('schema_type', $record->schema_type ?? 'WebPage') }}"></div>
        <div class="col-md-4">
            <label class="form-label">OG Image</label>
            <input type="file" name="og_image" class="form-control" data-filepond data-accepted="image/png,image/jpeg,image/webp,image/svg+xml" data-current-url="{{ $record->og_image_path ? site_asset_url($record->og_image_path) : '' }}" data-current-name="{{ $record->og_image_path ? basename($record->og_image_path) : '' }}">
            @if($record->og_image_path)
                <div class="admin-brand-preview-card mt-3">
                    <span>Pré-visualização atual</span>
                    <div class="admin-brand-preview-image mt-2" style="min-height: 180px;">
                        <img src="{{ site_asset_url($record->og_image_path) }}" alt="{{ $record->title ?: $record->route_name }}" style="max-height: 160px; border-radius: 0.8rem;">
                    </div>
                    <div class="mt-2 small text-muted">Imagem atual: <a href="{{ site_asset_url($record->og_image_path) }}" target="_blank" rel="noopener">{{ $record->og_image_path }}</a></div>
                </div>
            @endif
        </div>
        <div class="col-12 form-check"><input type="checkbox" class="form-check-input" id="seo_noindex_generic" name="noindex" value="1" @checked(old('noindex', $record->noindex))><label class="form-check-label" for="seo_noindex_generic">Noindex</label></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form>
