<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Nome</th><th>E-mail</th><th>Funções</th><th>Ativo</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->email }}</td>
                <td>{{ $item->roles->pluck('name')->implode(', ') }}</td>
                <td>{{ $item->is_active ? 'Sim' : 'Não' }}</td>
                <td class="text-end">
                    @can('impersonate.users')
                        @if(auth()->id() !== $item->id && $item->is_active)
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
                    @endcan
                    <button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Editar</button>
                    <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum usuário encontrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
