@php
    $isEdit = $record->exists;
@endphp
<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form>
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nome</label><input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}"></div>
        <div class="col-md-6"><label class="form-label">Guard</label><input type="text" name="guard_name" class="form-control" value="{{ old('guard_name', $record->guard_name ?? 'web') }}"></div>
        <div class="col-12"><label class="form-label">Permissões</label><select name="permission_names[]" class="form-select" multiple size="8">@foreach($permissions as $permission)<option value="{{ $permission->name }}" @selected(in_array($permission->name, old('permission_names', $record->permissions->pluck('name')->all() ?? [])))>{{ $permission->name }}</option>@endforeach</select></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form>
