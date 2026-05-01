<div class="card admin-table-card">
    <div class="card-header">
        <div>
            <div class="admin-card-kicker">Experiencia em aplicativo</div>
            <h3 class="card-title">PWA e instalacao</h3>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-4 admin-premium-form">
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-md-3 form-check ps-5 pt-4"><input type="checkbox" class="form-check-input" id="pwa_enabled" name="pwa_enabled" value="1" @checked(old('pwa_enabled', $pwa['enabled']))><label class="form-check-label" for="pwa_enabled">Ativar PWA no site</label></div>
                    <div class="col-md-3 form-check ps-5 pt-4"><input type="checkbox" class="form-check-input" id="pwa_installation_enabled" name="pwa_installation_enabled" value="1" @checked(old('pwa_installation_enabled', $pwa['installation_enabled']))><label class="form-check-label" for="pwa_installation_enabled">Permitir instalacao</label></div>
                    <div class="col-md-3 form-check ps-5 pt-4"><input type="checkbox" class="form-check-input" id="pwa_install_prompt_enabled" name="pwa_install_prompt_enabled" value="1" @checked(old('pwa_install_prompt_enabled', $pwa['install_prompt_enabled']))><label class="form-check-label" for="pwa_install_prompt_enabled">Exibir pop-up</label></div>
                    <div class="col-md-3 form-check ps-5 pt-4"><input type="checkbox" class="form-check-input" id="pwa_footer_install_enabled" name="pwa_footer_install_enabled" value="1" @checked(old('pwa_footer_install_enabled', $pwa['footer_install_enabled']))><label class="form-check-label" for="pwa_footer_install_enabled">Botao no rodape</label></div>
                    <div class="col-md-3 form-check ps-5"><input type="checkbox" class="form-check-input" id="pwa_mobile_install_enabled" name="pwa_mobile_install_enabled" value="1" @checked(old('pwa_mobile_install_enabled', $pwa['mobile_install_enabled']))><label class="form-check-label" for="pwa_mobile_install_enabled">Botao no menu movel</label></div>
                </div>
            </div>

            <div class="col-md-6"><label class="form-label" for="pwa_app_name">Nome do aplicativo</label><input id="pwa_app_name" type="text" name="pwa_app_name" class="form-control" maxlength="120" value="{{ old('pwa_app_name', $pwa['app_name']) }}"></div>
            <div class="col-md-3"><label class="form-label" for="pwa_short_name">Nome curto</label><input id="pwa_short_name" type="text" name="pwa_short_name" class="form-control" maxlength="32" value="{{ old('pwa_short_name', $pwa['short_name']) }}"></div>
            <div class="col-md-3"><label class="form-label" for="pwa_display">Modo de exibicao</label><select id="pwa_display" name="pwa_display" class="form-select">@foreach($pwaDisplayOptions as $value => $label)<option value="{{ $value }}" @selected(old('pwa_display', $pwa['display']) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label" for="pwa_start_path">URL inicial</label><input id="pwa_start_path" type="text" name="pwa_start_path" class="form-control" maxlength="255" value="{{ old('pwa_start_path', $pwa['start_path']) }}"></div>
            <div class="col-md-3"><label class="form-label" for="pwa_scope">Escopo</label><input id="pwa_scope" type="text" name="pwa_scope" class="form-control" maxlength="255" value="{{ old('pwa_scope', $pwa['scope']) }}"></div>
            <div class="col-md-3"><label class="form-label" for="pwa_orientation">Orientacao</label><select id="pwa_orientation" name="pwa_orientation" class="form-select">@foreach($pwaOrientationOptions as $value => $label)<option value="{{ $value }}" @selected(old('pwa_orientation', $pwa['orientation']) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label" for="pwa_description">Descricao do aplicativo</label><textarea id="pwa_description" name="pwa_description" class="form-control" rows="3" maxlength="255">{{ old('pwa_description', $pwa['description']) }}</textarea></div>

            <div class="col-md-3"><label class="form-label" for="pwa_theme_color">Cor principal</label><div class="input-group"><input id="pwa_theme_color_picker" type="color" class="form-control form-control-color" value="{{ old('pwa_theme_color', $pwa['theme_color']) }}" oninput="document.getElementById('pwa_theme_color').value=this.value.toUpperCase()"><input id="pwa_theme_color" type="text" name="pwa_theme_color" class="form-control text-uppercase" value="{{ old('pwa_theme_color', $pwa['theme_color']) }}"></div></div>
            <div class="col-md-3"><label class="form-label" for="pwa_background_color">Cor de fundo</label><div class="input-group"><input id="pwa_background_color_picker" type="color" class="form-control form-control-color" value="{{ old('pwa_background_color', $pwa['background_color']) }}" oninput="document.getElementById('pwa_background_color').value=this.value.toUpperCase()"><input id="pwa_background_color" type="text" name="pwa_background_color" class="form-control text-uppercase" value="{{ old('pwa_background_color', $pwa['background_color']) }}"></div></div>
            <div class="col-md-3"><label class="form-label" for="pwa_icon_192">Icone 192x192</label><input id="pwa_icon_192" type="file" name="pwa_icon_192" class="form-control" data-filepond data-accepted="image/png,image/jpeg,image/webp" data-current-url="{{ $pwa['icon_192_url'] ?: '' }}" data-current-name="{{ $pwa['icon_192_path'] ? basename($pwa['icon_192_path']) : '' }}"></div>
            <div class="col-md-3"><label class="form-label" for="pwa_icon_512">Icone 512x512</label><input id="pwa_icon_512" type="file" name="pwa_icon_512" class="form-control" data-filepond data-accepted="image/png,image/jpeg,image/webp" data-current-url="{{ $pwa['icon_512_url'] ?: '' }}" data-current-name="{{ $pwa['icon_512_path'] ? basename($pwa['icon_512_path']) : '' }}"></div>

            <div class="col-12"><div class="admin-premium-surface p-3"><div class="admin-card-kicker mb-3">Convite de instalacao</div><div class="row g-3">
                <div class="col-md-4"><label class="form-label" for="pwa_popup_badge">Etiqueta do pop-up</label><input id="pwa_popup_badge" type="text" name="pwa_popup_badge" class="form-control" maxlength="80" value="{{ old('pwa_popup_badge', $pwa['popup_badge']) }}"></div>
                <div class="col-md-8"><label class="form-label" for="pwa_popup_title">Titulo do pop-up</label><input id="pwa_popup_title" type="text" name="pwa_popup_title" class="form-control" maxlength="120" value="{{ old('pwa_popup_title', $pwa['popup_title']) }}"></div>
                <div class="col-12"><label class="form-label" for="pwa_popup_description">Descricao do pop-up</label><textarea id="pwa_popup_description" name="pwa_popup_description" class="form-control" rows="3" maxlength="255">{{ old('pwa_popup_description', $pwa['popup_description']) }}</textarea></div>
                <div class="col-md-3"><label class="form-label" for="pwa_popup_primary_label">Botao principal</label><input id="pwa_popup_primary_label" type="text" name="pwa_popup_primary_label" class="form-control" maxlength="60" value="{{ old('pwa_popup_primary_label', $pwa['popup_primary_label']) }}"></div>
                <div class="col-md-3"><label class="form-label" for="pwa_popup_secondary_label">Botao secundario</label><input id="pwa_popup_secondary_label" type="text" name="pwa_popup_secondary_label" class="form-control" maxlength="60" value="{{ old('pwa_popup_secondary_label', $pwa['popup_secondary_label']) }}"></div>
                <div class="col-md-3"><label class="form-label" for="pwa_footer_label">Texto do botao no rodape</label><input id="pwa_footer_label" type="text" name="pwa_footer_label" class="form-control" maxlength="60" value="{{ old('pwa_footer_label', $pwa['footer_label']) }}"></div>
                <div class="col-md-3"><label class="form-label" for="pwa_mobile_menu_label">Texto do botao no menu movel</label><input id="pwa_mobile_menu_label" type="text" name="pwa_mobile_menu_label" class="form-control" maxlength="60" value="{{ old('pwa_mobile_menu_label', $pwa['mobile_menu_label']) }}"></div>
            </div></div></div>

            <div class="col-12"><div class="admin-premium-surface p-3"><div class="admin-card-kicker mb-3">Tela offline</div><div class="row g-3">
                <div class="col-md-4"><label class="form-label" for="pwa_offline_title">Titulo offline</label><input id="pwa_offline_title" type="text" name="pwa_offline_title" class="form-control" maxlength="120" value="{{ old('pwa_offline_title', $pwa['offline_title']) }}"></div>
                <div class="col-md-4"><label class="form-label" for="pwa_offline_button_label">Botao offline</label><input id="pwa_offline_button_label" type="text" name="pwa_offline_button_label" class="form-control" maxlength="60" value="{{ old('pwa_offline_button_label', $pwa['offline_button_label']) }}"></div>
                <div class="col-12"><label class="form-label" for="pwa_offline_message">Mensagem offline</label><textarea id="pwa_offline_message" name="pwa_offline_message" class="form-control" rows="3" maxlength="255">{{ old('pwa_offline_message', $pwa['offline_message']) }}</textarea></div>
            </div></div></div>
        </div>
    </div>
</div>
