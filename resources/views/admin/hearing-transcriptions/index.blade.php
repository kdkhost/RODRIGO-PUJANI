@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Produtividade com revisão humana</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Áudio privado, transcrição preservada e ata estruturada que somente pode ser aprovada por usuário autorizado.</p>
                </div>
                <span class="badge badge-soft-{{ $providerEnabled ? 'success' : 'warning' }}">Provedor {{ $providerEnabled ? 'configurado' : 'pendente' }}</span>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card admin-premium-card mb-4">
                <div class="card-header"><strong>Nova audiência</strong></div>
                <div class="card-body">
                    <form action="{{ route('admin.hearing-transcriptions.store') }}" method="POST" enctype="multipart/form-data" data-ajax-form id="hearing-upload-form">
                        @csrf
                        <div class="row g-3 admin-premium-form">
                            <div class="col-md-5">
                                <label class="form-label">Título</label>
                                <input type="text" name="title" class="form-control" maxlength="255" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cliente</label>
                                <select name="client_id" class="form-select">
                                    <option value="">Opcional</option>
                                    @foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Processo</label>
                                <select name="legal_case_id" class="form-select">
                                    <option value="">Opcional</option>
                                    @foreach($cases as $case)<option value="{{ $case->id }}" data-client-id="{{ $case->client_id }}">{{ $case->title }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Evento da agenda</label>
                                <select name="calendar_event_id" class="form-select">
                                    <option value="">Sem vínculo</option>
                                    @foreach($events as $event)<option value="{{ $event->id }}">{{ $event->start_at?->format('d/m/Y H:i') }} · {{ $event->title }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Áudio</label>
                                <input type="file" name="audio" id="hearing-audio-file" class="form-control" accept="audio/mpeg,audio/wav,audio/ogg,audio/webm,audio/mp4,.mp3,.wav,.ogg,.webm,.m4a,.mp4" required>
                                <input type="hidden" name="duration_seconds" id="hearing-duration-seconds">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="recording_legal_notice" value="1" id="recording-legal-notice" required>
                                    <label class="form-check-label" for="recording-legal-notice">Confirmo que devo observar as regras legais, éticas e profissionais aplicáveis à gravação desta audiência ou reunião.</label>
                                </div>
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                                <button type="button" class="btn btn-outline-primary" id="hearing-record-start"><i class="bi bi-mic me-1"></i>Gravar no navegador</button>
                                <button type="button" class="btn btn-outline-danger d-none" id="hearing-record-stop"><i class="bi bi-stop-circle me-1"></i>Parar gravação</button>
                                <span class="text-muted" id="hearing-record-status">Envie um arquivo ou grave com autorização.</span>
                                <button type="submit" class="btn btn-primary ms-auto"><i class="bi bi-cloud-arrow-up me-1"></i>Armazenar e processar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card admin-premium-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Audiência</th><th>Processo / cliente</th><th>Arquivo privado</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
                        <tbody>
                            @forelse($records as $record)
                                <tr>
                                    <td><div class="admin-entity-title">{{ $record->title }}</div><div class="admin-entity-meta">{{ $record->created_at?->format('d/m/Y H:i') }}</div></td>
                                    <td><div>{{ $record->legalCase?->title ?: 'Sem processo' }}</div><small class="text-muted">{{ $record->client?->name ?: 'Sem cliente' }}</small></td>
                                    <td><div>{{ $record->original_name }}</div><small class="text-muted">{{ number_format($record->size / 1048576, 2, ',', '.') }} MB · SHA-256 registrado</small></td>
                                    <td><span class="badge badge-soft-info">{{ str($record->status)->replace('_', ' ')->headline() }}</span></td>
                                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.hearing-transcriptions.show', $record) }}">Revisar</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-5 text-muted">Nenhuma audiência enviada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">{{ $records->links() }}</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const startButton = document.getElementById('hearing-record-start');
    const stopButton = document.getElementById('hearing-record-stop');
    const status = document.getElementById('hearing-record-status');
    const fileInput = document.getElementById('hearing-audio-file');
    const durationInput = document.getElementById('hearing-duration-seconds');
    let recorder = null;
    let stream = null;
    let chunks = [];
    let startedAt = 0;

    startButton?.addEventListener('click', async () => {
        if (!document.getElementById('recording-legal-notice')?.checked) {
            window.Swal?.fire?.({ icon: 'warning', title: 'Confirmação necessária', text: 'Leia e confirme o aviso legal antes de gravar.' });
            return;
        }
        if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
            status.textContent = 'Este navegador não oferece gravação compatível. Envie um arquivo de áudio.';
            return;
        }
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        const preferred = ['audio/webm;codecs=opus', 'audio/ogg;codecs=opus'].find((type) => MediaRecorder.isTypeSupported(type));
        recorder = new MediaRecorder(stream, preferred ? { mimeType: preferred } : undefined);
        chunks = [];
        recorder.addEventListener('dataavailable', (event) => { if (event.data.size) chunks.push(event.data); });
        recorder.addEventListener('stop', () => {
            const mime = recorder.mimeType || 'audio/webm';
            const extension = mime.includes('ogg') ? 'ogg' : 'webm';
            const blob = new Blob(chunks, { type: mime });
            const file = new File([blob], `audiencia-${Date.now()}.${extension}`, { type: mime });
            const transfer = new DataTransfer();
            transfer.items.add(file);
            fileInput.files = transfer.files;
            durationInput.value = Math.max(1, Math.ceil((Date.now() - startedAt) / 1000));
            status.textContent = `Gravação pronta (${durationInput.value}s).`;
            stream?.getTracks().forEach((track) => track.stop());
        });
        recorder.start(1000);
        startedAt = Date.now();
        startButton.classList.add('d-none');
        stopButton.classList.remove('d-none');
        status.textContent = 'Gravando...';
    });

    stopButton?.addEventListener('click', () => {
        if (recorder?.state === 'recording') recorder.stop();
        stopButton.classList.add('d-none');
        startButton.classList.remove('d-none');
    });
});
</script>
@endpush
