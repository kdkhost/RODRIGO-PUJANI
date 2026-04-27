@php
    $statusLabels = [
        'new' => 'Novo',
        'in_progress' => 'Em andamento',
        'answered' => 'Respondido',
        'archived' => 'Arquivado',
    ];
@endphp

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Nome</th><th>Contato</th><th>Área</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->email }}<br><small class="text-muted">{{ $item->phone }}</small></td>
                <td>{{ $item->area_interest }}</td>
                <td><span class="badge badge-soft-info">{{ $statusLabels[$item->status] ?? $item->status }}</span></td>
                <td class="text-end"><button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Gerenciar</button> <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center py-4 text-muted">Nenhuma mensagem encontrada.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
