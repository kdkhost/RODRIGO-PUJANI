@php
    $isEdit = $record->exists;
@endphp
<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Autor</label><input type="text" name="author_name" class="form-control" value="{{ old('author_name', $record->author_name) }}"></div>
        <div class="col-md-6"><label class="form-label">Cargo</label><input type="text" name="author_role" class="form-control" value="{{ old('author_role', $record->author_role) }}"></div>
        <div class="col-md-6"><label class="form-label">Empresa</label><input type="text" name="company" class="form-control" value="{{ old('company', $record->company) }}"></div>
        <div class="col-md-3"><label class="form-label">Nota</label><input type="number" name="rating" class="form-control" min="1" max="5" value="{{ old('rating', $record->rating ?? 5) }}"></div>
        <div class="col-md-3"><label class="form-label">Imagem</label><input type="file" name="image" class="form-control" data-filepond data-accepted="image/png,image/jpeg,image/webp,image/svg+xml" data-current-url="{{ $record->image_path ? site_asset_url($record->image_path) : '' }}" data-current-name="{{ $record->image_path ? basename($record->image_path) : '' }}">@if($record->image_path)<div class="mt-2 small text-muted">Imagem atual: <a href="{{ site_asset_url($record->image_path) }}" target="_blank" rel="noopener">{{ $record->image_path }}</a></div>@endif</div>
        <div class="col-12"><label class="form-label">Depoimento</label><textarea name="content" class="form-control" rows="4">{{ old('content', $record->content) }}</textarea></div>
        <div class="col-md-3"><label class="form-label">Ordem</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $record->sort_order ?? 0) }}"></div>
        <div class="col-md-9 d-flex gap-4 align-items-end"><div class="form-check"><input type="checkbox" class="form-check-input" name="is_featured" id="dep_featured" value="1" @checked(old('is_featured', $record->is_featured ?? true))><label class="form-check-label" for="dep_featured">Destaque</label></div><div class="form-check"><input type="checkbox" class="form-check-input" name="is_active" id="dep_active" value="1" @checked(old('is_active', $record->is_active ?? true))><label class="form-check-label" for="dep_active">Ativo</label></div></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form>
