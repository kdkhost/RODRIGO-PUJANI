@php
    $actor = auth()->user();
@endphp

<div class="table-responsive">
    <table class="table table-hover align-middle admin-users-table">
        <thead>
        <tr>
            <th>Usuário</th>
            <th>Contato</th>
            <th>Função</th>
            <th>Ativo</th>
            <th class="text-end">Ações</th>
        </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            @php
                $itemInitials = collect(explode(' ', trim((string) $item->name)))
                    ->filter()
                    ->map(fn ($part) => mb_substr($part, 0, 1))
                    ->take(2)
                    ->implode('');
                $itemInitials = $itemInitials !== '' ? mb_strtoupper($itemInitials) : 'US';
                $itemAvatarUrl = $item->avatar_path ? site_asset_url($item->avatar_path) : null;
                $roleName = $item->roles->pluck('name')->first() ?: 'Sem função';
            @endphp
            <tr>
                <td>
                    <div class="admin-user-row">
                        @if($itemAvatarUrl)
                            <img class="admin-avatar admin-avatar-md admin-user-list-avatar" src="{{ $itemAvatarUrl }}" alt="{{ $item->name }}">
                        @else
                            <span class="admin-avatar admin-avatar-md admin-user-list-avatar">{{ $itemInitials }}</span>
                        @endif
                        <div>
                            <div class="admin-entity-title">{{ $item->name }}</div>
                            <div class="admin-entity-meta">
                                {{ $item->document_number ?: 'Documento não informado' }}
                                @if($item->address_city || $item->address_state)
                                    • {{ collect([$item->address_city, $item->address_state])->filter()->implode('/') }}
                                @endif
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="admin-entity-title">{{ $item->email }}</div>
                    <div class="admin-entity-meta">{{ $item->phone ?: ($item->whatsapp ?: 'Sem telefone cadastrado') }}</div>
                </td>
                <td><span class="admin-role-pill">{{ $roleName }}</span></td>
                <td>
                    <span class="admin-status-pill {{ $item->is_active ? 'is-active' : 'is-inactive' }}">
                        {{ $item->is_active ? 'Ativo' : 'Inativo' }}
                    </span>
                </td>
                <td class="text-end">
                    <div class="admin-table-actions">
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
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum usuário encontrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
