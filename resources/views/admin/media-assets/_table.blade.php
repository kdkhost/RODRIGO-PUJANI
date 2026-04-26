<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Arquivo</th><th>Tipo</th><th>Tamanho</th><th>Diretório</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $item->original_name }}</td>
                <td>{{ $item->type }}</td>
                <td>{{ number_format($item->size / 1024, 1, ',', '.') }} KB</td>
                <td>{{ $item->directory }}</td>
                <td class="text-end"><button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Editar</button> <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center py-4 text-muted">Nenhuma mídia encontrada.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
