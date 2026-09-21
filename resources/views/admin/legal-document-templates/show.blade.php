@extends('admin.layouts.app')

@php
    $latestVersion = $template->versions->sortByDesc('version')->first();
    $latestDefinition = json_encode($latestVersion?->definition ?? ['blocks' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $branding = branding_config();
    $brandLogo = $branding['logo_path'] ?? '';
@endphp

@section('content')
    <div class="app-content-header">
        <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1>{{ $template->name }}</h1>
                <p class="text-muted mb-0">{{ $template->slug }} · {{ App\Models\LegalDocumentTemplate::contextScopes()[$template->context_scope] ?? $template->context_scope }}</p>
            </div>
            <div class="d-flex gap-2">
                @can('update', $template)
                    <a class="btn btn-outline-secondary" href="{{ route('admin.legal-document-templates.edit', $template) }}">Editar metadados</a>
                @endcan
                @can('generate', $template)
                    <a class="btn btn-primary" href="{{ route('admin.legal-document-templates.generate.create', $template) }}">
                        <i class="bi bi-file-earmark-plus me-1"></i>Gerar documento
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header"><strong>Versões publicadas</strong></div>
                        <div class="card-body table-responsive">
                            <table class="table align-middle">
                                <thead><tr><th>Versão</th><th>Data</th><th>Responsável</th><th>Tokens</th><th>SHA-256</th></tr></thead>
                                <tbody>
                                    @foreach($template->versions as $version)
                                        <tr>
                                            <td><strong>v{{ $version->version }}</strong></td>
                                            <td>{{ $version->created_at?->format('d/m/Y H:i') }}</td>
                                            <td>{{ $version->creator?->name ?: 'Sistema' }}</td>
                                            <td>{{ count($version->allowed_tokens ?? []) }}</td>
                                            <td><code class="small text-break">{{ $version->content_sha256 }}</code></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @can('createVersion', $template)
                        <div class="card mt-4">
                            <div class="card-header"><strong>Publicar nova versão</strong></div>
                            <div class="card-body">
                                <div class="alert alert-warning">A versão publicada será imutável. Para corrigir conteúdo, publique outra versão.</div>
                                <form method="POST" action="{{ route('admin.legal-document-templates.versions.store', $template) }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label" for="title_template">Título do documento</label>
                                        <input class="form-control" id="title_template" name="title_template" maxlength="255" required value="{{ old('title_template', $latestVersion?->title_template) }}">
                                    </div>
                                    <div class="legal-doc-designer" data-document-designer data-brand-logo="{{ $brandLogo }}" data-background-upload-url="{{ route('admin.legal-document-templates.background-upload') }}" data-csrf-token="{{ csrf_token() }}">
                                        <div class="legal-doc-designer-toolbar">
                                            <div>
                                                <label class="form-label mb-1">Papel timbrado / fundo da página</label>
                                                <input class="form-control form-control-sm" data-doc-bg-path placeholder="Ex.: storage/branding/papel-timbrado.png">
                                                <div class="legal-doc-bg-drop mt-2" data-doc-bg-drop role="button" tabindex="0">
                                                    <input type="file" accept="image/png,image/jpeg,image/webp" hidden data-doc-bg-file>
                                                    <i class="bi bi-cloud-arrow-up"></i>
                                                    <span>Arraste o papel timbrado aqui ou clique para enviar</span>
                                                    <small data-doc-bg-status>PNG, JPG ou WEBP até 10 MB. Aplicado na página selecionada.</small>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="form-label mb-1">Opacidade do fundo</label>
                                                <input class="form-range" type="range" min="0" max="1" step="0.01" data-doc-bg-opacity>
                                            </div>
                                            <div>
                                                <label class="form-label mb-1">Encaixe no A4</label>
                                                <select class="form-select form-select-sm" data-doc-bg-fit>
                                                    <option value="cover">Ocupar A4 inteiro</option>
                                                    <option value="contain">Conter sem cortar</option>
                                                    <option value="stretch">Esticar</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-doc-add="text">Texto</button>
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-doc-add="signature">Assinatura</button>
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-doc-add="line">Linha</button>
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-doc-add="rectangle">Caixa</button>
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-doc-add="logo" data-logo-path="{{ $brandLogo }}">Logo</button>
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-doc-add-page>Nova página</button>
                                        </div>
                                        <div class="legal-doc-designer-grid">
                                            <div class="legal-doc-page-list" data-doc-pages></div>
                                            <aside class="legal-doc-inspector">
                                                <h3 class="h6">Item selecionado</h3>
                                                <div class="row g-2">
                                                    <div class="col-6"><label class="form-label">X mm</label><input class="form-control form-control-sm" type="number" step="0.1" data-doc-field="x_mm"></div>
                                                    <div class="col-6"><label class="form-label">Y mm</label><input class="form-control form-control-sm" type="number" step="0.1" data-doc-field="y_mm"></div>
                                                    <div class="col-6"><label class="form-label">Largura</label><input class="form-control form-control-sm" type="number" step="0.1" data-doc-field="w_mm"></div>
                                                    <div class="col-6"><label class="form-label">Altura</label><input class="form-control form-control-sm" type="number" step="0.1" data-doc-field="h_mm"></div>
                                                    <div class="col-6"><label class="form-label">Opacidade</label><input class="form-control form-control-sm" type="number" min="0" max="1" step="0.01" data-doc-field="opacity"></div>
                                                    <div class="col-6" data-doc-type-field="text"><label class="form-label">Fonte</label><input class="form-control form-control-sm" type="number" step="0.5" data-doc-field="font_size_pt"></div>
                                                    <div class="col-12" data-doc-type-field="text"><label class="form-label">Texto</label><textarea class="form-control form-control-sm" rows="4" data-doc-field="text"></textarea></div>
                                                    <div class="col-12" data-doc-type-field="signature"><label class="form-label">Rótulo</label><input class="form-control form-control-sm" data-doc-field="label"></div>
                                                    <div class="col-6" data-doc-type-field="signature"><label class="form-label">Ordem</label><input class="form-control form-control-sm" type="number" min="1" step="1" data-doc-field="signer_order"></div>
                                                    <div class="col-6" data-doc-type-field="signature"><label class="form-label">Obrigatório</label><select class="form-select form-select-sm" data-doc-field="required"><option value="true">Sim</option><option value="false">Opcional/testemunha</option></select></div>
                                                    <div class="col-12" data-doc-type-field="image"><label class="form-label">Imagem</label><input class="form-control form-control-sm" data-doc-field="image_path"></div>
                                                    <div class="col-12"><button class="btn btn-sm btn-outline-danger" type="button" data-doc-remove>Remover item</button></div>
                                                </div>
                                            </aside>
                                        </div>
                                    </div>
                                    <textarea class="form-control font-monospace mt-3" id="definition_json" name="definition_json" rows="10" required hidden>{{ old('definition_json', $latestDefinition) }}</textarea>
                                    <details class="mt-3">
                                        <summary>Avançado: ver JSON gerado automaticamente</summary>
                                        <textarea class="form-control font-monospace mt-2" rows="10" data-doc-json-mirror readonly></textarea>
                                    </details>
                                    <button class="btn btn-primary" type="submit">Publicar versão {{ ((int) $latestVersion?->version) + 1 }}</button>
                                </form>
                            </div>
                        </div>
                    @endcan
                </div>

                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header"><strong>Metadados</strong></div>
                        <div class="card-body">
                            <p><strong>Status:</strong> {{ $template->is_active ? 'Ativo' : 'Inativo' }}</p>
                            <p><strong>Formato padrão:</strong> {{ App\Models\LegalDocumentTemplate::outputFormats()[$template->default_output_format] ?? $template->default_output_format }}</p>
                            <p><strong>Criado por:</strong> {{ $template->creator?->name ?: 'Sistema' }}</p>
                            <p class="mb-0"><strong>Descrição:</strong><br>{{ $template->description ?: 'Sem descrição.' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4 mb-4">
                <div class="card-header"><strong>Últimas gerações</strong></div>
                <div class="card-body table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Data</th><th>Documento</th><th>Versão</th><th>Contexto</th><th>Responsável</th><th>Integridade</th><th></th></tr></thead>
                        <tbody>
                            @forelse($template->generations as $generation)
                                <tr>
                                    <td>{{ $generation->generated_at?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $generation->legalDocument?->title ?: 'Documento removido' }}</td>
                                    <td>v{{ $generation->templateVersion?->version }}</td>
                                    <td>
                                        {{ $generation->client?->name }}
                                        @if($generation->legalCase)
                                            <small class="d-block text-muted">{{ $generation->legalCase->title }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $generation->generator?->name ?: 'Sistema' }}</td>
                                    <td><code class="small">{{ Str::limit($generation->rendered_sha256, 16, '…') }}</code></td>
                                    <td class="text-end">
                                        @if($generation->legalDocument)
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.legal-documents.download', $generation->legalDocument) }}">Baixar</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Nenhum documento foi gerado com este template.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
