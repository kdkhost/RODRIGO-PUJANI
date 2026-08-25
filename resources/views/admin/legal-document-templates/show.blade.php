@extends('admin.layouts.app')

@php
    $latestVersion = $template->versions->sortByDesc('version')->first();
    $latestDefinition = json_encode($latestVersion?->definition ?? ['blocks' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
                                    <div class="mb-3">
                                        <label class="form-label" for="definition_json">Estrutura JSON</label>
                                        <textarea class="form-control font-monospace" id="definition_json" name="definition_json" rows="16" required>{{ old('definition_json', $latestDefinition) }}</textarea>
                                    </div>
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
