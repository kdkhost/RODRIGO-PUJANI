@php
    $isEdit = $record->exists;
@endphp
<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form>
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Página</label>
            <select name="page_id" class="form-select">
                @foreach($pages as $page)
                    <option value="{{ $page->id }}" @selected(old('page_id', $record->page_id) == $page->id)>{{ $page->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">Chave</label><input type="text" name="section_key" class="form-control" value="{{ old('section_key', $record->section_key) }}"></div>
        <div class="col-md-8"><label class="form-label">Título</label><input type="text" name="title" class="form-control" value="{{ old('title', $record->title) }}"></div>
        <div class="col-md-4"><label class="form-label">Estilo</label><input type="text" name="style_variant" class="form-control" value="{{ old('style_variant', $record->style_variant) }}"></div>
        <div class="col-12"><label class="form-label">Subtítulo</label><textarea name="subtitle" class="form-control" rows="2">{{ old('subtitle', $record->subtitle) }}</textarea></div>
        <div class="col-12"><label class="form-label">Conteúdo</label><textarea name="content" class="form-control" data-editor="summernote">{{ old('content', $record->content) }}</textarea></div>
        <div class="col-12"><label class="form-label">JSON Estruturado</label><textarea name="data_json" class="form-control" rows="5">{{ old('data_json', $record->data ? json_encode($record->data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '') }}</textarea></div>
        <div class="col-md-3"><label class="form-label">Ordem</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $record->sort_order ?? 0) }}"></div>
        <div class="col-md-3 form-check mt-5"><input type="checkbox" name="is_active" id="section_active" value="1" class="form-check-input" @checked(old('is_active', $record->is_active ?? true))><label class="form-check-label" for="section_active">Ativa</label></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
</form>
