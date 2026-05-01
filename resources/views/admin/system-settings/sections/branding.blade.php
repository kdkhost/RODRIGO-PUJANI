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
                <label class="form-label" for="admin_subtitle">Subtitulo do painel</label>
                <input id="admin_subtitle" type="text" name="admin_subtitle" class="form-control" maxlength="80" value="{{ old('admin_subtitle', $branding['admin_subtitle']) }}" placeholder="Subtitulo do painel">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="admin_footer_text">Texto principal do rodape</label>
                <input id="admin_footer_text" type="text" name="admin_footer_text" class="form-control" maxlength="180" value="{{ old('admin_footer_text', $branding['admin_footer_text']) }}" placeholder="Texto principal do rodape">
            </div>
            <div class="col-12">
                <label class="form-label" for="admin_footer_meta">Texto complementar do rodape</label>
                <input id="admin_footer_meta" type="text" name="admin_footer_meta" class="form-control" maxlength="180" value="{{ old('admin_footer_meta', $branding['admin_footer_meta']) }}" placeholder="Texto complementar do rodape">
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
