<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
        <tr>
            <th>Título</th>
            <th>Slug</th>
            <th>Destaque</th>
            <th>Status</th>
            <th class="text-end">Ações</th>
        </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>
                    <div class="fw-semibold text-white">{{ $item->title }}</div>
                    @if($item->highlight)
                        <div class="text-muted small">{{ $item->highlight }}</div>
                    @endif
                </td>
                <td><code>{{ $item->slug }}</code></td>
                <td>
                    <span class="badge {{ $item->is_featured ? 'text-bg-warning' : 'text-bg-secondary' }}">
                        {{ $item->is_featured ? 'Em destaque' : 'Padrão' }}
                    </span>
                </td>
                <td>
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <span class="badge {{ $item->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $item->is_active ? 'Ativa' : 'Inativa' }}
                        </span>
                        <button
                            type="button"
                            class="btn btn-sm {{ $item->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                            data-toggle-url="{{ route('admin.practice-areas.toggle-active', $item->id) }}"
                            data-table-target="#admin-resource-table"
                            data-toggle-title="{{ $item->is_active ? 'Desativar área?' : 'Ativar área?' }}"
                            data-toggle-text="{{ $item->is_active ? 'A área deixará de aparecer nas seções que usam apenas registros ativos.' : 'A área voltará a ficar disponível para exibição no site e no painel.' }}"
                            data-toggle-button="{{ $item->is_active ? 'Desativar' : 'Ativar' }}"
                        >
                            {{ $item->is_active ? 'Desativar' : 'Ativar' }}
                        </button>
                    </div>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Editar</button>
                    <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">Nenhuma área cadastrada.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
