@php
    $isEdit = $record->exists;
@endphp
<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Título</label><input type="text" name="title" class="form-control" value="{{ old('title', $record->title) }}"></div>
        <div class="col-md-4"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="{{ old('slug', $record->slug) }}"></div>
        <div class="col-md-4"><label class="form-label">Ícone</label><input type="text" name="icon" class="form-control" value="{{ old('icon', $record->icon) }}"></div>
        <div class="col-md-8"><label class="form-label">Chamada</label><input type="text" name="highlight" class="form-control" value="{{ old('highlight', $record->highlight) }}"></div>
        <div class="col-12"><label class="form-label">Resumo</label><textarea name="short_description" class="form-control" rows="2">{{ old('short_description', $record->short_description) }}</textarea></div>
        <div class="col-12"><label class="form-label">Descrição</label><textarea name="description" class="form-control" data-editor="summernote">{{ old('description', $record->description) }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Imagem</label><input type="file" name="image" class="form-control" data-filepond>@if($record->image_path)<div class="mt-2 small text-muted">Imagem atual: <a href="{{ site_asset_url($record->image_path) }}" target="_blank" rel="noopener">{{ $record->image_path }}</a></div>@endif</div>
        <div class="col-md-3"><label class="form-label">Ordem</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $record->sort_order ?? 0) }}"></div>
        <div class="col-md-3 d-flex gap-3 align-items-center pt-4">
            <div class="form-check"><input type="checkbox" class="form-check-input" id="area_featured" name="is_featured" value="1" @checked(old('is_featured', $record->is_featured))><label class="form-check-label" for="area_featured">Destaque</label></div>
            <div class="form-check"><input type="checkbox" class="form-check-input" id="area_active" name="is_active" value="1" @checked(old('is_active', $record->is_active ?? true))><label class="form-check-label" for="area_active">Ativa</label></div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form>
