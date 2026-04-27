@extends('admin.layouts.app')

@section('content')
    @php
        $initials = collect(explode(' ', trim((string) $user->name)))
            ->filter()
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('');
        $initials = $initials !== '' ? mb_strtoupper($initials) : 'PA';
        $avatarUrl = $user->avatar_path ? site_asset_url($user->avatar_path) : null;
        $roleSummary = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->implode(', ') : null;
    @endphp

    <div class="app-content-header admin-page-hero admin-profile-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Conta e segurança</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Mantenha seus dados administrativos atualizados e proteja o acesso ao painel.</p>
                </div>
                <div class="admin-profile-badge">
                    @if($avatarUrl)
                        <img class="admin-avatar admin-avatar-lg" src="{{ $avatarUrl }}" alt="{{ $user->name }}">
                    @else
                        <span class="admin-avatar admin-avatar-lg">{{ $initials }}</span>
                    @endif
                    <div>
                        <strong>{{ $user->name }}</strong>
                        <small>{{ $roleSummary ?: 'Usuário autenticado' }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-xl-4">
                    <div class="admin-profile-summary">
                        <div class="admin-profile-cover"></div>
                        <div class="admin-profile-summary-body">
                            @if($avatarUrl)
                                <img class="admin-avatar admin-avatar-xl" src="{{ $avatarUrl }}" alt="{{ $user->name }}">
                            @else
                                <span class="admin-avatar admin-avatar-xl">{{ $initials }}</span>
                            @endif
                            <h2>{{ $user->name }}</h2>
                            <p>{{ $user->email }}</p>

                            <div class="admin-profile-tags">
                                <span><i class="bi bi-shield-check"></i>{{ $roleSummary ?: 'Sem função definida' }}</span>
                                <span><i class="bi bi-circle-fill"></i>{{ $user->is_active ? 'Ativo' : 'Inativo' }}</span>
                            </div>

                            <div class="admin-profile-facts">
                                <div>
                                    <span>Conta criada</span>
                                    <strong>{{ $user->created_at?->format('d/m/Y') ?? 'Não informado' }}</strong>
                                </div>
                                <div>
                                    <span>Último acesso</span>
                                    <strong>{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Não registrado' }}</strong>
                                </div>
                                <div>
                                    <span>Fuso horário</span>
                                    <strong>{{ $user->timezone ?: config('app.timezone') }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="card admin-form-card">
                                <div class="card-header">
                                    <div>
                                        <div class="admin-card-kicker">Identidade</div>
                                        <h3 class="card-title">Informações do perfil</h3>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                                        @csrf
                                    </form>

                                    <form method="post" action="{{ route('profile.update') }}" class="admin-premium-form" enctype="multipart/form-data">
                                        @csrf
                                        @method('patch')

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="name" class="form-label">Nome</label>
                                                <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label for="email" class="form-label">E-mail</label>
                                                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
                                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="col-12">
                                                <label for="avatar" class="form-label">Foto do perfil</label>
                                                <input id="avatar" name="avatar" type="file" class="form-control @error('avatar') is-invalid @enderror" data-filepond data-accepted="image/png,image/jpeg,image/webp">
                                                @error('avatar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                @if($avatarUrl)
                                                    <div class="small text-muted mt-2">Foto atual: <a href="{{ $avatarUrl }}" target="_blank" rel="noopener">{{ $user->avatar_path }}</a></div>
                                                @endif
                                            </div>
                                        </div>

                                        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                            <div class="alert alert-warning mt-3 mb-0 d-flex gap-3 align-items-start">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                                <div>
                                                    <strong>E-mail ainda não verificado.</strong>
                                                    <div class="small">Envie um novo link para concluir a verificação da conta.</div>
                                                    <button form="send-verification" class="btn btn-sm btn-outline-warning mt-2">Reenviar verificação</button>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="admin-form-actions">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-check2-circle me-1"></i>Salvar perfil
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card admin-form-card">
                                <div class="card-header">
                                    <div>
                                        <div class="admin-card-kicker">Segurança</div>
                                        <h3 class="card-title">Atualizar senha</h3>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="{{ route('password.update') }}" class="admin-premium-form">
                                        @csrf
                                        @method('put')

                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label for="update_password_current_password" class="form-label">Senha atual</label>
                                                <input id="update_password_current_password" name="current_password" type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password">
                                                @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="col-md-4">
                                                <label for="update_password_password" class="form-label">Nova senha</label>
                                                <input id="update_password_password" name="password" type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                                                @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="col-md-4">
                                                <label for="update_password_password_confirmation" class="form-label">Confirmar senha</label>
                                                <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                                                @error('password_confirmation', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>

                                        <div class="admin-form-actions">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-lock me-1"></i>Atualizar senha
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="admin-danger-zone">
                                <div>
                                    <div class="admin-card-kicker">Zona crítica</div>
                                    <h3>Excluir conta</h3>
                                    <p>Esta ação encerra sua sessão e remove permanentemente seu usuário.</p>
                                </div>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delete-profile-modal">
                                    <i class="bi bi-trash3 me-1"></i>Excluir conta
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delete-profile-modal" tabindex="-1" aria-labelledby="delete-profile-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="modal-header">
                        <h5 class="modal-title" id="delete-profile-modal-title">Confirmar exclusão da conta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">Informe sua senha para confirmar a exclusão permanente deste usuário.</p>
                        <label for="delete_profile_password" class="form-label">Senha</label>
                        <input id="delete_profile_password" name="password" type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" autocomplete="current-password">
                        @error('password', 'userDeletion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" data-confirm-submit="true" data-confirm-title="Excluir conta?" data-confirm-text="Esta ação não poderá ser desfeita." data-confirm-button="Excluir">
                            Excluir definitivamente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@if ($errors->userDeletion->isNotEmpty())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('delete-profile-modal');
                if (modal && window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    @endpush
@endif
