@extends('admin.layouts.app')

@php
    $statusLabels = ['draft' => 'Rascunho', 'pending' => 'Pendente', 'completed' => 'Concluída', 'declined' => 'Recusada', 'cancelled' => 'Cancelada', 'expired' => 'Expirada'];
    $statusClasses = ['draft' => 'secondary', 'pending' => 'warning', 'completed' => 'success', 'declined' => 'danger', 'cancelled' => 'secondary', 'expired' => 'dark'];
@endphp

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid"><div class="admin-page-hero-inner">
            <div><div class="admin-eyebrow">Documentos formais</div><h1>Assinaturas eletrônicas</h1><p>Solicitações, integridade, documentos concluídos e comprovantes de evidências.</p></div>
            @can('create', App\Models\SignatureRequest::class)<a class="btn btn-primary" href="{{ route('admin.signature-requests.create') }}"><i class="bi bi-plus-lg me-1"></i>Nova solicitação</a>@endcan
        </div></div>
    </div>
    <div class="app-content"><div class="container-fluid">
        <div class="card admin-table-card">
            <div class="card-header"><div><div class="admin-card-kicker">Central de assinaturas</div><h2 class="card-title">Solicitações registradas</h2></div></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover align-middle mb-0"><thead><tr><th class="ps-4">Documento</th><th>Cliente</th><th>Status</th><th>Prazo</th><th>Signatários</th><th class="text-end pe-4">Ação</th></tr></thead><tbody>
                    @forelse($items as $item)
                        <tr><td class="ps-4"><strong>{{ $item->title }}</strong><small class="d-block text-muted">{{ $item->public_uuid }}</small></td><td>{{ $item->client?->name }}</td><td><span class="badge text-bg-{{ $statusClasses[$item->status] ?? 'secondary' }}">{{ $statusLabels[$item->status] ?? $item->status }}</span></td><td>{{ $item->expires_at?->format('d/m/Y H:i') ?: 'Sem prazo' }}</td><td>{{ $item->signers->where('status', 'signed')->count() }}/{{ $item->signers->count() }}</td><td class="text-end pe-4"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.signature-requests.show', $item) }}"><i class="bi bi-eye me-1"></i>Detalhes</a></td></tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">Nenhuma solicitação de assinatura.</td></tr>
                    @endforelse
                </tbody></table>
            </div>
            @if($items->hasPages())<div class="card-footer">{{ $items->links() }}</div>@endif
        </div>
    </div></div>
@endsection
