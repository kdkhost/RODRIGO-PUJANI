<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Autor</th><th>Empresa</th><th>Nota</th><th>Ativo</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $item->author_name }}</td><td>{{ $item->company }}</td><td>{{ $item->rating }}/5</td><td>{{ $item->is_active ? 'Sim' : 'Não' }}</td>
                <td class="text-end"><button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Editar</button> <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum depoimento cadastrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
