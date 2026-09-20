<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Assinatura eletrônica</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
<main class="container py-5" style="max-width:920px">
    <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3 mb-4">
                <div>
                    <span class="text-uppercase text-muted small fw-bold">Documento para assinatura</span>
                    <h1 class="h3 mb-2">{{ $signer->signatureRequest->title }}</h1>
                    @if($signer->signatureRequest->message)
                        <p class="mb-0">{{ $signer->signatureRequest->message }}</p>
                    @endif
                </div>
                <a class="btn btn-outline-primary" href="{{ route('signatures.public.document', $token) }}" target="_blank" rel="noopener">
                    Abrir PDF original
                </a>
            </div>

            <div class="alert alert-info">
                Antes de assinar, abra e leia o PDF original. Ele está protegido pelo SHA-256
                <code>{{ $signer->signatureRequest->document->sha256 }}</code>.
            </div>

            <p>
                Signatário: <strong>{{ $signer->name }}</strong><br>
                Prazo: {{ $signer->token_expires_at?->format('d/m/Y H:i') }}
            </p>

            <form method="POST" action="{{ route('signatures.public.sign', $token) }}" class="border rounded p-3 mb-3">
                @csrf
                <label class="form-label" for="signature-name">Confirme seu nome completo</label>
                <input id="signature-name" name="name" class="form-control mb-2" required>

                @if($signer->document_normalized)
                    <label class="form-label" for="signature-document">Confirme seu CPF/CNPJ</label>
                    <input id="signature-document" name="document" class="form-control mb-2" data-mask="cpf-cnpj" required>
                @endif

                <div class="form-check my-3">
                    <input type="checkbox" name="consent" value="1" class="form-check-input" id="consent" required>
                    <label class="form-check-label" for="consent">
                        Li o documento PDF original e concordo em assiná-lo eletronicamente, com registro de data, hora, IP, dispositivo e integridade.
                    </label>
                </div>

                <button class="btn btn-success w-100">Assinar documento</button>
            </form>

            <form method="POST" action="{{ route('signatures.public.decline', $token) }}">
                @csrf
                <label class="form-label" for="decline-reason">Motivo da recusa</label>
                <textarea id="decline-reason" name="reason" class="form-control mb-2" minlength="5" required></textarea>
                <button class="btn btn-outline-danger">Recusar assinatura</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
