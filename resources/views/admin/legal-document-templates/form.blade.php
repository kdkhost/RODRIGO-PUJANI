@extends('admin.layouts.app')

@php
    $creating = ! $template->exists;
    $defaultDefinition = [
        'layout' => 'absolute',
        'unit' => 'mm',
        'paper' => ['size' => 'A4', 'width_mm' => 210, 'height_mm' => 297],
        'pages' => [[
            'width_mm' => 210,
            'height_mm' => 297,
            'background' => ['color' => '#ffffff', 'image_path' => '', 'image_opacity' => 0.08, 'image_fit' => 'cover'],
            'elements' => [
                ['id' => 'titulo', 'type' => 'text', 'x_mm' => 24, 'y_mm' => 34, 'w_mm' => 162, 'h_mm' => 16, 'text' => 'Documento de {{client.name}}', 'font_size_pt' => 16, 'font_weight' => '700', 'line_height' => 1.2, 'align' => 'center', 'color' => '#111827', 'opacity' => 1],
                ['id' => 'corpo', 'type' => 'text', 'x_mm' => 24, 'y_mm' => 64, 'w_mm' => 162, 'h_mm' => 120, 'text' => "Cliente: {{client.name}}\nCPF/CNPJ: {{client.document_number}}\nDocumento emitido em {{system.current_date}} por {{generator.name}}.", 'font_size_pt' => 11, 'font_weight' => '400', 'line_height' => 1.45, 'align' => 'justify', 'color' => '#111827', 'opacity' => 1],
                ['id' => 'assinatura-cliente', 'type' => 'signature', 'x_mm' => 55, 'y_mm' => 236, 'w_mm' => 100, 'h_mm' => 24, 'label' => 'Assinatura do cliente', 'signer_order' => 1, 'required' => true, 'border_color' => '#111827', 'opacity' => 1],
            ],
        ]],
    ];
    $definitionValue = old(
        'definition_json',
        json_encode($latestVersion?->definition ?? $defaultDefinition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    $defaultTitleTemplate = 'Documento de {{client.name}}';
    $branding = branding_config();
    $brandLogo = $branding['logo_path'] ?? '';
@endphp

@section('content')
    <div class="app-content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center gap-3">
            <div>
                <h1>{{ $pageTitle }}</h1>
                <p class="text-muted mb-0">
                    {{ $creating ? 'Cadastre os metadados e publique a primeira versão.' : 'Edite somente metadados; o conteúdo publicado permanece imutável.' }}
                </p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ $creating ? route('admin.legal-document-templates.index') : route('admin.legal-document-templates.show', $template) }}">
                Voltar
            </a>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form method="POST" action="{{ $creating ? route('admin.legal-document-templates.store') : route('admin.legal-document-templates.update', $template) }}" data-document-template-form>
                @csrf
                @unless($creating)
                    @method('PUT')
                @endunless

                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="card">
                            <div class="card-header"><strong>Identificação</strong></div>
                            <div class="card-body row g-3">
                                <div class="col-md-7">
                                    <label class="form-label" for="name">Nome</label>
                                    <input class="form-control" id="name" name="name" maxlength="255" required value="{{ old('name', $template->name) }}">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="slug">Identificador</label>
                                    <input class="form-control" id="slug" name="slug" maxlength="255" required value="{{ old('slug', $template->slug) }}" placeholder="procuracao-ad-judicia">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="description">Descrição</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" maxlength="5000">{{ old('description', $template->description) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="context_scope">Contexto</label>
                                    <select class="form-select" id="context_scope" name="context_scope" required @disabled(! $creating)>
                                        @foreach($contextScopes as $value => $label)
                                            <option value="{{ $value }}" @selected(old('context_scope', $template->context_scope ?: App\Models\LegalDocumentTemplate::CONTEXT_CLIENT_CASE) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @unless($creating)
                                        <input type="hidden" name="context_scope" value="{{ $template->context_scope }}">
                                        <div class="form-text">O contexto não pode ser alterado depois da publicação da primeira versão.</div>
                                    @endunless
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="default_output_format">Formato padrão</label>
                                    <select class="form-select" id="default_output_format" name="default_output_format" required>
                                        @foreach($outputFormats as $value => $label)
                                            <option value="{{ $value }}" @selected(old('default_output_format', $template->default_output_format ?: App\Models\LegalDocumentTemplate::FORMAT_PDF) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Use PDF para preservar posição, fundo e impressão idêntica no editor milimétrico.</div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="is_active" value="0">
                                        <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $template->exists ? $template->is_active : true))>
                                        <label class="form-check-label" for="is_active">Disponível para geração</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($creating)
                            <div class="card mt-4">
                                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <strong>Editor visual milimétrico</strong>
                                        <small class="d-block text-muted">A4 fiel ao PDF e à impressão. Arraste os itens e ajuste as medidas em milímetros.</small>
                                    </div>
                                    <div class="btn-group btn-group-sm flex-wrap" role="group">
                                        <button class="btn btn-outline-primary" type="button" data-doc-add="text"><i class="bi bi-fonts me-1"></i>Texto</button>
                                        <button class="btn btn-outline-primary" type="button" data-doc-add="signature"><i class="bi bi-pen me-1"></i>Assinatura</button>
                                        <button class="btn btn-outline-primary" type="button" data-doc-add="line">Linha</button>
                                        <button class="btn btn-outline-primary" type="button" data-doc-add="rectangle">Caixa</button>
                                        <button class="btn btn-outline-primary" type="button" data-doc-add="logo" data-logo-path="{{ $brandLogo }}">Logo</button>
                                        <button class="btn btn-outline-secondary" type="button" data-doc-add-page>Nova página</button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <label class="form-label" for="title_template">Título do documento</label>
                                    <input class="form-control mb-3" id="title_template" name="title_template" maxlength="255" required value="{{ old('title_template', $defaultTitleTemplate) }}">

                                    <div class="legal-doc-designer" data-document-designer data-brand-logo="{{ $brandLogo }}" data-background-upload-url="{{ route('admin.legal-document-templates.background-upload') }}" data-csrf-token="{{ csrf_token() }}">
                                        <div class="legal-doc-designer-toolbar">
                                            <div>
                                                <label class="form-label mb-1" for="doc-background-path">Papel timbrado / fundo da página</label>
                                                <input class="form-control form-control-sm" id="doc-background-path" data-doc-bg-path placeholder="Ex.: storage/branding/papel-timbrado.png">
                                                <div class="legal-doc-bg-drop mt-2" data-doc-bg-drop role="button" tabindex="0">
                                                    <input type="file" accept="image/png,image/jpeg,image/webp" hidden data-doc-bg-file>
                                                    <i class="bi bi-cloud-arrow-up"></i>
                                                    <span>Arraste o papel timbrado aqui ou clique para enviar</span>
                                                    <small data-doc-bg-status>PNG, JPG ou WEBP até 10 MB. Aplicado na página selecionada.</small>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="form-label mb-1" for="doc-background-opacity">Opacidade do fundo</label>
                                                <input class="form-range" id="doc-background-opacity" type="range" min="0" max="1" step="0.01" data-doc-bg-opacity>
                                            </div>
                                            <div>
                                                <label class="form-label mb-1" for="doc-background-fit">Encaixe no A4</label>
                                                <select class="form-select form-select-sm" id="doc-background-fit" data-doc-bg-fit>
                                                    <option value="cover">Ocupar A4 inteiro</option>
                                                    <option value="contain">Conter sem cortar</option>
                                                    <option value="stretch">Esticar</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="legal-doc-designer-grid">
                                            <div class="legal-doc-page-list" data-doc-pages></div>
                                            <aside class="legal-doc-inspector">
                                                <h3 class="h6">Item selecionado</h3>
                                                <p class="small text-muted">Arraste no papel ou ajuste abaixo para posicionamento preciso.</p>
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
                                                    <div class="col-12 d-flex gap-2 mt-2">
                                                        <button class="btn btn-sm btn-outline-danger" type="button" data-doc-remove>Remover item</button>
                                                    </div>
                                                </div>
                                            </aside>
                                        </div>
                                    </div>

                                    <textarea class="form-control font-monospace mt-3" id="definition_json" name="definition_json" rows="10" required hidden>{{ $definitionValue }}</textarea>
                                    <details class="mt-3">
                                        <summary>Avançado: ver JSON gerado automaticamente</summary>
                                        <textarea class="form-control font-monospace mt-2" rows="10" data-doc-json-mirror readonly></textarea>
                                    </details>
                                </div>
                            </div>
                        @endif

                        <div class="d-flex justify-content-end gap-2 mt-4 mb-4">
                            <a class="btn btn-outline-secondary" href="{{ $creating ? route('admin.legal-document-templates.index') : route('admin.legal-document-templates.show', $template) }}">Cancelar</a>
                            <button class="btn btn-primary" type="submit">{{ $creating ? 'Criar e publicar versão 1' : 'Salvar metadados' }}</button>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card position-sticky" style="top: 1rem;">
                            <div class="card-header"><strong>Tokens permitidos</strong></div>
                            <div class="card-body" style="max-height: 70vh; overflow: auto;">
                                <p class="small text-muted">Use apenas os tokens da lista. Tokens desconhecidos bloqueiam a publicação e a geração.</p>
                                @foreach($allowedTokens as $token => $description)
                                    <div class="border-bottom py-2">
                                        <code>{{ '{'.'{'.$token.'}'.'}' }}</code>
                                        <small class="d-block text-muted">{{ $description }}</small>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
