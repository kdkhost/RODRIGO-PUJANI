@php
    $actor = auth()->user();
@endphp

<div class="table-responsive">
    <table class="table table-hover align-middle admin-users-table">
        <thead>
        <tr>
            <th>Profissional</th>
            <th>Atuação</th>
            <th>Contato</th>
            <th>Status</th>
            <th>Login</th>
            <th class="text-end">Ações</th>
        </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            @php
                $linkedUser = $item->linkedUser;
                $canToggleLogin = $linkedUser?->canHaveStatusChangedBy($actor) ?? false;
                $memberInitials = collect(explode(' ', trim((string) $item->name)))
                    ->filter()
                    ->map(fn ($part) => mb_substr($part, 0, 1))
                    ->take(2)
                    ->implode('');
                $memberInitials = $memberInitials !== '' ? mb_strtoupper($memberInitials) : 'EQ';
                $memberAvatarUrl = $item->image_path
                    ? site_asset_url($item->image_path)
                    : ($linkedUser?->avatar_path ? site_asset_url($linkedUser->avatar_path) : null);
                $memberRole = $linkedUser?->roles?->pluck('name')?->first();
            @endphp
            <tr>
                <td>
                    <div class="admin-user-row">
                        @if($memberAvatarUrl)
                            <img class="admin-avatar admin-avatar-md admin-user-list-avatar" src="{{ $memberAvatarUrl }}" alt="{{ $item->name }}">
                        @else
                            <span class="admin-avatar admin-avatar-md admin-user-list-avatar">{{ $memberInitials }}</span>
                        @endif
                        <div>
                            <div class="admin-entity-title">{{ $item->name }}</div>
                            <div class="admin-entity-meta">
                                {{ $item->is_partner ? 'Sócio' : 'Equipe jurídica' }}
                                @if($memberRole)
                                    • {{ $memberRole }}
                                @endif
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="admin-entity-title">{{ $item->role }}</div>
                    <div class="admin-entity-meta">{{ $item->oab_number ?: 'OAB não informada' }}</div>
                </td>
                <td>
                    <div class="admin-entity-title">{{ $item->email ?: 'E-mail não informado' }}</div>
                    <div class="admin-entity-meta">{{ $item->phone ?: ($item->whatsapp ?: 'Sem telefone cadastrado') }}</div>
                </td>
                <td>
                    <button
                        type="button"
                        class="admin-status-toggle {{ $item->is_active ? 'is-active' : 'is-inactive' }}"
                        data-toggle-url="{{ route('admin.team-members.toggle-active', $item->id) }}"
                        data-table-target="#admin-resource-table"
                        data-toggle-title="{{ $item->is_active ? 'Desativar profissional?' : 'Ativar profissional?' }}"
                        data-toggle-text="{{ $item->is_active ? 'O profissional ficará oculto das áreas que dependem do cadastro ativo até nova ativação.' : 'O profissional voltará a ficar ativo no cadastro da equipe.' }}"
                        data-toggle-button="{{ $item->is_active ? 'Desativar' : 'Ativar' }}"
                    >
                        <span></span>{{ $item->is_active ? 'Ativo' : 'Inativo' }}
                    </button>
                </td>
                <td>
                    @if($linkedUser)
                        @if($canToggleLogin)
                            <button
                                type="button"
                                class="admin-status-toggle {{ $linkedUser->is_active ? 'is-active' : 'is-inactive' }}"
                                data-toggle-url="{{ route('admin.users.toggle-active', $linkedUser) }}"
                                data-table-target="#admin-resource-table"
                                data-toggle-title="{{ $linkedUser->is_active ? 'Desativar login do profissional?' : 'Ativar login do profissional?' }}"
                                data-toggle-text="{{ $linkedUser->is_active ? 'O profissional perderá o acesso ao sistema até nova ativação.' : 'O profissional voltará a acessar o sistema conforme sua função e permissões.' }}"
                                data-toggle-button="{{ $linkedUser->is_active ? 'Desativar login' : 'Ativar login' }}"
                            >
                                <span></span>{{ $linkedUser->is_active ? 'Login ativo' : 'Login inativo' }}
                            </button>
                        @else
                            <span class="admin-status-pill {{ $linkedUser->is_active ? 'is-active' : 'is-inactive' }}">
                                {{ $linkedUser->is_active ? 'Login ativo' : 'Login inativo' }}
                            </span>
                        @endif
                    @else
                        <span class="admin-status-pill is-inactive">Sem acesso</span>
                        <div class="admin-entity-meta mt-1">Cadastre um usuário com o mesmo e-mail para liberar login.</div>
                    @endif
                </td>
                <td class="text-end">
                    <div class="admin-table-actions">
                        <button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Editar</button>
                        <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum membro cadastrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
