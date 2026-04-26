@php($isEdit = $record->exists)
<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form>
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Grupo</label><input type="text" name="group" class="form-control" value="{{ old('group', $record->group) }}"></div>
        <div class="col-md-4"><label class="form-label">Chave</label><input type="text" name="key" class="form-control" value="{{ old('key', $record->key) }}"></div>
        <div class="col-md-4"><label class="form-label">Tipo</label><select name="type" class="form-select">@foreach(['text','textarea','json','boolean'] as $type)<option value="{{ $type }}" @selected(old('type', $record->type ?: 'text') === $type)>{{ $type }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Rótulo</label><input type="text" name="label" class="form-control" value="{{ old('label', $record->label) }}"></div>
        <div class="col-md-3"><label class="form-label">Ordem</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $record->sort_order ?? 0) }}"></div>
        <div class="col-md-3 form-check mt-5"><input type="checkbox" class="form-check-input" name="is_public" id="setting_public" value="1" @checked(old('is_public', $record->is_public))><label class="form-check-label" for="setting_public">Público</label></div>
        <div class="col-12"><label class="form-label">Valor</label><textarea name="value" class="form-control" rows="3" @if(in_array(old('type', $record->type ?: 'text'), ['textarea', 'html'], true)) data-editor="summernote" data-editor-height="220" @endif>{{ old('value', $record->value) }}</textarea></div>
        <div class="col-12"><label class="form-label">JSON</label><textarea name="json_text" class="form-control" rows="5">{{ old('json_text', $record->json_value ? json_encode($record->json_value, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '') }}</textarea></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form>
