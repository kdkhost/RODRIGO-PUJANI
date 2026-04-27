@php
    $isEdit = $record->exists;
@endphp

<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form class="admin-premium-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-7">
            <label class="form-label">Chave técnica da permissão</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}" placeholder="Ex.: pages.manage" required>
            <div class="invalid-feedback" data-error-for="name"></div>
            <div class="form-text">Use o padrão <code>modulo.acao</code> para manter o ACL consistente e previsível.</div>
        </div>

        <div class="col-md-5">
            <label class="form-label">Contexto de autenticação</label>
            <input type="text" name="guard_name" class="form-control" value="{{ old('guard_name', $record->guard_name ?? 'web') }}" required>
            <div class="invalid-feedback" data-error-for="guard_name"></div>
        </div>

        <div class="col-12">
            <div class="admin-permission-patterns">
                <div><strong>Listagem e gestão</strong><code>pages.manage</code></div>
                <div><strong>Usuários</strong><code>users.manage</code></div>
                <div><strong>Impersonação</strong><code>users.impersonate</code></div>
                <div><strong>Arquivos críticos</strong><code>system-files.manage</code></div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle me-1"></i>Salvar permissão
        </button>
    </div>
</form>
