<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Processo</th>
                <th>Cliente</th>
                <th>Responsável</th>
                <th>Status</th>
                <th>Portal / CNJ</th>
                <th>Próximo marco</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>
                        <div class="admin-entity-title">{{ $item->title }}</div>
                        <div class="admin-entity-meta">{{ $item->process_number ?: ($item->internal_code ?: 'Sem número informado') }}</div>
                    </td>
                    <td>{{ $item->client?->name ?: 'Cliente não vinculado' }}</td>
                    <td>{{ $item->primaryLawyer?->name ?: 'Sem responsável' }}</td>
                    <td>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge badge-soft-info">{{ str($item->status)->replace('_', ' ')->headline() }}</span>
                            <span class="badge badge-soft-warning">{{ str($item->priority)->headline() }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge {{ $item->portal_visible ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ $item->portal_visible ? 'Visível ao cliente' : 'Interno' }}</span>
                            <span class="badge {{ $item->datajud_sync_enabled ? 'badge-soft-warning' : 'badge-soft-secondary' }}">{{ $item->datajud_sync_enabled ? 'CNJ ativo' : 'CNJ inativo' }}</span>
                        </div>
                        <div class="admin-entity-meta">{{ $item->updates_count }} andamento(s) · {{ $item->legal_documents_count }} documento(s)</div>
                    </td>
                    <td>
                        <div class="admin-entity-title">{{ $item->next_deadline_at?->format('d/m/Y H:i') ?: 'Sem prazo' }}</div>
                        <div class="admin-entity-meta">{{ $item->next_hearing_at ? 'Audiência: '.$item->next_hearing_at->format('d/m/Y H:i') : 'Sem audiência' }}</div>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                            @if($item->process_number && $item->tribunal_alias)
                                <form action="{{ route('admin.legal-cases.sync-datajud', $item->id) }}" method="POST" data-ajax-form class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-warning" type="submit" data-confirm-submit="true" data-confirm-title="Sincronizar com o CNJ?" data-confirm-text="As movimentações públicas do processo serão importadas do DataJud para o histórico deste caso.">
                                        CNJ
                                    </button>
                                </form>
                            @endif
                            <button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Editar</button>
                            <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Nenhum processo cadastrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
