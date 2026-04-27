<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Andamento</th>
                <th>Processo</th>
                <th>Origem</th>
                <th>Visibilidade</th>
                <th>Registrado em</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>
                        <div class="admin-entity-title">{{ $item->title }}</div>
                        <div class="admin-entity-meta">{{ str($item->update_type)->replace('_', ' ')->headline() }}</div>
                    </td>
                    <td>
                        <div class="admin-entity-title">{{ $item->legalCase?->title ?: 'Sem processo' }}</div>
                        <div class="admin-entity-meta">{{ $item->client?->name ?: 'Sem cliente' }}</div>
                    </td>
                    <td>
                        <span class="badge badge-soft-info">{{ str($item->source)->replace('_', ' ')->headline() }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $item->is_visible_to_client ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                            {{ $item->is_visible_to_client ? 'Cliente visualiza' : 'Interno' }}
                        </span>
                    </td>
                    <td>
                        <div class="admin-entity-title">{{ $item->occurred_at?->format('d/m/Y H:i') }}</div>
                        <div class="admin-entity-meta">{{ $item->creator?->name ?: 'Automático' }}</div>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Editar</button>
                            <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Nenhum andamento cadastrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
