@php
    $isEdit = $record->exists;
    $selectedPermissions = old('permission_names', $record->exists ? $record->permissions->pluck('name')->all() : []);
@endphp

<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form class="admin-premium-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-7">
            <label class="form-label">Nome da funcao</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}" placeholder="Ex.: Editor, Financeiro, Atendimento" required>
            <div class="invalid-feedback" data-error-for="name"></div>
        </div>

        <div class="col-md-5">
            <label class="form-label">Guard</label>
            <input type="text" name="guard_name" class="form-control" value="{{ old('guard_name', $record->guard_name ?? 'web') }}" required>
            <div class="invalid-feedback" data-error-for="guard_name"></div>
        </div>

        <div class="col-12">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <label class="form-label mb-0">Permissoes granulares</label>
                <span class="admin-permission-meta">{{ count($selectedPermissions) }} selecionada(s)</span>
            </div>

            <div class="admin-permissions-grid">
                @foreach($permissions as $permission)
                    @php
                        $permissionId = 'role-permission-'.\Illuminate\Support\Str::slug($permission->name);
                        $friendlyName = \Illuminate\Support\Str::of($permission->name)->replace(['-', '.'], ' ')->title();
                    @endphp
                    <label class="admin-permission-option" for="{{ $permissionId }}">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            id="{{ $permissionId }}"
                            name="permission_names[]"
                            value="{{ $permission->name }}"
                            @checked(in_array($permission->name, $selectedPermissions, true))
                        >
                        <span>
                            <strong>{{ $friendlyName }}</strong>
                            <small>{{ $permission->name }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
            <div class="invalid-feedback d-block" data-error-for="permission_names"></div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle me-1"></i>Salvar funcao
        </button>
    </div>
</form>
