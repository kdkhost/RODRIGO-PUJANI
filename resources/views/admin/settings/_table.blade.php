<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Grupo</th><th>Chave</th><th>Tipo</th><th>Valor</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $item->group }}</td><td><code>{{ $item->key }}</code></td><td>{{ $item->type }}</td><td>{{ \Illuminate\Support\Str::limit($item->value, 40) }}</td>
                <td class="text-end"><button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Editar</button> <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center py-4 text-muted">Nenhuma configuração cadastrada.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
