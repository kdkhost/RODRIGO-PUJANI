@php
    $definitionJsonValue = $definitionJsonValue ?? '{}';
@endphp

<div class="legal-doc-designer" data-document-designer data-brand-logo="{{ $brandLogo }}" data-background-upload-url="{{ route('admin.legal-document-templates.background-upload') }}" data-csrf-token="{{ csrf_token() }}">
    <div class="legal-doc-designer-header">
        <div>
            <span class="legal-doc-designer-kicker">Composição A4 no-code</span>
            <h3>Monte o documento visualmente</h3>
            <p>Use papel timbrado, grade, margens e posições em milímetros para manter o PDF idêntico à criação e à impressão.</p>
        </div>
        <div class="legal-doc-designer-actions" aria-label="Adicionar itens ao documento">
            <button class="btn btn-sm btn-primary" type="button" data-doc-add="text"><i class="bi bi-fonts me-1"></i>Texto</button>
            <button class="btn btn-sm btn-primary" type="button" data-doc-add="signature"><i class="bi bi-pen me-1"></i>Assinatura</button>
            <button class="btn btn-sm btn-outline-primary" type="button" data-doc-add="line">Linha</button>
            <button class="btn btn-sm btn-outline-primary" type="button" data-doc-add="rectangle">Caixa</button>
            <button class="btn btn-sm btn-outline-primary" type="button" data-doc-add="logo" data-logo-path="{{ $brandLogo }}">Logo</button>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-doc-add-page title="Cria uma nova página copiando o fundo atual e leva assinaturas para a última folha."><i class="bi bi-file-earmark-plus me-1"></i>Nova página</button>
        </div>
        <small class="legal-doc-designer-hint">Nova página copia automaticamente o papel timbrado atual e mantém assinaturas/testemunhas na última folha.</small>
    </div>

    <div class="legal-doc-designer-toolbar">
        <section class="legal-doc-tool-card">
            <div class="legal-doc-tool-card-header">
                <i class="bi bi-grid-3x3-gap"></i>
                <div>
                    <strong>Grade e margens</strong>
                    <small>Assistentes opcionais para alinhamento fino.</small>
                </div>
            </div>
            <div class="legal-doc-tool-switches">
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" data-doc-grid-visible>
                    <span class="form-check-label">Mostrar grade</span>
                </label>
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" data-doc-snap-grid>
                    <span class="form-check-label">Encaixar na grade</span>
                </label>
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" data-doc-margins-enabled>
                    <span class="form-check-label">Mostrar margens</span>
                </label>
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" data-doc-free-positioning>
                    <span class="form-check-label">Ajuste livre fora da margem</span>
                </label>
            </div>
            <div class="legal-doc-guide-fields">
                <div>
                    <label class="form-label mb-1">Grade</label>
                    <div class="input-group input-group-sm">
                        <input class="form-control" type="number" min="1" max="50" step="0.5" data-doc-grid-size>
                        <span class="input-group-text">mm</span>
                    </div>
                </div>
                <div>
                    <label class="form-label mb-1">Topo</label>
                    <input class="form-control form-control-sm" type="number" min="0" max="120" step="0.5" data-doc-margin="top">
                </div>
                <div>
                    <label class="form-label mb-1">Direita</label>
                    <input class="form-control form-control-sm" type="number" min="0" max="120" step="0.5" data-doc-margin="right">
                </div>
                <div>
                    <label class="form-label mb-1">Base</label>
                    <input class="form-control form-control-sm" type="number" min="0" max="120" step="0.5" data-doc-margin="bottom">
                </div>
                <div>
                    <label class="form-label mb-1">Esquerda</label>
                    <input class="form-control form-control-sm" type="number" min="0" max="120" step="0.5" data-doc-margin="left">
                </div>
            </div>
        </section>

        <section class="legal-doc-tool-card legal-doc-tool-card-background">
            <div class="legal-doc-tool-card-header">
                <i class="bi bi-image"></i>
                <div>
                    <strong>Papel timbrado / fundo</strong>
                    <small>Envie uma imagem para ocupar a página A4 selecionada.</small>
                </div>
            </div>
            <label class="form-label mb-1" for="doc-background-path">Caminho do fundo</label>
            <input class="form-control form-control-sm" id="doc-background-path" data-doc-bg-path placeholder="Ex.: uploads/legal-document-backgrounds/papel-timbrado.png">
            <div class="legal-doc-bg-drop mt-2" data-doc-bg-drop role="button" tabindex="0">
                <input type="file" accept="image/png,image/jpeg,image/webp" hidden data-doc-bg-file>
                <i class="bi bi-cloud-arrow-up"></i>
                <span>Arraste o papel timbrado aqui ou clique para enviar</span>
                <small data-doc-bg-status>PNG, JPG ou WEBP até 10 MB. Aplicado na página selecionada.</small>
            </div>
        </section>

        <section class="legal-doc-tool-card">
            <div class="legal-doc-tool-card-header">
                <i class="bi bi-sliders"></i>
                <div>
                    <strong>Aparência do fundo</strong>
                    <small>Controle visual do papel timbrado no A4.</small>
                </div>
            </div>
            <label class="form-label mb-1" for="doc-background-opacity">Opacidade</label>
            <input class="form-range" id="doc-background-opacity" type="range" min="0" max="1" step="0.01" data-doc-bg-opacity>
            <label class="form-label mb-1 mt-2" for="doc-background-fit">Encaixe no A4</label>
            <select class="form-select form-select-sm" id="doc-background-fit" data-doc-bg-fit>
                <option value="cover">Ocupar A4 inteiro</option>
                <option value="contain">Conter sem cortar</option>
                <option value="stretch">Esticar</option>
            </select>
            <p class="small text-muted mb-0 mt-2">A grade e as margens aparecem apenas no editor. O PDF final mantém o conteúdo limpo.</p>
        </section>
    </div>

    <div class="legal-doc-designer-grid">
        <div class="legal-doc-page-list" data-doc-pages aria-label="Pré-visualização das páginas A4"></div>
        <aside class="legal-doc-inspector">
            <div class="legal-doc-inspector-header">
                <div>
                    <span class="legal-doc-designer-kicker">Propriedades</span>
                    <h3>Item selecionado</h3>
                </div>
                <button class="btn btn-sm btn-outline-danger" type="button" data-doc-remove>Remover</button>
            </div>
            <p class="small text-muted">Arraste no papel ou ajuste os campos abaixo para posicionamento exato em milímetros.</p>
            <div class="row g-2">
                <div class="col-6"><label class="form-label">X mm</label><input class="form-control form-control-sm" type="number" step="0.1" data-doc-field="x_mm"></div>
                <div class="col-6"><label class="form-label">Y mm</label><input class="form-control form-control-sm" type="number" step="0.1" data-doc-field="y_mm"></div>
                <div class="col-6"><label class="form-label">Largura</label><input class="form-control form-control-sm" type="number" step="0.1" data-doc-field="w_mm"></div>
                <div class="col-6"><label class="form-label">Altura</label><input class="form-control form-control-sm" type="number" step="0.1" data-doc-field="h_mm"></div>
                <div class="col-6"><label class="form-label">Opacidade</label><input class="form-control form-control-sm" type="number" min="0" max="1" step="0.01" data-doc-field="opacity"></div>
                <div class="col-6" data-doc-type-field="text"><label class="form-label">Fonte</label><input class="form-control form-control-sm" type="number" step="0.5" data-doc-field="font_size_pt"></div>
                <div class="col-12" data-doc-type-field="text"><label class="form-label">Texto</label><textarea class="form-control form-control-sm" rows="4" data-doc-field="text"></textarea></div>
                <div class="col-12" data-doc-type-field="signature"><label class="form-label">Rótulo da assinatura</label><input class="form-control form-control-sm" data-doc-field="label"></div>
                <div class="col-6" data-doc-type-field="signature"><label class="form-label">Ordem do signatário</label><input class="form-control form-control-sm" type="number" min="1" step="1" data-doc-field="signer_order"></div>
                <div class="col-6" data-doc-type-field="signature"><label class="form-label">Obrigatório</label><select class="form-select form-select-sm" data-doc-field="required"><option value="true">Sim</option><option value="false">Opcional/testemunha</option></select></div>
                <div class="col-12" data-doc-type-field="image"><label class="form-label">Caminho da imagem</label><input class="form-control form-control-sm" data-doc-field="image_path"></div>
            </div>
        </aside>
    </div>

    <textarea class="form-control font-monospace mt-3" id="definition_json" name="definition_json" rows="10" required hidden>{{ $definitionJsonValue }}</textarea>
    <details class="mt-3">
        <summary>Avançado: ver JSON gerado automaticamente</summary>
        <textarea class="form-control font-monospace mt-2" rows="10" data-doc-json-mirror readonly></textarea>
    </details>
</div>
