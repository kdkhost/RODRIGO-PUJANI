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
