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
        $locationSummary = collect([$user->address_city, $user->address_state])->filter()->implode(' / ');
    @endphp

    <div class="app-content-header admin-page-hero admin-profile-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Conta e segurança</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Mantenha seus dados completos, com endereço e canais de contato atualizados para a operação do escritório.</p>
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
                                    <span>Telefone principal</span>
                                    <strong>{{ $user->phone ?: 'Não informado' }}</strong>
                                </div>
                                <div>
                                    <span>Localidade</span>
                                    <strong>{{ $locationSummary ?: 'Não informada' }}</strong>
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
                                        <div class="admin-card-kicker">Cadastro completo</div>
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

                                        <div class="admin-premium-surface p-3 p-lg-4 mb-3">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <div class="admin-card-kicker">Identificação</div>
                                                </div>
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

                                                <div class="col-md-4">
                                                    <label for="profile_phone" class="form-label">Telefone</label>
                                                    <input id="profile_phone" name="phone" type="text" data-mask="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}">
                                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="profile_whatsapp" class="form-label">WhatsApp</label>
                                                    <input id="profile_whatsapp" name="whatsapp" type="text" data-mask="phone" class="form-control @error('whatsapp') is-invalid @enderror" value="{{ old('whatsapp', $user->whatsapp) }}">
                                                    @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="profile_alternate_phone" class="form-label">Telefone alternativo</label>
                                                    <input id="profile_alternate_phone" name="alternate_phone" type="text" data-mask="phone" class="form-control @error('alternate_phone') is-invalid @enderror" value="{{ old('alternate_phone', $user->alternate_phone) }}">
                                                    @error('alternate_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="profile_document_number" class="form-label">CPF ou CNPJ</label>
                                                    <input id="profile_document_number" name="document_number" type="text" data-mask="cpf-cnpj" class="form-control @error('document_number') is-invalid @enderror" value="{{ old('document_number', $user->document_number) }}">
                                                    @error('document_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="profile_birth_date" class="form-label">Data de nascimento</label>
                                                    <input id="profile_birth_date" name="birth_date" type="date" class="form-control @error('birth_date') is-invalid @enderror" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}">
                                                    @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="profile_timezone" class="form-label">Fuso horário</label>
                                                    <input id="profile_timezone" name="timezone" type="text" class="form-control @error('timezone') is-invalid @enderror" value="{{ old('timezone', $user->timezone ?: config('app.timezone')) }}">
                                                    @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>

                                                <div class="col-12 admin-upload-compact">
                                                    <div class="row g-3 align-items-start">
                                                        <div class="col-lg-10">
                                                            <label for="avatar" class="form-label">Foto do perfil</label>
                                                            <input
                                                                id="avatar"
                                                                name="avatar"
                                                                type="file"
                                                                class="form-control @error('avatar') is-invalid @enderror"
                                                                data-filepond
                                                                data-accepted="image/png,image/jpeg,image/webp"
                                                                data-current-url="{{ $avatarUrl ?: '' }}"
                                                                data-current-name="{{ $user->avatar_path ? basename($user->avatar_path) : '' }}"
                                                            >
                                                            @error('avatar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                            @if($avatarUrl)
                                                                <div class="small text-muted mt-2">Foto atual: <a href="{{ $avatarUrl }}" target="_blank" rel="noopener">{{ $user->avatar_path }}</a></div>
                                                            @endif
                                                        </div>
                                                        <div class="col-lg-2 admin-profile-avatar-preview-column">
                                                            <label class="form-label text-center w-100">Preview</label>
                                                            <div class="admin-profile-avatar-preview">
                                                                @if($avatarUrl)
                                                                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}">
                                                                @else
                                                                    <span>{{ $initials }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="admin-premium-surface p-3 p-lg-4">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <div class="admin-card-kicker">Endereço completo</div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="profile_address_zip" class="form-label">CEP</label>
                                                    <input id="profile_address_zip" name="address_zip" type="text" data-mask="cep" data-cep-autofill class="form-control @error('address_zip') is-invalid @enderror" value="{{ old('address_zip', $user->address_zip) }}">
                                                    @error('address_zip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>

                                                <div class="col-md-7">
                                                    <label for="profile_address_street" class="form-label">Logradouro</label>
                                                    <input id="profile_address_street" name="address_street" type="text" class="form-control @error('address_street') is-invalid @enderror" value="{{ old('address_street', $user->address_street) }}">
                                                    @error('address_street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>

                                                <div class="col-md-2">
                                                    <label for="profile_address_number" class="form-label">Número</label>
                                                    <input id="profile_address_number" name="address_number" type="text" class="form-control @error('address_number') is-invalid @enderror" value="{{ old('address_number', $user->address_number) }}">
                                                    @error('address_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="profile_address_complement" class="form-label">Complemento</label>
                                                    <input id="profile_address_complement" name="address_complement" type="text" class="form-control @error('address_complement') is-invalid @enderror" value="{{ old('address_complement', $user->address_complement) }}">
                                                    @error('address_complement')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="profile_address_district" class="form-label">Bairro</label>
                                                    <input id="profile_address_district" name="address_district" type="text" class="form-control @error('address_district') is-invalid @enderror" value="{{ old('address_district', $user->address_district) }}">
                                                    @error('address_district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="profile_address_city" class="form-label">Cidade</label>
                                                    <input id="profile_address_city" name="address_city" type="text" class="form-control @error('address_city') is-invalid @enderror" value="{{ old('address_city', $user->address_city) }}">
                                                    @error('address_city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>

                                                <div class="col-md-1">
                                                    <label for="profile_address_state" class="form-label">UF</label>
                                                    <input id="profile_address_state" name="address_state" type="text" class="form-control text-uppercase @error('address_state') is-invalid @enderror" value="{{ old('address_state', $user->address_state) }}" maxlength="2">
                                                    @error('address_state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
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
                                @if($user->isSuperAdmin())
                                    <span class="badge text-bg-dark">Conta Super Admin protegida</span>
                                @else
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delete-profile-modal">
                                        <i class="bi bi-trash3 me-1"></i>Excluir conta
                                    </button>
                                @endif
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
