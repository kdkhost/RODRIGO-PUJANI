<div class="card admin-table-card">
    <div class="card-header">
        <div>
            <div class="admin-card-kicker">Protecao anti-spam</div>
            <h3 class="card-title">reCAPTCHA v3 invisivel</h3>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 admin-premium-form">
            <div class="col-md-4 form-check ps-5 pt-4">
                <input type="checkbox" class="form-check-input" id="recaptcha_enabled" name="recaptcha_enabled" value="1" @checked(old('recaptcha_enabled', $recaptcha['enabled']))>
                <label class="form-check-label" for="recaptcha_enabled">Ativar protecao invisivel</label>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="recaptcha_min_score">Score minimo</label>
                <input id="recaptcha_min_score" type="number" name="recaptcha_min_score" class="form-control" min="0.1" max="1" step="0.1" value="{{ old('recaptcha_min_score', number_format($recaptcha['minimum_score'], 1, '.', '')) }}" placeholder="0.5">
            </div>
            <div class="col-md-4">
                <label class="form-label">Escopo protegido</label>
                <input type="text" class="form-control" value="Login, redefinicao, portal do cliente e contato" readonly>
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
