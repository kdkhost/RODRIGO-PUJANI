<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Funcao</th>
                <th>Guard</th>
                <th>Permissoes</th>
                <th class="text-end">Acoes</th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>
                    <div class="admin-permission-name">{{ $item->name }}</div>
                    <div class="admin-permission-meta">{{ $item->permissions->count() }} permissao(oes) vinculada(s)</div>
                </td>
                <td><span class="badge badge-soft-info">{{ $item->guard_name }}</span></td>
                <td>
                    <div class="admin-permission-badges">
                        @forelse($item->permissions->take(8) as $permission)
                            <span class="admin-permission-badge">{{ $permission->name }}</span>
                        @empty
                            <span class="text-muted small">Nenhuma permissao</span>
                        @endforelse

                        @if($item->permissions->count() > 8)
                            <span class="admin-permission-badge">+{{ $item->permissions->count() - 8 }}</span>
                        @endif
                    </div>
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Acoes">
                        <button class="btn btn-outline-primary" type="button" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}" data-modal-title="Editar {{ $singularLabel }}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-outline-danger" type="button" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center py-4 text-muted">Nenhuma funcao encontrada.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="px-3 py-2">{{ $items->links() }}</div>
