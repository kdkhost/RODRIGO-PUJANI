@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Acesso sensível</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Antes de abrir os arquivos críticos do ambiente, confirme sua senha atual. Esta etapa é exclusiva do Super Admin.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-person-lock"></i>
                    <div>
                        <strong>Revalidação obrigatória</strong>
                        <small>Somente para esta abertura</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-4 justify-content-center admin-system-confirm-shell">
                <div class="col-xl-4 col-lg-5">
                    <div class="card admin-system-confirm-aside">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Por que existe este bloqueio</div>
                                <h3 class="card-title">Camada premium de segurança</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-system-guard-points">
                                <div>
                                    <strong>Arquivos críticos</strong>
                                    <span>.env e .htaccess afetam autenticação, rotas, cache, redirecionamentos e disponibilidade.</span>
                                </div>
                                <div>
                                    <strong>Escopo isolado</strong>
                                    <span>Administradores comuns não acessam nem visualizam este módulo no painel.</span>
                                </div>
                                <div>
                                    <strong>Backup automático</strong>
                                    <span>Toda alteração gera histórico, mas a revisão prévia continua obrigatória.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5 col-lg-7">
                    <div class="card admin-system-confirm-card">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Confirmação do Super Admin</div>
                                <h3 class="card-title">Digite sua senha atual</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.system-files.confirm.store') }}" method="POST" class="admin-premium-form">
                                @csrf

                                <div class="mb-3">
                                    <label for="system_files_password" class="form-label">Senha atual</label>
                                    <input
                                        id="system_files_password"
                                        name="password"
                                        type="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        autocomplete="current-password"
                                        autofocus
                                        required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="admin-system-confirm-note">
                                    A liberação gerada aqui serve apenas para abrir esta área sensível na sessão atual.
                                </div>

                                <div class="admin-form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-unlock me-1"></i>Validar e acessar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
