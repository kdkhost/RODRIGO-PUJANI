<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Tipo</th>
                <th>Responsável</th>
                <th>Contato</th>
                <th>Cidade</th>
                <th>Status</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>
                        <div class="admin-entity-title">{{ $item->name }}</div>
                        <div class="admin-entity-meta">{{ $item->document_number ?: 'Sem documento informado' }}</div>
                    </td>
                    <td>
                        <span class="badge {{ $item->person_type === 'company' ? 'badge-soft-info' : 'badge-soft-warning' }}">
                            {{ $item->person_type === 'company' ? 'Pessoa jurídica' : 'Pessoa física' }}
                        </span>
                        @if($item->trade_name)
                            <div class="admin-entity-meta mt-2">{{ $item->trade_name }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="admin-entity-title">{{ $item->assignedLawyer?->name ?: 'Não vinculado' }}</div>
                    </td>
                    <td>
                        <div class="admin-entity-title">{{ $item->email ?: 'Sem e-mail' }}</div>
                        <div class="admin-entity-meta">{{ $item->whatsapp ?: ($item->phone ?: 'Sem telefone') }}</div>
                    </td>
                    <td>{{ $item->address_city ? $item->address_city.($item->address_state ? '/'.$item->address_state : '') : 'Não informado' }}</td>
                    <td><span class="badge {{ $item->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $item->is_active ? 'Ativo' : 'Inativo' }}</span></td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Editar</button>
                            <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Nenhum cliente cadastrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
