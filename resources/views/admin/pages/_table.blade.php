<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
        <tr>
            <th>Título</th>
            <th>Slug</th>
            <th>Template</th>
            <th>Status</th>
            <th>Menu</th>
            <th class="text-end">Ações</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($items as $page)
            <tr>
                <td>{{ $page->title }}</td>
                <td><code>{{ $page->slug }}</code></td>
                <td>{{ $page->template }}</td>
                <td><span class="badge {{ $page->status === 'published' ? 'badge-soft-success' : 'badge-soft-warning' }}">{{ $page->status }}</span></td>
                <td>{{ $page->show_in_menu ? 'Sim' : 'Não' }}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $page->id) }}" data-modal-title="Editar {{ $singularLabel }}">Editar</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $page->id) }}" data-table-target="#admin-resource-table">Excluir</button>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhuma página encontrada.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
