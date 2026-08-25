@extends('admin.layouts.app')

@php
    $creating = ! $template->exists;
    $defaultDefinition = [
        'blocks' => [
            ['type' => 'heading', 'level' => 2, 'text' => 'Dados do cliente'],
            ['type' => 'paragraph', 'text' => 'Cliente: {{client.name}} — CPF/CNPJ: {{client.document_number}}'],
            ['type' => 'paragraph', 'text' => 'Documento emitido em {{system.current_date}} por {{generator.name}}.'],
        ],
    ];
    $definitionValue = old(
        'definition_json',
        json_encode($latestVersion?->definition ?? $defaultDefinition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
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
            <form method="POST" action="{{ $creating ? route('admin.legal-document-templates.store') : route('admin.legal-document-templates.update', $template) }}">
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
                                            <option value="{{ $value }}" @selected(old('default_output_format', $template->default_output_format ?: App\Models\LegalDocumentTemplate::FORMAT_DOCX) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
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
                                <div class="card-header"><strong>Versão inicial imutável</strong></div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label" for="title_template">Título do documento</label>
                                        <input class="form-control" id="title_template" name="title_template" maxlength="255" required value="{{ old('title_template', 'Documento de {{client.name}}') }}">
                                    </div>
                                    <div>
                                        <label class="form-label" for="definition_json">Estrutura JSON</label>
                                        <textarea class="form-control font-monospace" id="definition_json" name="definition_json" rows="18" required>{{ $definitionValue }}</textarea>
                                        <div class="form-text">Somente blocos heading, paragraph, list, page_break e spacer são aceitos. HTML e código executável não são processados.</div>
                                    </div>
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
