@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Marca, segurança e dados iniciais</div>
                    <h1>Configurações do sistema</h1>
                    <p>Centralize a identidade visual do painel, gerencie o favicon, ative o reCAPTCHA v3 invisível e popule a base com dados de demonstração para apresentação do sistema.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-shield-lock"></i>
                    <div>
                        <strong>{{ $recaptcha['enabled'] ? 'Proteção ativa' : 'Proteção opcional' }}</strong>
                        <small>{{ $branding['brand_name'] }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="admin-system-settings-grid">
                <form action="{{ route('admin.system-settings.update') }}" method="POST" data-ajax-form enctype="multipart/form-data" class="d-grid gap-4">
                    @csrf
                    @method('PUT')

                    <div class="card admin-table-card">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Identidade visual</div>
                                <h3 class="card-title">Marca principal do sistema</h3>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3 admin-premium-form">
                                <div class="col-md-6">
                                    <label class="form-label" for="brand_name">Nome da marca</label>
                                    <input id="brand_name" type="text" name="brand_name" class="form-control" maxlength="120" value="{{ old('brand_name', $branding['brand_name']) }}" placeholder="Nome da marca">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="brand_short_name">Sigla da marca</label>
                                    <input id="brand_short_name" type="text" name="brand_short_name" class="form-control" maxlength="8" value="{{ old('brand_short_name', $branding['brand_short_name']) }}" placeholder="Sigla da marca">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="admin_subtitle">Subtítulo do painel</label>
                                    <input id="admin_subtitle" type="text" name="admin_subtitle" class="form-control" maxlength="80" value="{{ old('admin_subtitle', $branding['admin_subtitle']) }}" placeholder="Subtítulo do painel">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="admin_footer_text">Texto principal do rodapé</label>
                                    <input id="admin_footer_text" type="text" name="admin_footer_text" class="form-control" maxlength="180" value="{{ old('admin_footer_text', $branding['admin_footer_text']) }}" placeholder="Texto principal do rodapé">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="admin_footer_meta">Texto complementar do rodapé</label>
                                    <input id="admin_footer_meta" type="text" name="admin_footer_meta" class="form-control" maxlength="180" value="{{ old('admin_footer_meta', $branding['admin_footer_meta']) }}" placeholder="Texto complementar do rodapé">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="logo">Logo principal</label>
                                    <input id="logo" type="file" name="logo" class="form-control" data-filepond data-accepted="image/png,image/jpeg,image/webp,image/svg+xml">
                                    @if($branding['logo_url'])
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="remove_logo" name="remove_logo" value="1">
                                            <label class="form-check-label" for="remove_logo">Remover logo atual</label>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="favicon">Favicon</label>
                                    <input id="favicon" type="file" name="favicon" class="form-control" data-filepond data-accepted="image/x-icon,image/png,image/webp,image/svg+xml,image/jpeg">
                                    @if($branding['favicon_url'])
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="remove_favicon" name="remove_favicon" value="1">
                                            <label class="form-check-label" for="remove_favicon">Remover favicon atual</label>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card admin-table-card">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Proteção anti-spam</div>
                                <h3 class="card-title">reCAPTCHA v3 invisível</h3>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3 admin-premium-form">
                                <div class="col-md-4 form-check ps-5 pt-4">
                                    <input type="checkbox" class="form-check-input" id="recaptcha_enabled" name="recaptcha_enabled" value="1" @checked(old('recaptcha_enabled', $recaptcha['enabled']))>
                                    <label class="form-check-label" for="recaptcha_enabled">Ativar proteção invisível</label>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="recaptcha_min_score">Score mínimo</label>
                                    <input id="recaptcha_min_score" type="number" name="recaptcha_min_score" class="form-control" min="0.1" max="1" step="0.1" value="{{ old('recaptcha_min_score', number_format($recaptcha['minimum_score'], 1, '.', '')) }}" placeholder="0.5">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Escopo protegido</label>
                                    <input type="text" class="form-control" value="Login, redefinição, portal do cliente e contato" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="recaptcha_site_key">Site key</label>
                                    <input id="recaptcha_site_key" type="text" name="recaptcha_site_key" class="form-control" value="{{ old('recaptcha_site_key', $recaptcha['site_key']) }}" placeholder="Site key do Google reCAPTCHA">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="recaptcha_secret_key">Secret key</label>
                                    <input id="recaptcha_secret_key" type="text" name="recaptcha_secret_key" class="form-control" value="{{ old('recaptcha_secret_key', $recaptcha['secret_key']) }}" placeholder="Secret key do Google reCAPTCHA">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Salvar configurações
                            </button>
                        </div>
                    </div>
                </form>

                <div class="admin-system-aside-stack">
                    <div class="admin-system-preview-card">
                        <div class="admin-system-preview-head">
                            <span class="admin-system-preview-mark"><i class="bi bi-palette"></i></span>
                            <div class="admin-system-preview-copy">
                                <span>Prévia da marca</span>
                                <strong>{{ $branding['brand_name'] }}</strong>
                                <p>Logo, favicon, subtítulo do painel e rodapé administrativo unificados no mesmo ponto de gestão.</p>
                            </div>
                        </div>

                        <div class="admin-brand-preview-grid mt-3">
                            <div class="admin-brand-preview-card">
                                <span>Logo atual</span>
                                <div class="admin-brand-preview-image mt-2">
                                    @if($branding['logo_url'])
                                        <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['brand_name'] }}">
                                    @else
                                        <span class="admin-brand-preview-empty">{{ $branding['brand_short_name'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="admin-brand-preview-card">
                                <span>Favicon atual</span>
                                <div class="admin-brand-preview-image admin-brand-preview-favicon mt-2">
                                    @if($branding['favicon_url'])
                                        <img src="{{ $branding['favicon_url'] }}" alt="Favicon {{ $branding['brand_name'] }}">
                                    @else
                                        <span class="admin-brand-preview-empty">{{ $branding['brand_short_name'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="admin-system-assets-grid mt-3">
                            <div class="admin-brand-preview-card">
                                <span>Rodapé administrativo</span>
                                <strong class="mt-2">{{ $branding['admin_footer_text'] }}</strong>
                                <small>{{ $branding['admin_footer_meta'] }}</small>
                            </div>
                            <div class="admin-brand-preview-card">
                                <span>Proteção</span>
                                <strong class="mt-2">{{ $recaptcha['enabled'] ? 'reCAPTCHA ativo' : 'reCAPTCHA desativado' }}</strong>
                                <small>Score mínimo atual: {{ number_format($recaptcha['minimum_score'], 1, ',', '.') }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="admin-system-demo-card">
                        <div class="admin-system-demo-head">
                            <span class="admin-system-demo-mark"><i class="bi bi-database-add"></i></span>
                            <div class="admin-system-demo-copy">
                                <span>Base de demonstração</span>
                                <strong>Popular o sistema com exemplos reais de uso</strong>
                                <p>Cria clientes, processos, tarefas, andamentos, documentos, agenda e usuários de demonstração para apresentar o escritório com dados visíveis.</p>
                            </div>
                        </div>

                        <div class="admin-system-demo-list">
                            <div>
                                <strong>{{ number_format($stats['users'], 0, ',', '.') }} usuários</strong>
                                <small>Supervisão do painel e equipes.</small>
                            </div>
                            <div>
                                <strong>{{ number_format($stats['clients'], 0, ',', '.') }} clientes</strong>
                                <small>Base disponível no portal do cliente.</small>
                            </div>
                            <div>
                                <strong>{{ number_format($stats['cases'], 0, ',', '.') }} processos</strong>
                                <small>Com tarefas, andamentos e documentos.</small>
                            </div>
                            <div>
                                <strong>{{ number_format($stats['calendar_events'], 0, ',', '.') }} eventos</strong>
                                <small>Agenda pronta para edição no FullCalendar.</small>
                            </div>
                            <div>
                                <strong>Credenciais demo</strong>
                                <small><code>gestor.demo@pujani.adv.br</code> e <code>associado.demo@pujani.adv.br</code></small>
                            </div>
                        </div>

                        <form action="{{ route('admin.system-settings.seed-demo-data') }}" method="POST" data-ajax-form class="mt-3">
                            @csrf
                            <button
                                type="submit"
                                class="btn btn-outline-primary w-100"
                                data-confirm-submit="true"
                                data-confirm-title="Popular base de demonstração?"
                                data-confirm-text="Os registros de exemplo serão criados ou atualizados para exibir o sistema já montado."
                                data-confirm-button="Popular agora"
                            >
                                <i class="bi bi-stars me-1"></i>Popular dados de exemplo
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
