<div class="card admin-table-card">
    <div class="card-header">
        <div>
            <div class="admin-card-kicker">Comunicacao por e-mail</div>
            <h3 class="card-title">SMTP, testes e templates personalizados</h3>
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

            <div class="col-12">
                <div class="admin-premium-surface p-3">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                        <div>
                            <div class="admin-card-kicker mb-1">Variaveis clicaveis</div>
                            <small class="text-muted">Clique para inserir no assunto, cabecalho, corpo ou rodape do template.</small>
                        </div>
                        <div class="admin-mail-token-list">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-mail-token="@{{name}}">Nome</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-mail-token="@{{email}}">E-mail</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-mail-token="@{{app_name}}">App</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-mail-token="@{{from_name}}">Remetente</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-mail-token="@{{reset_url}}">URL de reset</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-mail-token="@{{year}}">Ano</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="mail_template_reset_subject">Assunto redefinicao de senha</label>
                <input id="mail_template_reset_subject" type="text" name="mail_template_reset_subject" class="form-control mail-template-input" value="{{ old('mail_template_reset_subject', $mailConfig['template_reset_subject']) }}" placeholder="Assunto do e-mail de reset">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="mail_template_generic_subject">Assunto padrao</label>
                <input id="mail_template_generic_subject" type="text" name="mail_template_generic_subject" class="form-control mail-template-input" value="{{ old('mail_template_generic_subject', $mailConfig['template_generic_subject']) }}" placeholder="Assunto padrao do sistema">
            </div>
            <div class="col-md-4 form-check ps-5 pt-4">
                <input type="checkbox" class="form-check-input" id="mail_template_show_logo" name="mail_template_show_logo" value="1" @checked(old('mail_template_show_logo', $mailTheme['show_logo']))>
                <label class="form-check-label" for="mail_template_show_logo">Exibir logo do sistema no e-mail</label>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="mail_template_layout">Modelo visual</label>
                <select id="mail_template_layout" name="mail_template_layout" class="form-select mail-template-input">
                    @foreach($mailLayoutOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('mail_template_layout', $mailTheme['layout']) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="mail_template_font_family">Fonte</label>
                <select id="mail_template_font_family" name="mail_template_font_family" class="form-select mail-template-input">
                    @foreach($mailFontOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('mail_template_font_family', $mailTheme['font_family']) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="mail_template_header">Cabecalho do e-mail</label>
                <textarea id="mail_template_header" name="mail_template_header" class="form-control mail-template-input" rows="3" data-editor="summernote" data-editor-height="220">{{ old('mail_template_header', $mailConfig['template_header']) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="mail_template_footer">Rodape do e-mail</label>
                <textarea id="mail_template_footer" name="mail_template_footer" class="form-control mail-template-input" rows="3" data-editor="summernote" data-editor-height="220">{{ old('mail_template_footer', $mailConfig['template_footer']) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="mail_template_reset_body">Corpo redefinicao de senha</label>
                <textarea id="mail_template_reset_body" name="mail_template_reset_body" class="form-control mail-template-input" rows="6" data-editor="summernote" data-editor-height="280">{{ old('mail_template_reset_body', $mailConfig['template_reset_body']) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="mail_template_generic_body">Corpo padrao de e-mails</label>
                <textarea id="mail_template_generic_body" name="mail_template_generic_body" class="form-control mail-template-input" rows="6" data-editor="summernote" data-editor-height="280">{{ old('mail_template_generic_body', $mailConfig['template_generic_body']) }}</textarea>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="mail_template_background_color">Topo</label>
                <div class="input-group">
                    <input type="color" class="form-control form-control-color" value="{{ old('mail_template_background_color', $mailTheme['background_color']) }}" oninput="document.getElementById('mail_template_background_color').value=this.value.toUpperCase(); document.getElementById('mail_template_background_color').dispatchEvent(new Event('input', { bubbles: true }));">
                    <input id="mail_template_background_color" type="text" name="mail_template_background_color" class="form-control text-uppercase mail-template-input" value="{{ old('mail_template_background_color', $mailTheme['background_color']) }}">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="mail_template_body_background_color">Fundo externo</label>
                <div class="input-group">
                    <input type="color" class="form-control form-control-color" value="{{ old('mail_template_body_background_color', $mailTheme['body_background_color']) }}" oninput="document.getElementById('mail_template_body_background_color').value=this.value.toUpperCase(); document.getElementById('mail_template_body_background_color').dispatchEvent(new Event('input', { bubbles: true }));">
                    <input id="mail_template_body_background_color" type="text" name="mail_template_body_background_color" class="form-control text-uppercase mail-template-input" value="{{ old('mail_template_body_background_color', $mailTheme['body_background_color']) }}">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="mail_template_card_background_color">Card</label>
                <div class="input-group">
                    <input type="color" class="form-control form-control-color" value="{{ old('mail_template_card_background_color', $mailTheme['card_background_color']) }}" oninput="document.getElementById('mail_template_card_background_color').value=this.value.toUpperCase(); document.getElementById('mail_template_card_background_color').dispatchEvent(new Event('input', { bubbles: true }));">
                    <input id="mail_template_card_background_color" type="text" name="mail_template_card_background_color" class="form-control text-uppercase mail-template-input" value="{{ old('mail_template_card_background_color', $mailTheme['card_background_color']) }}">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="mail_template_border_color">Borda</label>
                <div class="input-group">
                    <input type="color" class="form-control form-control-color" value="{{ old('mail_template_border_color', $mailTheme['border_color']) }}" oninput="document.getElementById('mail_template_border_color').value=this.value.toUpperCase(); document.getElementById('mail_template_border_color').dispatchEvent(new Event('input', { bubbles: true }));">
                    <input id="mail_template_border_color" type="text" name="mail_template_border_color" class="form-control text-uppercase mail-template-input" value="{{ old('mail_template_border_color', $mailTheme['border_color']) }}">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="mail_template_heading_color">Titulos</label>
                <div class="input-group">
                    <input type="color" class="form-control form-control-color" value="{{ old('mail_template_heading_color', $mailTheme['heading_color']) }}" oninput="document.getElementById('mail_template_heading_color').value=this.value.toUpperCase(); document.getElementById('mail_template_heading_color').dispatchEvent(new Event('input', { bubbles: true }));">
                    <input id="mail_template_heading_color" type="text" name="mail_template_heading_color" class="form-control text-uppercase mail-template-input" value="{{ old('mail_template_heading_color', $mailTheme['heading_color']) }}">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="mail_template_text_color">Texto</label>
                <div class="input-group">
                    <input type="color" class="form-control form-control-color" value="{{ old('mail_template_text_color', $mailTheme['text_color']) }}" oninput="document.getElementById('mail_template_text_color').value=this.value.toUpperCase(); document.getElementById('mail_template_text_color').dispatchEvent(new Event('input', { bubbles: true }));">
                    <input id="mail_template_text_color" type="text" name="mail_template_text_color" class="form-control text-uppercase mail-template-input" value="{{ old('mail_template_text_color', $mailTheme['text_color']) }}">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="mail_template_muted_color">Texto auxiliar</label>
                <div class="input-group">
                    <input type="color" class="form-control form-control-color" value="{{ old('mail_template_muted_color', $mailTheme['muted_color']) }}" oninput="document.getElementById('mail_template_muted_color').value=this.value.toUpperCase(); document.getElementById('mail_template_muted_color').dispatchEvent(new Event('input', { bubbles: true }));">
                    <input id="mail_template_muted_color" type="text" name="mail_template_muted_color" class="form-control text-uppercase mail-template-input" value="{{ old('mail_template_muted_color', $mailTheme['muted_color']) }}">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="mail_template_button_background_color">Botao</label>
                <div class="input-group">
                    <input type="color" class="form-control form-control-color" value="{{ old('mail_template_button_background_color', $mailTheme['button_background_color']) }}" oninput="document.getElementById('mail_template_button_background_color').value=this.value.toUpperCase(); document.getElementById('mail_template_button_background_color').dispatchEvent(new Event('input', { bubbles: true }));">
                    <input id="mail_template_button_background_color" type="text" name="mail_template_button_background_color" class="form-control text-uppercase mail-template-input" value="{{ old('mail_template_button_background_color', $mailTheme['button_background_color']) }}">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="mail_template_button_text_color">Texto do botao</label>
                <div class="input-group">
                    <input type="color" class="form-control form-control-color" value="{{ old('mail_template_button_text_color', $mailTheme['button_text_color']) }}" oninput="document.getElementById('mail_template_button_text_color').value=this.value.toUpperCase(); document.getElementById('mail_template_button_text_color').dispatchEvent(new Event('input', { bubbles: true }));">
                    <input id="mail_template_button_text_color" type="text" name="mail_template_button_text_color" class="form-control text-uppercase mail-template-input" value="{{ old('mail_template_button_text_color', $mailTheme['button_text_color']) }}">
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="mail_template_custom_css">CSS adicional</label>
                <textarea id="mail_template_custom_css" name="mail_template_custom_css" class="form-control mail-template-input" rows="5" placeholder=".preview-card { border-radius: 24px; }">{{ old('mail_template_custom_css', $mailTheme['custom_css']) }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="card admin-table-card">
    <div class="card-header">
        <div>
            <div class="admin-card-kicker">Preview responsivo</div>
            <h3 class="card-title">Visualizacao do template ativo</h3>
        </div>
    </div>
    <div class="card-body p-4">
        <div id="mail-template-preview"></div>
    </div>
</div>
