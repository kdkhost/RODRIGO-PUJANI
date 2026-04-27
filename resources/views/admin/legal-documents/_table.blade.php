<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Documento</th>
                <th>Processo / cliente</th>
                <th>Arquivo</th>
                <th>Proteção</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>
                        <div class="admin-entity-title">{{ $item->title }}</div>
                        <div class="admin-entity-meta">{{ $item->category ?: 'Sem categoria' }}</div>
                    </td>
                    <td>
                        <div class="admin-entity-title">{{ $item->legalCase?->title ?: 'Sem processo' }}</div>
                        <div class="admin-entity-meta">{{ $item->client?->name ?: 'Sem cliente' }}</div>
                    </td>
                    <td>
                        @if($item->path)
                            <a href="{{ site_asset_url($item->path) }}" target="_blank" rel="noopener">{{ $item->original_name ?: $item->file_name }}</a>
                            <div class="admin-entity-meta">{{ $item->size ? number_format($item->size / 1024, 1, ',', '.').' KB' : 'Tamanho não informado' }}</div>
                        @else
                            <span class="text-muted">Sem arquivo</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge {{ $item->is_sensitive ? 'badge-soft-danger' : 'badge-soft-info' }}">{{ $item->is_sensitive ? 'Sensível' : 'Operacional' }}</span>
                            <span class="badge {{ $item->shared_with_client ? 'badge-soft-success' : 'badge-soft-warning' }}">{{ $item->shared_with_client ? 'Compartilhado' : 'Interno' }}</span>
                        </div>
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
                    <td colspan="5" class="text-center py-4 text-muted">Nenhum documento cadastrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
