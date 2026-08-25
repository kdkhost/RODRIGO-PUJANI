@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header">
        <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1>Templates jurídicos</h1>
                <p class="text-muted mb-0">Modelos estruturados, versões imutáveis e geração privada em DOCX ou PDF.</p>
            </div>
            @can('create', App\Models\LegalDocumentTemplate::class)
                <a class="btn btn-primary" href="{{ route('admin.legal-document-templates.create') }}">
                    <i class="bi bi-plus-lg me-1"></i>Novo template
                </a>
            @endcan
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-6 col-xl-4">
                            <label class="form-label" for="template-search">Buscar</label>
                            <input
                                class="form-control"
                                id="template-search"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Nome ou identificador"
                            >
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-primary" type="submit">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Template</th>
                                <th>Contexto</th>
                                <th>Versão</th>
                                <th>Gerações</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                                <tr>
                                    <td>
                                        <strong>{{ $template->name }}</strong>
                                        <small class="d-block text-muted">{{ $template->slug }}</small>
                                    </td>
                                    <td>{{ App\Models\LegalDocumentTemplate::contextScopes()[$template->context_scope] ?? $template->context_scope }}</td>
                                    <td>
                                        v{{ $template->latestVersion?->version ?? '—' }}
                                        <small class="d-block text-muted">{{ $template->versions_count }} versão(ões)</small>
                                    </td>
                                    <td>{{ $template->generations_count }}</td>
                                    <td>
                                        <span class="badge {{ $template->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $template->is_active ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.legal-document-templates.show', $template) }}">
                                            Detalhes
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">Nenhum template jurídico cadastrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $templates->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
