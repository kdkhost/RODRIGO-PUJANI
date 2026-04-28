@php($actor = auth()->user())

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
        <tr>
            <th>Usuário</th>
            <th>Contato</th>
            <th>Funções</th>
            <th>Ativo</th>
            <th class="text-end">Ações</th>
        </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>
                    <div class="admin-entity-title">{{ $item->name }}</div>
                    <div class="admin-entity-meta">
                        {{ $item->document_number ?: 'Documento não informado' }}
                        @if($item->address_city || $item->address_state)
                            • {{ collect([$item->address_city, $item->address_state])->filter()->implode('/') }}
                        @endif
                    </div>
                </td>
                <td>
                    <div class="admin-entity-title">{{ $item->email }}</div>
                    <div class="admin-entity-meta">{{ $item->phone ?: ($item->whatsapp ?: 'Sem telefone cadastrado') }}</div>
                </td>
                <td>{{ $item->roles->pluck('name')->implode(', ') }}</td>
                <td>{{ $item->is_active ? 'Sim' : 'Não' }}</td>
                <td class="text-end">
                    @if($item->canBeImpersonatedBy($actor))
                        <form action="{{ route('admin.users.impersonate', $item) }}" method="POST" class="d-inline">
                            @csrf
                            <button
                                class="btn btn-sm btn-outline-dark"
                                type="submit"
                                data-confirm-submit="true"
                                data-confirm-title="Acessar sem senha?"
                                data-confirm-text="Você será autenticado temporariamente como {{ $item->name }}."
                                data-confirm-button="Acessar"
                            >Assumir acesso</button>
                        </form>
                    @endif
                    <button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Editar</button>
                    @if($item->canBeDeletedBy($actor))
                        <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum usuário encontrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
