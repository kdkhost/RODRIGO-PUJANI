@extends('admin.layouts.app')

@section('content')
    @php
        $hasExistingDocuments = $documents->isNotEmpty();
        $hasTemplates = ($templates ?? collect())->isNotEmpty();
        $inactiveTemplatesCount = (int) ($inactiveTemplatesCount ?? 0);
        $currentSource = old('document_source', $documentSource ?? ($hasExistingDocuments ? 'existing' : ($hasTemplates ? 'template' : 'upload')));
        if (! $hasExistingDocuments && $currentSource === 'existing') {
            $currentSource = $hasTemplates ? 'template' : 'upload';
        }
        if (! $hasTemplates && $currentSource === 'template') {
            $currentSource = $hasExistingDocuments ? 'existing' : 'upload';
        }
        $selectedDocumentId = (int) old('legal_document_id', $selectedDocument ?? 0);
        $selectedTemplateId = (int) old('template_id', $selectedTemplate ?? 0);
    @endphp

    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Assinatura eletrônica</div>
                    <h1>Enviar documento para assinatura</h1>
                    <p>Selecione um PDF privado, gere um PDF por modelo do Gerador de documentos ou anexe um novo PDF agora. O sistema envia convites individuais, controla a ordem e guarda evidências.</p>
                </div>
                <a class="btn btn-outline-secondary" href="{{ route('admin.signature-requests.index') }}">
                    <i class="bi bi-arrow-left me-1"></i>Voltar
                </a>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            @if(session('status'))
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-1"></i>{{ session('status') }}
                </div>
            @endif

            @if(!empty($fromTemplateGeneration))
                <div class="alert alert-info">
                    <i class="bi bi-magic me-1"></i>
                    Documento gerado por template selecionado automaticamente. Revise os signatários e confirme para enviar o link individual por e-mail.
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <strong>Revise os dados da solicitação.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.signature-requests.store') }}" enctype="multipart/form-data" class="signature-request-form">
                @csrf

                <div class="row g-4">
                    <div class="col-xl-7">
                        <div class="card admin-premium-card h-100">
                            <div class="card-header">
                                <div>
                                    <div class="admin-card-kicker">Documento</div>
                                    <h2 class="card-title">O que será enviado ao cliente?</h2>
                                </div>
                            </div>
                            <div class="card-body">
                                <div
                                    class="signature-source-toggle"
                                    data-signature-source-toggle
                                    data-current-source="{{ $currentSource }}"
                                    data-has-existing-documents="{{ $hasExistingDocuments ? '1' : '0' }}"
                                >
                                    <label class="signature-source-option {{ $hasExistingDocuments ? '' : 'opacity-75' }}">
                                        <input type="radio" name="document_source" value="existing" @checked($currentSource === 'existing') @disabled(! $hasExistingDocuments)>
                                        <span>
                                            <strong>Selecionar documento existente</strong>
                                            <small>{{ $hasExistingDocuments ? 'Use um PDF privado já salvo em Documentos.' : 'Nenhum PDF privado elegível disponível.' }}</small>
                                        </span>
                                    </label>
                                    <label class="signature-source-option {{ $hasTemplates ? '' : 'opacity-75' }}">
                                        <input type="radio" name="document_source" value="template" @checked($currentSource === 'template') @disabled(! $hasTemplates)>
                                        <span>
                                            <strong>Gerar pelo modelo</strong>
                                            <small>
                                                @if($hasTemplates)
                                                    Use um modelo do Gerador e crie o PDF privado automaticamente.
                                                @elseif($inactiveTemplatesCount > 0)
                                                    {{ $inactiveTemplatesCount }} modelo(s) com versão estão inativos. Ative no Gerador de documentos.
                                                @else
                                                    Nenhum modelo ativo disponível.
                                                @endif
                                            </small>
                                        </span>
                                    </label>
                                    <label class="signature-source-option">
                                        <input type="radio" name="document_source" value="upload" @checked($currentSource === 'upload')>
                                        <span>
                                            <strong>Anexar PDF agora</strong>
                                            <small>Envie o arquivo e o sistema salva no storage privado antes do convite.</small>
                                        </span>
                                    </label>
                                </div>

                                <div class="signature-source-panel mt-4" data-signature-source-panel="existing">
                                    @if($documents->isEmpty())
                                        <div class="alert alert-warning mb-0">
                                            Nenhum PDF privado elegível foi encontrado. Use <strong>Gerar pelo modelo</strong>, <strong>Anexar PDF agora</strong> ou cadastre o documento em Jurídico &gt; Documentos.
                                        </div>
                                    @else
                                        <label class="form-label" for="legal_document_id">Documento existente</label>
                                        <select id="legal_document_id" name="legal_document_id" class="form-select">
                                            <option value="">Selecione o PDF que será enviado</option>
                                            @foreach($documents as $document)
                                                <option
                                                    value="{{ $document->id }}"
                                                    @selected($selectedDocumentId === (int) $document->id)
                                                    data-title="{{ e($document->title) }}"
                                                    data-client="{{ e($document->client?->name ?: 'Sem cliente') }}"
                                                    data-case="{{ e($document->legalCase?->title ?: 'Sem processo') }}"
                                                    data-file="{{ e($document->original_name ?: $document->file_name ?: 'documento.pdf') }}"
                                                    data-size="{{ e($document->size ? number_format($document->size / 1024, 1, ',', '.').' KB' : 'Tamanho não informado') }}"
                                                    data-sha="{{ e($document->sha256) }}"
                                                    data-generated="{{ $document->generation ? '1' : '0' }}"
                                                    data-template="{{ e($document->generation?->template?->name ?: '') }}"
                                                    data-download-url="{{ route('admin.legal-documents.download', $document) }}"
                                                >
                                                    {{ $document->title }}
                                                    @if($document->client)
                                                        — {{ $document->client->name }}
                                                    @endif
                                                    @if($document->legalCase)
                                                        — {{ $document->legalCase->title }}
                                                    @endif
                                                    @if($document->generation)
                                                        — gerado por template
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="admin-signature-document-preview mt-3" data-signature-document-preview data-empty="true">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="admin-system-preview-mark"><i class="bi bi-file-earmark-pdf"></i></div>
                                                <div class="flex-grow-1 min-w-0">
                                                    <strong data-document-preview-title>Nenhum documento selecionado</strong>
                                                    <div class="small text-muted" data-document-preview-meta>Escolha um PDF privado na lista para ver cliente, processo, origem e hash.</div>
                                                    <div class="small text-muted text-break mt-1" data-document-preview-sha></div>
                                                </div>
                                                <a class="btn btn-sm btn-outline-primary d-none" href="#" target="_blank" rel="noopener" data-document-preview-download>
                                                    <i class="bi bi-eye me-1"></i>Pré-visualizar
                                                </a>
                                            </div>
                                        </div>
                                        <div class="form-text">
                                            Só aparecem PDFs privados, com cliente vinculado e hash SHA-256 calculado. Documentos gerados por template em PDF também ficam disponíveis aqui.
                                        </div>
                                    @endif
                                </div>

                                <div class="signature-source-panel mt-4" data-signature-source-panel="template">
                                    @if($templates->isEmpty())
                                        <div class="alert alert-warning mb-0">
                                            @if($inactiveTemplatesCount > 0)
                                                Existe(m) {{ $inactiveTemplatesCount }} modelo(s) com versão publicada, mas inativo(s). Acesse <strong>Jurídico &gt; Gerador de documentos</strong>, abra o modelo e clique em <strong>Ativar modelo</strong> para liberar teste, geração e assinatura.
                                            @else
                                                Nenhum modelo ativo do Gerador de documentos está disponível para geração e assinatura.
                                            @endif
                                        </div>
                                    @else
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label" for="template_id">Modelo do Gerador de documentos</label>
                                                <select id="template_id" name="template_id" class="form-select">
                                                    <option value="">Selecione o modelo que será convertido em PDF</option>
                                                    @foreach($templates as $template)
                                                        @php($contextLabel = \App\Models\LegalDocumentTemplate::contextScopes()[$template->context_scope] ?? $template->context_scope)
                                                        <option
                                                            value="{{ $template->id }}"
                                                            @selected($selectedTemplateId === (int) $template->id)
                                                            data-name="{{ e($template->name) }}"
                                                            data-context="{{ e($template->context_scope) }}"
                                                            data-context-label="{{ e($contextLabel) }}"
                                                            data-format="{{ e($template->default_output_format) }}"
                                                            data-version="{{ e($template->latestVersion?->version ?: '1') }}"
                                                            data-title-template="{{ e($template->latestVersion?->title_template ?: $template->name) }}"
                                                        >
                                                            {{ $template->name }} — {{ $contextLabel }} — versão {{ $template->latestVersion?->version ?: '1' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="form-text">
                                                    Modelos criados em Jurídico &gt; Gerador de documentos aparecem aqui. O sistema gera um PDF privado, calcula SHA-256 e envia o link individual de assinatura.
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="template_client_id">Cliente para preencher o modelo</label>
                                                <select id="template_client_id" name="template_client_id" class="form-select">
                                                    <option value="">Selecione o cliente</option>
                                                    @foreach($clients as $client)
                                                        <option value="{{ $client->id }}" @selected((string) old('template_client_id') === (string) $client->id)>{{ $client->name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="form-text">Obrigatório para modelos de cliente ou cliente + processo.</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="template_legal_case_id">Processo para preencher o modelo</label>
                                                <select id="template_legal_case_id" name="template_legal_case_id" class="form-select">
                                                    <option value="">Sem processo específico</option>
                                                    @foreach($cases as $case)
                                                        <option value="{{ $case->id }}" data-client-id="{{ $case->client_id }}" @selected((string) old('template_legal_case_id') === (string) $case->id)>{{ $case->title }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="form-text">Obrigatório para modelos de processo ou cliente + processo.</div>
                                            </div>
                                        </div>
                                        <div class="admin-signature-document-preview mt-3" data-signature-template-preview data-empty="true">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="admin-system-preview-mark"><i class="bi bi-magic"></i></div>
                                                <div class="flex-grow-1 min-w-0">
                                                    <strong data-template-preview-title>Nenhum modelo selecionado</strong>
                                                    <div class="small text-muted" data-template-preview-meta>Escolha um modelo para ver escopo, versão e saída.</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="signature-source-panel mt-4" data-signature-source-panel="upload">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="upload_client_id">Cliente vinculado</label>
                                            <select id="upload_client_id" name="upload_client_id" class="form-select">
                                                <option value="">Selecione o cliente</option>
                                                @foreach($clients as $client)
                                                    <option value="{{ $client->id }}" @selected((string) old('upload_client_id') === (string) $client->id)>{{ $client->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="upload_legal_case_id">Processo ou caso</label>
                                            <select id="upload_legal_case_id" name="upload_legal_case_id" class="form-select">
                                                <option value="">Sem processo específico</option>
                                                @foreach($cases as $case)
                                                    <option value="{{ $case->id }}" data-client-id="{{ $case->client_id }}" @selected((string) old('upload_legal_case_id') === (string) $case->id)>{{ $case->title }}</option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">Se escolher um processo, ele precisa pertencer ao cliente selecionado.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="upload_title">Nome do documento no sistema</label>
                                            <input id="upload_title" name="upload_title" class="form-control" maxlength="255" value="{{ old('upload_title') }}" placeholder="Ex.: Contrato de honorários">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="upload_file">PDF para assinatura</label>
                                            <input
                                                id="upload_file"
                                                type="file"
                                                name="upload_file"
                                                class="form-control"
                                                accept="application/pdf,.pdf"
                                                data-filepond
                                                data-accepted="application/pdf"
                                                data-max-file-size="15MB"
                                            >
                                            <div class="form-text">Arraste e solte ou selecione um PDF real. O arquivo será salvo em área privada, validado e registrado com SHA-256.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-4 mb-0">
                                    <i class="bi bi-shield-check me-1"></i>
                                    O cliente não recebe o arquivo como anexo inicial: ele recebe um link individual seguro para ler, confirmar dados e assinar.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-5">
                        <div class="card admin-premium-card h-100">
                            <div class="card-header">
                                <div>
                                    <div class="admin-card-kicker">Convite</div>
                                    <h2 class="card-title">Dados do envio</h2>
                                </div>
                            </div>
                            <div class="card-body row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="title">Título da solicitação</label>
                                    <input id="title" name="title" class="form-control" maxlength="255" value="{{ old('title') }}" placeholder="Ex.: Assinatura do contrato de honorários" required>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label" for="expires_at">Expira em</label>
                                    <input id="expires_at" type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at', now()->addDays(config('signatures.default_expiration_days'))->format('Y-m-d\TH:i')) }}" required>
                                </div>
                                <div class="col-md-5 d-flex align-items-end">
                                    <div class="form-check mb-2">
                                        <input type="hidden" name="ordered" value="0">
                                        <input type="checkbox" name="ordered" value="1" class="form-check-input" id="ordered" @checked(old('ordered'))>
                                        <label for="ordered" class="form-check-label">Exigir ordem</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="message">Mensagem ao cliente</label>
                                    <textarea id="message" name="message" class="form-control" rows="3" placeholder="Mensagem opcional enviada junto ao convite.">{{ old('message') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card admin-premium-card">
                            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="admin-card-kicker">Signatários</div>
                                    <h2 class="card-title">Quem precisa assinar?</h2>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-signer">
                                    <i class="bi bi-plus-lg me-1"></i>Adicionar signatário
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="signers" class="signature-signers-list">
                                    @php($oldSigners = old('signers', [['name' => '', 'email' => '', 'document' => '']]))
                                    @foreach($oldSigners as $index => $signer)
                                        <div class="row g-2 mb-2 signer-row">
                                            <div class="col-md-4">
                                                <input name="signers[{{ $index }}][name]" class="form-control" value="{{ $signer['name'] ?? '' }}" placeholder="Nome completo" required>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="email" name="signers[{{ $index }}][email]" class="form-control" value="{{ $signer['email'] ?? '' }}" placeholder="E-mail" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input name="signers[{{ $index }}][document]" class="form-control" value="{{ $signer['document'] ?? '' }}" data-mask="cpf-cnpj" placeholder="CPF/CNPJ">
                                            </div>
                                            <div class="col-md-1 d-grid">
                                                <button type="button" class="btn btn-outline-danger remove-signer" @disabled($loop->first && count($oldSigners) === 1)>×</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted">Até 20 signatários. Quando a ordem estiver marcada, o próximo convite só é liberado após a assinatura anterior.</small>
                            </div>
                            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <span class="text-muted small">Após criar, os convites são enviados por e-mail aos signatários.</span>
                                <button class="btn btn-primary">
                                    <i class="bi bi-send me-1"></i>Criar e enviar para assinatura
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const panels = Array.from(document.querySelectorAll('[data-signature-source-panel]'));
            const radios = Array.from(document.querySelectorAll('input[name="document_source"]'));
            const documentSelect = document.getElementById('legal_document_id');
            const documentPreview = document.querySelector('[data-signature-document-preview]');
            const templateSelect = document.getElementById('template_id');
            const templatePreview = document.querySelector('[data-signature-template-preview]');
            const requestTitle = document.getElementById('title');

            function syncSource() {
                const current = radios.find((radio) => radio.checked)?.value || 'existing';
                panels.forEach((panel) => {
                    const active = panel.dataset.signatureSourcePanel === current;
                    panel.classList.toggle('d-none', !active);
                    panel.querySelectorAll('input, select, textarea').forEach((field) => {
                        if (field.name !== 'document_source') {
                            field.disabled = !active;
                        }
                    });
                });
            }

            radios.forEach((radio) => radio.addEventListener('change', syncSource));
            syncSource();

            function syncDocumentPreview() {
                if (!documentSelect || !documentPreview) {
                    return;
                }

                const option = documentSelect.selectedOptions[0];
                const title = documentPreview.querySelector('[data-document-preview-title]');
                const meta = documentPreview.querySelector('[data-document-preview-meta]');
                const sha = documentPreview.querySelector('[data-document-preview-sha]');
                const download = documentPreview.querySelector('[data-document-preview-download]');

                if (!option || !option.value) {
                    documentPreview.dataset.empty = 'true';
                    if (title) title.textContent = 'Nenhum documento selecionado';
                    if (meta) meta.textContent = 'Escolha um PDF privado na lista para ver cliente, processo, origem e hash.';
                    if (sha) sha.textContent = '';
                    if (download) {
                        download.classList.add('d-none');
                        download.removeAttribute('href');
                    }

                    return;
                }

                const origin = option.dataset.generated === '1'
                    ? `Gerado por template${option.dataset.template ? `: ${option.dataset.template}` : ''}`
                    : 'Documento cadastrado';

                documentPreview.dataset.empty = 'false';
                if (title) title.textContent = option.dataset.title || option.textContent.trim();
                if (meta) {
                    meta.textContent = [
                        origin,
                        option.dataset.client,
                        option.dataset.case,
                        option.dataset.file,
                        option.dataset.size,
                    ].filter(Boolean).join(' • ');
                }
                if (sha) sha.textContent = option.dataset.sha ? `SHA-256: ${option.dataset.sha}` : '';
                if (download) {
                    download.href = option.dataset.downloadUrl || '#';
                    download.classList.remove('d-none');
                }
            }

            documentSelect?.addEventListener('change', syncDocumentPreview);
            syncDocumentPreview();

            function syncTemplatePreview() {
                if (!templateSelect || !templatePreview) {
                    return;
                }

                const option = templateSelect.selectedOptions[0];
                const title = templatePreview.querySelector('[data-template-preview-title]');
                const meta = templatePreview.querySelector('[data-template-preview-meta]');

                if (!option || !option.value) {
                    templatePreview.dataset.empty = 'true';
                    if (title) title.textContent = 'Nenhum modelo selecionado';
                    if (meta) meta.textContent = 'Escolha um modelo para ver escopo, versão e saída.';

                    return;
                }

                templatePreview.dataset.empty = 'false';
                if (title) title.textContent = option.dataset.name || option.textContent.trim();
                if (meta) {
                    meta.textContent = [
                        `Escopo: ${option.dataset.contextLabel || option.dataset.context || 'não informado'}`,
                        `Versão ${option.dataset.version || '1'}`,
                        'Saída: PDF privado para assinatura',
                        option.dataset.titleTemplate ? `Título: ${option.dataset.titleTemplate}` : '',
                    ].filter(Boolean).join(' • ');
                }

                if (requestTitle && !requestTitle.value.trim()) {
                    requestTitle.value = `Assinatura - ${option.dataset.name || option.textContent.trim()}`;
                }
            }

            templateSelect?.addEventListener('change', syncTemplatePreview);
            syncTemplatePreview();

            document.getElementById('upload_legal_case_id')?.addEventListener('change', (event) => {
                const selected = event.target.selectedOptions[0];
                const clientId = selected?.dataset?.clientId;
                const clientField = document.getElementById('upload_client_id');
                if (clientId && clientField && !clientField.value) {
                    clientField.value = clientId;
                }
            });

            document.getElementById('template_legal_case_id')?.addEventListener('change', (event) => {
                const selected = event.target.selectedOptions[0];
                const clientId = selected?.dataset?.clientId;
                const clientField = document.getElementById('template_client_id');
                if (clientId && clientField && !clientField.value) {
                    clientField.value = clientId;
                }
            });

            document.getElementById('add-signer')?.addEventListener('click', () => {
                const box = document.getElementById('signers');
                if (!box || box.children.length >= 20) {
                    return;
                }

                const row = box.firstElementChild.cloneNode(true);
                const index = box.children.length;
                row.querySelectorAll('input').forEach((input) => {
                    input.value = '';
                    input.name = input.name.replace(/signers\[\d+]/, `signers[${index}]`);
                });
                row.querySelector('.remove-signer').disabled = false;
                box.appendChild(row);
            });

            document.getElementById('signers')?.addEventListener('click', (event) => {
                if (!event.target.classList.contains('remove-signer')) {
                    return;
                }

                const box = document.getElementById('signers');
                if (box && box.children.length > 1) {
                    event.target.closest('.signer-row')?.remove();
                }
            });
        })();
    </script>
@endpush
