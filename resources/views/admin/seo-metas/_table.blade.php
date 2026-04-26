<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Rota</th><th>Título</th><th>Robots</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $item->route_name ?: 'relacional' }}</td>
                <td>{{ $item->title }}</td>
                <td>{{ $item->robots }}</td>
                <td class="text-end"><button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Editar</button> <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button></td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center py-4 text-muted">Nenhum registro SEO encontrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
