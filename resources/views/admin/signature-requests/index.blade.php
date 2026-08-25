@extends('admin.layouts.app')
@section('content')
<div class="app-content-header"><div class="container-fluid d-flex justify-content-between align-items-center"><div><h1>Assinaturas eletrônicas</h1><p class="text-muted mb-0">Solicitações, integridade e comprovantes.</p></div>@can('create', App\Models\SignatureRequest::class)<a class="btn btn-primary" href="{{ route('admin.signature-requests.create') }}"><i class="bi bi-plus-lg"></i> Nova solicitação</a>@endcan</div></div>
<div class="app-content"><div class="container-fluid"><div class="card"><div class="card-body table-responsive"><table class="table align-middle"><thead><tr><th>Documento</th><th>Cliente</th><th>Status</th><th>Prazo</th><th>Signatários</th><th></th></tr></thead><tbody>
@forelse($items as $item)<tr><td><strong>{{ $item->title }}</strong><br><small>{{ $item->public_uuid }}</small></td><td>{{ $item->client?->name }}</td><td><span class="badge text-bg-secondary">{{ strtoupper($item->status) }}</span></td><td>{{ $item->expires_at?->format('d/m/Y H:i') ?: 'Sem prazo' }}</td><td>{{ $item->signers->where('status','signed')->count() }}/{{ $item->signers->count() }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.signature-requests.show',$item) }}">Detalhes</a></td></tr>
@empty<tr><td colspan="6" class="text-center text-muted py-4">Nenhuma solicitação.</td></tr>@endforelse
</tbody></table>{{ $items->links() }}</div></div></div></div>
@endsection
