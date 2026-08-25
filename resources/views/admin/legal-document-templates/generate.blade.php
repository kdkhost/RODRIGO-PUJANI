@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center gap-3">
            <div>
                <h1>Gerar documento jurídico</h1>
                <p class="text-muted mb-0">{{ $template->name }} · {{ App\Models\LegalDocumentTemplate::contextScopes()[$template->context_scope] ?? $template->context_scope }}</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('admin.legal-document-templates.show', $template) }}">Voltar</a>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-xl-8">
                    <form method="POST" action="{{ route('admin.legal-document-templates.generate.store', $template) }}">
                        @csrf
                        <div class="card">
                            <div class="card-header"><strong>Dados da geração</strong></div>
                            <div class="card-body row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="legal_document_template_version_id">Versão</label>
                                    <select class="form-select" id="legal_document_template_version_id" name="legal_document_template_version_id" required>
                                        @foreach($versions as $version)
                                            <option value="{{ $version->id }}" @selected((string) old('legal_document_template_version_id', $versions->first()?->id) === (string) $version->id)>
                                                v{{ $version->version }} · {{ $version->created_at?->format('d/m/Y H:i') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="output_format">Formato</label>
                                    <select class="form-select" id="output_format" name="output_format" required>
                                        @foreach($outputFormats as $value => $label)
                                            <option value="{{ $value }}" @selected(old('output_format', $template->default_output_format) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @if($clients->isNotEmpty())
                                    <div class="col-12">
                                        <label class="form-label" for="client_id">Cliente</label>
                                        <select class="form-select" id="client_id" name="client_id" required>
                                            <option value="">Selecione</option>
                                            @foreach($clients as $client)
                                                <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>
                                                    {{ $client->name }}{{ $client->document_number ? ' · '.$client->document_number : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @if($cases->isNotEmpty())
                                    <div class="col-12">
                                        <label class="form-label" for="legal_case_id">Processo</label>
                                        <select class="form-select" id="legal_case_id" name="legal_case_id" required>
                                            <option value="">Selecione</option>
                                            @foreach($cases as $case)
                                                <option value="{{ $case->id }}" data-client-id="{{ $case->client_id }}" @selected((string) old('legal_case_id') === (string) $case->id)>
                                                    {{ $case->title }}{{ $case->process_number ? ' · '.$case->process_number : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($template->context_scope === App\Models\LegalDocumentTemplate::CONTEXT_CLIENT_CASE)
                                            <div class="form-text">A lista é filtrada pelo cliente selecionado.</div>
                                        @endif
                                    </div>
                                @endif

                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="shared_with_client" value="0">
                                        <input class="form-check-input" id="shared_with_client" name="shared_with_client" type="checkbox" value="1" @checked(old('shared_with_client'))>
                                        <label class="form-check-label" for="shared_with_client">Disponibilizar o documento gerado no portal do cliente</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-info mb-0">
                                        O arquivo será armazenado no storage jurídico privado. O sistema manterá a versão do template, o contexto criptografado, o usuário, a data e os hashes SHA-256 para auditoria.
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-end gap-2">
                                <a class="btn btn-outline-secondary" href="{{ route('admin.legal-document-templates.show', $template) }}">Cancelar</a>
                                <button class="btn btn-primary" type="submit"><i class="bi bi-file-earmark-check me-1"></i>Gerar documento</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($template->context_scope === App\Models\LegalDocumentTemplate::CONTEXT_CLIENT_CASE)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const client = document.getElementById('client_id');
                const legalCase = document.getElementById('legal_case_id');
                if (!client || !legalCase) return;

                const options = Array.from(legalCase.options);
                const filterCases = () => {
                    const selectedClient = client.value;
                    options.forEach((option) => {
                        if (!option.value) return;
                        option.hidden = option.dataset.clientId !== selectedClient;
                        option.disabled = option.hidden;
                    });
                    const current = legalCase.options[legalCase.selectedIndex];
                    if (current?.disabled) legalCase.value = '';
                };

                client.addEventListener('change', filterCases);
                filterCases();
            });
        </script>
    @endif
@endsection
