@if(!empty($generatedDocument))
    @php
        $canSendGeneratedDocument = \App\Services\ElectronicSignatureService::supports($generatedDocument)
            && filled($generatedDocument->client_id)
            && config('signatures.enabled', false);
    @endphp

    <div class="alert alert-success admin-generated-document-alert d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
        <div>
            <div class="fw-semibold">
                <i class="bi bi-check-circle me-1"></i>
                Documento gerado e salvo em Jurídico &gt; Documentos.
            </div>
            <div class="small">
                <strong>{{ $generatedDocument->title }}</strong>
                @if($generatedDocument->client)
                    <span class="text-muted"> · {{ $generatedDocument->client->name }}</span>
                @endif
                @if($generatedDocument->legalCase)
                    <span class="text-muted"> · {{ $generatedDocument->legalCase->title }}</span>
                @endif
            </div>
            @if(! $canSendGeneratedDocument)
                <div class="small text-muted mt-1">
                    Para envio por assinatura, o documento precisa ser PDF, ter cliente vinculado e a assinatura eletrônica precisa estar ativa.
                </div>
            @endif
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.legal-documents.download', $generatedDocument) }}">
                <i class="bi bi-download me-1"></i>Baixar
            </a>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-modal-url="{{ route($routeBase.'.edit', $generatedDocument->id) }}">
                <i class="bi bi-pencil-square me-1"></i>Editar cadastro
            </button>
            @if($canSendGeneratedDocument)
                @can('sendForSignature', $generatedDocument)
                    <a class="btn btn-sm btn-success" href="{{ route('admin.signature-requests.create', ['document' => $generatedDocument->id]) }}">
                        <i class="bi bi-send me-1"></i>Enviar para assinatura
                    </a>
                @endcan
            @endif
        </div>
    </div>
@endif
