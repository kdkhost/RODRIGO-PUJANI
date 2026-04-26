@php
    $isEdit = $record->exists;
@endphp
<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Diretório</label><input type="text" name="directory" class="form-control" value="{{ old('directory', $record->directory) }}"></div>
        <div class="col-md-6"><label class="form-label">Arquivo</label><input type="file" name="file" class="form-control" data-filepond></div>
        <div class="col-md-6"><label class="form-label">Texto alternativo</label><input type="text" name="alt_text" class="form-control" value="{{ old('alt_text', $record->alt_text) }}"></div>
        <div class="col-md-6 form-check mt-5"><input type="checkbox" class="form-check-input" id="media_public" name="is_public" value="1" @checked(old('is_public', $record->is_public ?? true))><label class="form-check-label" for="media_public">Pública</label></div>
        <div class="col-12"><label class="form-label">Legenda</label><textarea name="caption" class="form-control" rows="3">{{ old('caption', $record->caption) }}</textarea></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form>
