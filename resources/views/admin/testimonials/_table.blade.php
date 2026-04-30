<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
        <tr>
            <th>Autor</th>
            <th>Empresa</th>
            <th>Nota</th>
            <th>Status</th>
            <th class="text-end">Ações</th>
        </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            @php
                $imageUrl = site_asset_url($item->image_path);
                $initials = collect(preg_split('/\s+/', trim((string) $item->author_name)))
                    ->filter()
                    ->take(2)
                    ->map(fn ($part) => mb_substr($part, 0, 1))
                    ->implode('');
            @endphp
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="admin-avatar admin-avatar-md flex-shrink-0">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $item->author_name }}" class="w-100 h-100 object-fit-cover rounded-circle">
                            @else
                                <span>{{ $initials !== '' ? $initials : 'DP' }}</span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="fw-semibold text-white">{{ $item->author_name }}</div>
                            @if($item->author_role)
                                <div class="text-muted small">{{ $item->author_role }}</div>
                            @endif
                        </div>
                    </div>
                </td>
                <td>{{ $item->company ?: 'Não informado' }}</td>
                <td>{{ $item->rating ? $item->rating.'/5' : 'Sem nota' }}</td>
                <td>
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <span class="badge {{ $item->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $item->is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                        <button
                            type="button"
                            class="btn btn-sm {{ $item->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                            data-toggle-url="{{ route('admin.testimonials.toggle-active', $item->id) }}"
                            data-table-target="#admin-resource-table"
                            data-toggle-title="{{ $item->is_active ? 'Desativar depoimento?' : 'Ativar depoimento?' }}"
                            data-toggle-text="{{ $item->is_active ? 'O depoimento deixará de aparecer nas áreas que dependem apenas de registros ativos.' : 'O depoimento voltará a ficar disponível para exibição.' }}"
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
                <td colspan="5" class="text-center py-4 text-muted">Nenhum depoimento cadastrado.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
