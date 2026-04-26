@php
    $isEdit = $record->exists;
@endphp
<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nome</label><input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}"></div>
        <div class="col-md-6"><label class="form-label">E-mail</label><input type="email" name="email" class="form-control" value="{{ old('email', $record->email) }}"></div>
        <div class="col-md-4"><label class="form-label">Telefone</label><input type="text" name="phone" data-mask="phone" class="form-control" value="{{ old('phone', $record->phone) }}"></div>
        <div class="col-md-4"><label class="form-label">Timezone</label><input type="text" name="timezone" class="form-control" value="{{ old('timezone', $record->timezone ?? 'America/Sao_Paulo') }}"></div>
        <div class="col-md-4"><label class="form-label">Avatar</label><input type="file" name="avatar" class="form-control" data-filepond></div>
        <div class="col-md-6"><label class="form-label">Senha</label><input type="password" name="password" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Confirmar Senha</label><input type="password" name="password_confirmation" class="form-control"></div>
        <div class="col-12"><label class="form-label">Funções</label><select name="role_names[]" class="form-select" multiple size="5">@foreach($roles as $role)<option value="{{ $role->name }}" @selected(in_array($role->name, old('role_names', $record->roles->pluck('name')->all() ?? [])))>{{ $role->name }}</option>@endforeach</select></div>
        <div class="col-12 form-check"><input type="checkbox" class="form-check-input" id="user_active" name="is_active" value="1" @checked(old('is_active', $record->is_active ?? true))><label class="form-check-label" for="user_active">Usuário ativo</label></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form>
