<div class="card admin-table-card">
    <div class="card-header">
        <div>
            <div class="admin-card-kicker">Otimizacao de busca</div>
            <h3 class="card-title">SEO e indexacao</h3>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-4 admin-premium-form">
            <div class="col-md-6">
                <label class="form-label" for="seo_title_suffix">Sufixo de titulo</label>
                <input id="seo_title_suffix" type="text" name="seo_title_suffix" class="form-control" value="{{ old('seo_title_suffix', $seo['title_suffix']) }}" placeholder="Ex: | Pujani Advogados">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="seo_author">Autor do site</label>
                <input id="seo_author" type="text" name="seo_author" class="form-control" value="{{ old('seo_author', $seo['author']) }}" placeholder="Ex: Rodrigo Pujani">
            </div>
            <div class="col-12">
                <label class="form-label" for="seo_meta_description">Meta descricao global</label>
                <textarea id="seo_meta_description" name="seo_meta_description" class="form-control" rows="3" placeholder="Descreva o escritorio em ate 160 caracteres">{{ old('seo_meta_description', $seo['meta_description']) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="seo_meta_keywords">Palavras-chave</label>
                <textarea id="seo_meta_keywords" name="seo_meta_keywords" class="form-control" rows="3" placeholder="advogado, juridico, processos">{{ old('seo_meta_keywords', $seo['meta_keywords']) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="seo_hashtags">Hashtags persistentes</label>
                <textarea id="seo_hashtags" name="seo_hashtags" class="form-control" rows="3" placeholder="#pujani #advocacia">{{ old('seo_hashtags', $seo['hashtags']) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="seo_og_image_path">Imagem para redes sociais</label>
                <div class="input-group">
                    <input id="seo_og_image_path" type="text" name="seo_og_image_path" class="form-control" value="{{ old('seo_og_image_path', $seo['og_image_path']) }}" placeholder="Caminho da imagem ou URL">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.AdminUI.openAssetManager('seo_og_image_path')">
                        <i class="bi bi-folder2-open"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="seo_google_analytics_id">Google Analytics ID</label>
                <input id="seo_google_analytics_id" type="text" name="seo_google_analytics_id" class="form-control" value="{{ old('seo_google_analytics_id', $seo['google_analytics_id']) }}" placeholder="G-XXXXXXXX">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="seo_google_site_verification">Google Search Console</label>
                <input id="seo_google_site_verification" type="text" name="seo_google_site_verification" class="form-control" value="{{ old('seo_google_site_verification', $seo['google_site_verification']) }}" placeholder="Codigo de verificacao">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="seo_bing_site_verification">Bing Webmaster</label>
                <input id="seo_bing_site_verification" type="text" name="seo_bing_site_verification" class="form-control" value="{{ old('seo_bing_site_verification', $seo['bing_site_verification']) }}" placeholder="Codigo de verificacao">
            </div>
        </div>
    </div>
</div>
