@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Marca, PWA, segurança e dados iniciais</div>
                    <h1>Configurações do sistema</h1>
                    <p>Centralize a identidade visual do painel, controle todo o PWA pelo administrativo, gerencie o favicon, ative o reCAPTCHA v3 invisível e popule a base com dados de demonstração.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-phone"></i>
                    <div>
                        <strong>{{ $pwa['enabled'] ? 'PWA ativo' : 'PWA desativado' }}</strong>
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

                    <div class="card admin-table-card admin-settings-shell">
                        <div class="card-body p-3 p-lg-4">
                            <div class="admin-settings-toolbar">
                                <div class="admin-settings-toolbar-copy">
                                    <div class="admin-card-kicker mb-2">Organizacao por contexto</div>
                                    <h3 class="card-title mb-1">Guias de configuracao</h3>
                                    <p class="text-muted mb-0">A tela foi separada por assunto para evitar mistura entre marca, PWA, SMTP, protecao e SEO.</p>
                                </div>
                                <ul class="nav nav-pills admin-settings-tabs" id="system-settings-tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="settings-branding-tab" data-bs-toggle="pill" data-bs-target="#settings-branding-pane" type="button" role="tab" aria-controls="settings-branding-pane" aria-selected="true">
                                            <i class="bi bi-palette"></i>
                                            <span>Marca</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="settings-pwa-tab" data-bs-toggle="pill" data-bs-target="#settings-pwa-pane" type="button" role="tab" aria-controls="settings-pwa-pane" aria-selected="false">
                                            <i class="bi bi-phone"></i>
                                            <span>PWA</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="settings-mail-tab" data-bs-toggle="pill" data-bs-target="#settings-mail-pane" type="button" role="tab" aria-controls="settings-mail-pane" aria-selected="false">
                                            <i class="bi bi-envelope"></i>
                                            <span>SMTP</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="settings-security-tab" data-bs-toggle="pill" data-bs-target="#settings-security-pane" type="button" role="tab" aria-controls="settings-security-pane" aria-selected="false">
                                            <i class="bi bi-shield-lock"></i>
                                            <span>Seguranca</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="settings-seo-tab" data-bs-toggle="pill" data-bs-target="#settings-seo-pane" type="button" role="tab" aria-controls="settings-seo-pane" aria-selected="false">
                                            <i class="bi bi-graph-up-arrow"></i>
                                            <span>SEO</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="settings-support-tab" data-bs-toggle="pill" data-bs-target="#settings-support-pane" type="button" role="tab" aria-controls="settings-support-pane" aria-selected="false">
                                            <i class="bi bi-whatsapp"></i>
                                            <span>Atendimento</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content admin-settings-tab-content" id="system-settings-tab-content">
                    <div class="tab-pane fade" id="settings-mail-pane" role="tabpanel" aria-labelledby="settings-mail-tab" tabindex="0">
                    <div class="card admin-table-card">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Comunicacao por e-mail</div>
                                <h3 class="card-title">SMTP, teste e templates padrao</h3>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3 admin-premium-form">
                                <div class="col-md-3 form-check ps-5 pt-4">
                                    <input type="checkbox" class="form-check-input" id="mail_enabled" name="mail_enabled" value="1" @checked(old('mail_enabled', $mailConfig['enabled']))>
                                    <label class="form-check-label" for="mail_enabled">Ativar SMTP personalizado</label>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="mail_mailer">Mailer</label>
                                    <select id="mail_mailer" name="mail_mailer" class="form-select">
                                        <option value="smtp" @selected(old('mail_mailer', $mailConfig['mailer']) === 'smtp')>SMTP</option>
                                        <option value="sendmail" @selected(old('mail_mailer', $mailConfig['mailer']) === 'sendmail')>Sendmail</option>
                                        <option value="log" @selected(old('mail_mailer', $mailConfig['mailer']) === 'log')>Log</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="mail_host">Servidor SMTP</label>
                                    <input id="mail_host" type="text" name="mail_host" class="form-control" value="{{ old('mail_host', $mailConfig['host']) }}" placeholder="smtp.seudominio.com">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="mail_port">Porta</label>
                                    <input id="mail_port" type="number" name="mail_port" class="form-control" value="{{ old('mail_port', $mailConfig['port']) }}" placeholder="587">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="mail_encryption">Criptografia</label>
                                    <select id="mail_encryption" name="mail_encryption" class="form-select">
                                        <option value="none" @selected(old('mail_encryption', $mailConfig['encryption']) === 'none')>Nenhuma</option>
                                        <option value="tls" @selected(old('mail_encryption', $mailConfig['encryption']) === 'tls')>TLS</option>
                                        <option value="ssl" @selected(old('mail_encryption', $mailConfig['encryption']) === 'ssl')>SSL</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="mail_username">Usuario SMTP</label>
                                    <input id="mail_username" type="text" name="mail_username" class="form-control" value="{{ old('mail_username', $mailConfig['username']) }}" placeholder="usuario@dominio.com">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="mail_password">Senha SMTP</label>
                                    <input id="mail_password" type="password" name="mail_password" class="form-control" value="{{ old('mail_password', $mailConfig['password']) }}" placeholder="********">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="mail_from_address">E-mail remetente</label>
                                    <input id="mail_from_address" type="email" name="mail_from_address" class="form-control" value="{{ old('mail_from_address', $mailConfig['from_address']) }}" placeholder="nao-responda@dominio.com">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="mail_from_name">Nome remetente</label>
                                    <input id="mail_from_name" type="text" name="mail_from_name" class="form-control" value="{{ old('mail_from_name', $mailConfig['from_name']) }}" placeholder="Nome do escritorio">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="smtp_test_email">E-mail para teste</label>
                                    <input id="smtp_test_email" type="email" class="form-control" placeholder="destino@dominio.com">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-primary w-100" id="smtp-test-button" data-test-url="{{ route('admin.system-settings.smtp-test') }}">
                                        <i class="bi bi-send me-1"></i>Testar configuracao SMTP
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="mail_template_reset_subject">Assunto redefinicao de senha</label>
                                    <input id="mail_template_reset_subject" type="text" name="mail_template_reset_subject" class="form-control mail-template-input" value="{{ old('mail_template_reset_subject', $mailConfig['template_reset_subject']) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="mail_template_generic_subject">Assunto padrao</label>
                                    <input id="mail_template_generic_subject" type="text" name="mail_template_generic_subject" class="form-control mail-template-input" value="{{ old('mail_template_generic_subject', $mailConfig['template_generic_subject']) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="mail_template_header">Cabecalho do e-mail</label>
                                    <textarea id="mail_template_header" name="mail_template_header" class="form-control mail-template-input" rows="3">{{ old('mail_template_header', $mailConfig['template_header']) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="mail_template_footer">Rodape do e-mail</label>
                                    <textarea id="mail_template_footer" name="mail_template_footer" class="form-control mail-template-input" rows="3">{{ old('mail_template_footer', $mailConfig['template_footer']) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="mail_template_reset_body">Corpo redefinicao de senha</label>
                                    <textarea id="mail_template_reset_body" name="mail_template_reset_body" class="form-control mail-template-input" rows="6">{{ old('mail_template_reset_body', $mailConfig['template_reset_body']) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="mail_template_generic_body">Corpo padrao de e-mails</label>
                                    <textarea id="mail_template_generic_body" name="mail_template_generic_body" class="form-control mail-template-input" rows="6">{{ old('mail_template_generic_body', $mailConfig['template_generic_body']) }}</textarea>
                                </div>
                                <div class="col-12">
                                    <div class="admin-premium-surface p-3">
                                        <div class="admin-card-kicker mb-2">Preview em tempo real</div>
                                        <div id="mail-template-preview" style="border:1px solid rgba(255,255,255,.12); border-radius:12px; padding:16px; background:rgba(15,22,38,.45); color:#d4d8e1;"></div>
                                        <small class="text-muted d-block mt-2">Variaveis: <code>{'{'}{name}{'}'}</code>, <code>{'{'}{email}{'}'}</code>, <code>{'{'}{app_name}{'}'}</code>, <code>{'{'}{from_name}{'}'}</code>, <code>{'{'}{reset_url}{'}'}</code>, <code>{'{'}{year}{'}'}</code>.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>

                    <div class="tab-pane fade show active" id="settings-branding-pane" role="tabpanel" aria-labelledby="settings-branding-tab" tabindex="0">
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
                                    <input id="logo" type="file" name="logo" class="form-control" data-filepond data-accepted="image/png,image/jpeg,image/webp,image/svg+xml" data-current-url="{{ $branding['logo_url'] ?: '' }}" data-current-name="{{ $branding['logo_path'] ? basename($branding['logo_path']) : '' }}">
                                    @if($branding['logo_url'])
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="remove_logo" name="remove_logo" value="1">
                                            <label class="form-check-label" for="remove_logo">Remover logo atual</label>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="favicon">Favicon</label>
                                    <input id="favicon" type="file" name="favicon" class="form-control" data-filepond data-accepted="image/x-icon,image/png,image/webp,image/svg+xml,image/jpeg" data-current-url="{{ $branding['favicon_url'] ?: '' }}" data-current-name="{{ $branding['favicon_path'] ? basename($branding['favicon_path']) : '' }}">
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
                    </div>

                    <div class="tab-pane fade" id="settings-pwa-pane" role="tabpanel" aria-labelledby="settings-pwa-tab" tabindex="0">
                    <div class="card admin-table-card">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Experiência em aplicativo</div>
                                <h3 class="card-title">PWA e instalação</h3>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4 admin-premium-form">
                                <div class="col-12">
                                    <div class="row g-3">
                                        <div class="col-md-3 form-check ps-5 pt-4">
                                            <input type="checkbox" class="form-check-input" id="pwa_enabled" name="pwa_enabled" value="1" @checked(old('pwa_enabled', $pwa['enabled']))>
                                            <label class="form-check-label" for="pwa_enabled">Ativar PWA no site</label>
                                        </div>
                                        <div class="col-md-3 form-check ps-5 pt-4">
                                            <input type="checkbox" class="form-check-input" id="pwa_installation_enabled" name="pwa_installation_enabled" value="1" @checked(old('pwa_installation_enabled', $pwa['installation_enabled']))>
                                            <label class="form-check-label" for="pwa_installation_enabled">Permitir instalação</label>
                                        </div>
                                        <div class="col-md-3 form-check ps-5 pt-4">
                                            <input type="checkbox" class="form-check-input" id="pwa_install_prompt_enabled" name="pwa_install_prompt_enabled" value="1" @checked(old('pwa_install_prompt_enabled', $pwa['install_prompt_enabled']))>
                                            <label class="form-check-label" for="pwa_install_prompt_enabled">Exibir pop-up de anúncio</label>
                                        </div>
                                        <div class="col-md-3 form-check ps-5 pt-4">
                                            <input type="checkbox" class="form-check-input" id="pwa_footer_install_enabled" name="pwa_footer_install_enabled" value="1" @checked(old('pwa_footer_install_enabled', $pwa['footer_install_enabled']))>
                                            <label class="form-check-label" for="pwa_footer_install_enabled">Exibir botão no rodapé</label>
                                        </div>
                                        <div class="col-md-3 form-check ps-5">
                                            <input type="checkbox" class="form-check-input" id="pwa_mobile_install_enabled" name="pwa_mobile_install_enabled" value="1" @checked(old('pwa_mobile_install_enabled', $pwa['mobile_install_enabled']))>
                                            <label class="form-check-label" for="pwa_mobile_install_enabled">Exibir botão no menu móvel</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="pwa_app_name">Nome do aplicativo</label>
                                    <input id="pwa_app_name" type="text" name="pwa_app_name" class="form-control" maxlength="120" value="{{ old('pwa_app_name', $pwa['app_name']) }}" placeholder="Nome do aplicativo">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="pwa_short_name">Nome curto</label>
                                    <input id="pwa_short_name" type="text" name="pwa_short_name" class="form-control" maxlength="32" value="{{ old('pwa_short_name', $pwa['short_name']) }}" placeholder="Nome curto">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="pwa_display">Modo de exibição</label>
                                    <select id="pwa_display" name="pwa_display" class="form-select">
                                        @foreach($pwaDisplayOptions as $value => $label)
                                            <option value="{{ $value }}" @selected(old('pwa_display', $pwa['display']) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="pwa_start_path">URL inicial</label>
                                    <input id="pwa_start_path" type="text" name="pwa_start_path" class="form-control" maxlength="255" value="{{ old('pwa_start_path', $pwa['start_path']) }}" placeholder="/portal-cliente">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="pwa_scope">Escopo</label>
                                    <input id="pwa_scope" type="text" name="pwa_scope" class="form-control" maxlength="255" value="{{ old('pwa_scope', $pwa['scope']) }}" placeholder="/">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="pwa_orientation">Orientação</label>
                                    <select id="pwa_orientation" name="pwa_orientation" class="form-select">
                                        @foreach($pwaOrientationOptions as $value => $label)
                                            <option value="{{ $value }}" @selected(old('pwa_orientation', $pwa['orientation']) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="pwa_description">Descrição do aplicativo</label>
                                    <textarea id="pwa_description" name="pwa_description" class="form-control" rows="3" maxlength="255" placeholder="Descreva a experiência do aplicativo">{{ old('pwa_description', $pwa['description']) }}</textarea>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label" for="pwa_theme_color">Cor principal</label>
                                    <div class="input-group">
                                        <input id="pwa_theme_color_picker" type="color" class="form-control form-control-color" value="{{ old('pwa_theme_color', $pwa['theme_color']) }}" oninput="document.getElementById('pwa_theme_color').value=this.value.toUpperCase()">
                                        <input id="pwa_theme_color" type="text" name="pwa_theme_color" class="form-control text-uppercase" value="{{ old('pwa_theme_color', $pwa['theme_color']) }}" placeholder="#0B0C10">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="pwa_background_color">Cor de fundo</label>
                                    <div class="input-group">
                                        <input id="pwa_background_color_picker" type="color" class="form-control form-control-color" value="{{ old('pwa_background_color', $pwa['background_color']) }}" oninput="document.getElementById('pwa_background_color').value=this.value.toUpperCase()">
                                        <input id="pwa_background_color" type="text" name="pwa_background_color" class="form-control text-uppercase" value="{{ old('pwa_background_color', $pwa['background_color']) }}" placeholder="#0B0C10">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="pwa_icon_192">Ícone 192x192</label>
                                    <input id="pwa_icon_192" type="file" name="pwa_icon_192" class="form-control" data-filepond data-accepted="image/png,image/jpeg,image/webp" data-current-url="{{ $pwa['icon_192_url'] ?: '' }}" data-current-name="{{ $pwa['icon_192_path'] ? basename($pwa['icon_192_path']) : '' }}">
                                    @if($pwa['icon_192_url'])
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="remove_pwa_icon_192" name="remove_pwa_icon_192" value="1">
                                            <label class="form-check-label" for="remove_pwa_icon_192">Remover ícone 192 atual</label>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="pwa_icon_512">Ícone 512x512</label>
                                    <input id="pwa_icon_512" type="file" name="pwa_icon_512" class="form-control" data-filepond data-accepted="image/png,image/jpeg,image/webp" data-current-url="{{ $pwa['icon_512_url'] ?: '' }}" data-current-name="{{ $pwa['icon_512_path'] ? basename($pwa['icon_512_path']) : '' }}">
                                    @if($pwa['icon_512_url'])
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="remove_pwa_icon_512" name="remove_pwa_icon_512" value="1">
                                            <label class="form-check-label" for="remove_pwa_icon_512">Remover ícone 512 atual</label>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-12">
                                    <div class="admin-premium-surface p-3">
                                        <div class="admin-card-kicker mb-3">Convite de instalação</div>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label" for="pwa_popup_badge">Etiqueta do pop-up</label>
                                                <input id="pwa_popup_badge" type="text" name="pwa_popup_badge" class="form-control" maxlength="80" value="{{ old('pwa_popup_badge', $pwa['popup_badge']) }}" placeholder="Aplicativo disponível">
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label" for="pwa_popup_title">Título do pop-up</label>
                                                <input id="pwa_popup_title" type="text" name="pwa_popup_title" class="form-control" maxlength="120" value="{{ old('pwa_popup_title', $pwa['popup_title']) }}" placeholder="Instale o app do escritório">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label" for="pwa_popup_description">Descrição do pop-up</label>
                                                <textarea id="pwa_popup_description" name="pwa_popup_description" class="form-control" rows="3" maxlength="255" placeholder="Explique por que vale instalar">{{ old('pwa_popup_description', $pwa['popup_description']) }}</textarea>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="pwa_popup_primary_label">Botão principal</label>
                                                <input id="pwa_popup_primary_label" type="text" name="pwa_popup_primary_label" class="form-control" maxlength="60" value="{{ old('pwa_popup_primary_label', $pwa['popup_primary_label']) }}" placeholder="Instalar agora">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="pwa_popup_secondary_label">Botão secundário</label>
                                                <input id="pwa_popup_secondary_label" type="text" name="pwa_popup_secondary_label" class="form-control" maxlength="60" value="{{ old('pwa_popup_secondary_label', $pwa['popup_secondary_label']) }}" placeholder="Agora não">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="pwa_footer_label">Texto do botão no rodapé</label>
                                                <input id="pwa_footer_label" type="text" name="pwa_footer_label" class="form-control" maxlength="60" value="{{ old('pwa_footer_label', $pwa['footer_label']) }}" placeholder="Instalar aplicativo">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="pwa_mobile_menu_label">Texto do botão no menu móvel</label>
                                                <input id="pwa_mobile_menu_label" type="text" name="pwa_mobile_menu_label" class="form-control" maxlength="60" value="{{ old('pwa_mobile_menu_label', $pwa['mobile_menu_label']) }}" placeholder="Instalar aplicativo">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="admin-premium-surface p-3">
                                        <div class="admin-card-kicker mb-3">Tela offline</div>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label" for="pwa_offline_title">Título offline</label>
                                                <input id="pwa_offline_title" type="text" name="pwa_offline_title" class="form-control" maxlength="120" value="{{ old('pwa_offline_title', $pwa['offline_title']) }}" placeholder="Você está offline.">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="pwa_offline_button_label">Botão offline</label>
                                                <input id="pwa_offline_button_label" type="text" name="pwa_offline_button_label" class="form-control" maxlength="60" value="{{ old('pwa_offline_button_label', $pwa['offline_button_label']) }}" placeholder="Tentar novamente">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label" for="pwa_offline_message">Mensagem offline</label>
                                                <textarea id="pwa_offline_message" name="pwa_offline_message" class="form-control" rows="3" maxlength="255" placeholder="Explique o que acontece quando a conexão voltar">{{ old('pwa_offline_message', $pwa['offline_message']) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>

                    <div class="tab-pane fade" id="settings-security-pane" role="tabpanel" aria-labelledby="settings-security-tab" tabindex="0">
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
                    </div>
                    </div>

                    <div class="tab-pane fade" id="settings-seo-pane" role="tabpanel" aria-labelledby="settings-seo-tab" tabindex="0">
                    <div class="card admin-table-card">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Otimização de Busca</div>
                                <h3 class="card-title">SEO e Hashtags Persistentes</h3>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4 admin-premium-form">
                                <div class="col-md-6">
                                    <label class="form-label" for="seo_title_suffix">Sufixo de Título</label>
                                    <input id="seo_title_suffix" type="text" name="seo_title_suffix" class="form-control" value="{{ old('seo_title_suffix', $seo['title_suffix']) }}" placeholder="Ex: | Pujani Advogados">
                                    <small class="text-muted mt-1 d-block">Aparece após o nome de cada página no navegador.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="seo_author">Autor do Site</label>
                                    <input id="seo_author" type="text" name="seo_author" class="form-control" value="{{ old('seo_author', $seo['author']) }}" placeholder="Ex: Rodrigo Pujani">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="seo_meta_description">Meta Descrição Global</label>
                                    <textarea id="seo_meta_description" name="seo_meta_description" class="form-control" rows="3" placeholder="Descreva seu escritório em até 160 caracteres...">{{ old('seo_meta_description', $seo['meta_description']) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="seo_meta_keywords">Palavras-chave</label>
                                    <textarea id="seo_meta_keywords" name="seo_meta_keywords" class="form-control" rows="3" placeholder="advogado, jurídico, porto alegre, etc...">{{ old('seo_meta_keywords', $seo['meta_keywords']) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="seo_hashtags">Hashtags Persistentes</label>
                                    <textarea id="seo_hashtags" name="seo_hashtags" class="form-control" rows="3" placeholder="#pujani #advocacia #justiça">{{ old('seo_hashtags', $seo['hashtags']) }}</textarea>
                                    <small class="text-muted mt-1 d-block">Utilizadas para alavancar o site em redes sociais e buscas orgânicas.</small>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label" for="seo_og_image_path">Imagem Redes Sociais (OG Image)</label>
                                    <div class="input-group">
                                        <input id="seo_og_image_path" type="text" name="seo_og_image_path" class="form-control" value="{{ old('seo_og_image_path', $seo['og_image_path']) }}" placeholder="Caminho da imagem ou URL">
                                        <button type="button" class="btn btn-outline-secondary" onclick="window.AdminUI.openAssetManager('seo_og_image_path')">
                                            <i class="bi bi-folder2-open"></i>
                                        </button>
                                    </div>
                                    @if($seo['og_image_url'])
                                        <div class="mt-2">
                                            <img src="{{ $seo['og_image_url'] }}" alt="Preview SEO" class="rounded border" style="height: 60px; object-fit: cover;">
                                        </div>
                                    @endif
                                </div>
                                <hr class="my-4 opacity-5">
                                <div class="col-md-4">
                                    <label class="form-label" for="seo_google_analytics_id">Google Analytics ID</label>
                                    <input id="seo_google_analytics_id" type="text" name="seo_google_analytics_id" class="form-control" value="{{ old('seo_google_analytics_id', $seo['google_analytics_id']) }}" placeholder="G-XXXXXXXX">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="seo_google_site_verification">Google Search Console</label>
                                    <input id="seo_google_site_verification" type="text" name="seo_google_site_verification" class="form-control" value="{{ old('seo_google_site_verification', $seo['google_site_verification']) }}" placeholder="Código de verificação">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="seo_bing_site_verification">Bing Webmaster</label>
                                    <input id="seo_bing_site_verification" type="text" name="seo_bing_site_verification" class="form-control" value="{{ old('seo_bing_site_verification', $seo['bing_site_verification']) }}" placeholder="Código de verificação">
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>

                    <div class="tab-pane fade" id="settings-support-pane" role="tabpanel" aria-labelledby="settings-support-tab" tabindex="0">
                    <div class="card admin-table-card">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Suporte e Atendimento</div>
                                <h3 class="card-title">WhatsApp Multinível</h3>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3 admin-premium-form">
                                <div class="col-md-4 form-check ps-5 pt-4">
                                    <input type="checkbox" class="form-check-input" id="whatsapp_multiple_support" name="whatsapp_multiple_support" value="1" @checked(old('whatsapp_multiple_support', setting('site.whatsapp_multiple_support') == '1'))>
                                    <label class="form-check-label" for="whatsapp_multiple_support">Ativar seleção de especialistas</label>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="whatsapp_selection_title">Título da caixa</label>
                                    <input id="whatsapp_selection_title" type="text" name="whatsapp_selection_title" class="form-control" value="{{ old('whatsapp_selection_title', setting('site.whatsapp_selection_title', 'Escolha um especialista')) }}" placeholder="Ex: Fale com nossa equipe">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="whatsapp_selection_subtitle">Subtítulo da caixa</label>
                                    <input id="whatsapp_selection_subtitle" type="text" name="whatsapp_selection_subtitle" class="form-control" value="{{ old('whatsapp_selection_subtitle', setting('site.whatsapp_selection_subtitle', 'Selecione com quem deseja falar pelo WhatsApp:')) }}" placeholder="Ex: Clique no advogado desejado">
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-info border-0 bg-opacity-10 mb-0">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Quando ativo, o botão do WhatsApp no site exibirá uma caixa luxuosa listando todos os <strong>membros da equipe ativos</strong> que possuam número de WhatsApp cadastrado.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>

                    <div class="card admin-table-card admin-settings-submit-card">
                        <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 p-4">
                            <div>
                                <div class="admin-card-kicker mb-1">Aplicacao das alteracoes</div>
                                <strong>Salvamento unificado</strong>
                                <p class="text-muted mb-0">As guias organizam a experiencia visual, mas o envio continua centralizado para preservar a consistencia do cadastro.</p>
                            </div>
                            <button type="submit" class="btn btn-primary admin-settings-save-button">
                                <i class="bi bi-save me-1"></i>Salvar configuracoes
                            </button>
                        </div>
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

                    <div class="admin-system-preview-card">
                        <div class="admin-system-preview-head">
                            <span class="admin-system-preview-mark"><i class="bi bi-phone"></i></span>
                            <div class="admin-system-preview-copy">
                                <span>PWA do site</span>
                                <strong>{{ $pwa['app_name'] }}</strong>
                                <p>Manifesto, service worker, instalação, pop-up de anúncio, botão do rodapé, botão do menu móvel e tela offline agora ficam no mesmo ponto de gestão.</p>
                            </div>
                        </div>

                        <div class="admin-brand-preview-grid mt-3">
                            <div class="admin-brand-preview-card">
                                <span>Ícone 192</span>
                                <div class="admin-brand-preview-image mt-2">
                                    @if($pwa['icon_192_url'])
                                        <img src="{{ $pwa['icon_192_url'] }}" alt="Ícone 192 do PWA">
                                    @else
                                        <span class="admin-brand-preview-empty">{{ $branding['brand_short_name'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="admin-brand-preview-card">
                                <span>Ícone 512</span>
                                <div class="admin-brand-preview-image mt-2">
                                    @if($pwa['icon_512_url'])
                                        <img src="{{ $pwa['icon_512_url'] }}" alt="Ícone 512 do PWA">
                                    @else
                                        <span class="admin-brand-preview-empty">{{ $branding['brand_short_name'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="admin-system-assets-grid mt-3">
                            <div class="admin-brand-preview-card">
                                <span>Status do PWA</span>
                                <strong class="mt-2">{{ $pwa['enabled'] ? 'Ativo' : 'Desativado' }}</strong>
                                <small>{{ $pwa['installation_enabled'] ? 'Instalação permitida' : 'Instalação bloqueada' }}</small>
                            </div>
                            <div class="admin-brand-preview-card">
                                <span>Convite de instalação</span>
                                <strong class="mt-2">{{ $pwa['install_prompt_enabled'] ? 'Pop-up ativo' : 'Pop-up oculto' }}</strong>
                                <small>Rodapé: {{ $pwa['footer_install_enabled'] ? 'ativo' : 'oculto' }} · Menu móvel: {{ $pwa['mobile_install_enabled'] ? 'ativo' : 'oculto' }}</small>
                            </div>
                            <div class="admin-brand-preview-card">
                                <span>Manifesto</span>
                                <strong class="mt-2">{{ $pwa['display'] }}</strong>
                                <small>Início em {{ $pwa['start_path'] }} · escopo {{ $pwa['scope'] }}</small>
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

@push('styles')
<style>
    .admin-settings-toolbar {
        display: grid;
        gap: 1.25rem;
    }

    .admin-settings-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
    }

    .admin-settings-tabs .nav-link {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        border-radius: 12px;
        padding: .85rem 1rem;
        color: #b7c0d4;
        background: rgba(12, 18, 31, .55);
        border: 1px solid rgba(255, 255, 255, .08);
        font-weight: 700;
    }

    .admin-settings-tabs .nav-link.active {
        background: linear-gradient(135deg, rgba(196, 154, 60, .22), rgba(196, 154, 60, .08));
        color: #f7e6b3;
        border-color: rgba(196, 154, 60, .45);
        box-shadow: 0 12px 24px rgba(10, 14, 24, .18);
    }

    .admin-settings-tab-content > .tab-pane {
        display: none;
    }

    .admin-settings-tab-content > .active {
        display: block;
    }

    .admin-settings-submit-card {
        position: sticky;
        bottom: 1rem;
        z-index: 20;
    }

    .admin-settings-save-button {
        min-width: 220px;
    }

    @media (min-width: 992px) {
        .admin-settings-toolbar {
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.8fr);
            align-items: start;
        }

        .admin-settings-tabs {
            justify-content: flex-end;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabStorageKey = 'admin-system-settings-active-tab';
    const tabButtons = Array.from(document.querySelectorAll('#system-settings-tabs [data-bs-toggle="pill"]'));
    const preview = document.getElementById('mail-template-preview');
    const testButton = document.getElementById('smtp-test-button');
    const testEmail = document.getElementById('smtp_test_email');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const activateTab = (selector) => {
        const button = tabButtons.find((item) => item.dataset.bsTarget === selector);
        if (!button || !window.bootstrap?.Tab) {
            return;
        }

        window.localStorage.setItem(tabStorageKey, selector);
        window.history.replaceState({}, '', selector);
        bootstrap.Tab.getOrCreateInstance(button).show();
    };

    tabButtons.forEach((button) => {
        button.addEventListener('shown.bs.tab', () => {
            const target = button.dataset.bsTarget || '';
            if (target) {
                window.localStorage.setItem(tabStorageKey, target);
                window.history.replaceState({}, '', target);
            }
        });
    });

    const invalidField = document.querySelector('.is-invalid, .invalid-feedback');
    const invalidPane = invalidField?.closest('.tab-pane');
    const preferredTab = invalidPane?.id ? `#${invalidPane.id}` : (window.location.hash || window.localStorage.getItem(tabStorageKey) || '#settings-branding-pane');

    if (preferredTab) {
        activateTab(preferredTab);
    }

    const values = () => ({
        name: 'Cliente de Exemplo',
        email: 'cliente@exemplo.com',
        app_name: '{{ addslashes(config('app.name')) }}',
        from_name: document.getElementById('mail_from_name')?.value || 'Equipe',
        reset_url: '{{ addslashes(url('/reset-password/token-exemplo?email=cliente@exemplo.com')) }}',
        year: String(new Date().getFullYear()),
    });

    const compile = (text, vars) => {
        let output = String(text || '');
        Object.entries(vars).forEach(([key, val]) => {
            output = output.split('@{{' + key + '}}').join(String(val ?? ''));
        });
        return output.replace(/\{\{\s*[^}]+\s*\}\}/g, '');
    };

    const renderPreview = () => {
        if (!preview) return;
        const vars = values();
        const subject = compile(document.getElementById('mail_template_reset_subject')?.value || '', vars);
        const header = compile(document.getElementById('mail_template_header')?.value || '', vars);
        const body = compile(document.getElementById('mail_template_reset_body')?.value || '', vars);
        const footer = compile(document.getElementById('mail_template_footer')?.value || '', vars);

        preview.innerHTML = `
            <div style="font-size:13px; color:#9ca3b5;">Assunto</div>
            <div style="font-size:18px; font-weight:700; margin-bottom:10px;">${subject || '(sem assunto)'}</div>
            <div style="margin-bottom:10px; white-space:pre-line;">${header}</div>
            <div style="margin-bottom:10px; white-space:pre-line;">${body}</div>
            <div style="margin-bottom:8px;">
                <a href="#" style="display:inline-block; background:#c49a3c; color:#10131a; padding:8px 12px; border-radius:8px; font-weight:700; text-decoration:none;">Redefinir senha</a>
            </div>
            <div style="border-top:1px solid rgba(255,255,255,.1); padding-top:10px; white-space:pre-line; color:#a9b0bf;">${footer}</div>
        `;
    };

    document.querySelectorAll('.mail-template-input, #mail_from_name').forEach((input) => {
        input.addEventListener('input', renderPreview);
    });
    renderPreview();

    if (testButton && testEmail) {
        testButton.addEventListener('click', async () => {
            const email = (testEmail.value || '').trim();
            if (!email) {
                window.toastr?.warning('Informe um e-mail para teste SMTP.');
                return;
            }

            testButton.disabled = true;
            try {
                const response = await fetch(testButton.dataset.testUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        test_email: email,
                        mailer: document.getElementById('mail_mailer')?.value || 'smtp',
                        host: document.getElementById('mail_host')?.value || '',
                        port: document.getElementById('mail_port')?.value || '',
                        encryption: document.getElementById('mail_encryption')?.value || 'tls',
                        username: document.getElementById('mail_username')?.value || '',
                        password: document.getElementById('mail_password')?.value || '',
                        from_address: document.getElementById('mail_from_address')?.value || '',
                        from_name: document.getElementById('mail_from_name')?.value || '',
                    }),
                });

                const payload = await response.json();
                if (!response.ok) {
                    window.toastr?.error(payload.message || 'Falha no teste SMTP.');
                    return;
                }

                window.toastr?.success(payload.message || 'Teste SMTP enviado.');
            } catch (error) {
                window.toastr?.error('Nao foi possivel testar o SMTP agora.');
            } finally {
                testButton.disabled = false;
            }
        });
    }
});
</script>
@endpush
