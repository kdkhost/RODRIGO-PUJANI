@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div><div class="admin-eyebrow">Revisão humana obrigatória</div><h1>{{ $record->title }}</h1><p>{{ $record->legalCase?->title ?: 'Sem processo' }} · {{ $record->client?->name ?: 'Sem cliente' }}</p></div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-primary" href="{{ route('admin.hearing-transcriptions.download', $record) }}"><i class="bi bi-download me-1"></i>Áudio</a>
                    @if($record->minutes_draft)<a class="btn btn-outline-primary" href="{{ route('admin.hearing-transcriptions.export', $record) }}"><i class="bi bi-file-earmark-word me-1"></i>Exportar Word</a>@endif
                </div>
            </div>
        </div>
    </div>

    <div class="app-content"><div class="container-fluid">
        @if(in_array($record->status, ['failed', 'configuration_required', 'uploaded', 'queued'], true))
            <div class="alert alert-warning d-flex justify-content-between align-items-center">
                <span>{{ $record->processing_error ?: 'O conteúdo ainda não foi processado.' }}</span>
                <form action="{{ route('admin.hearing-transcriptions.process', $record) }}" method="POST" data-ajax-form>@csrf<button class="btn btn-sm btn-warning">Processar</button></form>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-xl-5">
                <div class="card admin-premium-card h-100"><div class="card-header"><strong>Transcrição original preservada</strong></div><div class="card-body"><pre class="text-wrap mb-0" style="white-space:pre-wrap">{{ $record->transcript_original ?: 'Aguardando processamento.' }}</pre></div></div>
            </div>
            <div class="col-xl-7">
                <form action="{{ route('admin.hearing-transcriptions.update', $record) }}" method="POST" data-ajax-form>
                    @csrf @method('PUT')
                    <div class="card admin-premium-card mb-4"><div class="card-header"><strong>Transcrição revisável</strong></div><div class="card-body"><textarea name="transcript_edited" class="form-control" rows="14" required @disabled($record->review_status === 'approved')>{{ $record->transcript_edited }}</textarea></div></div>
                    <div class="card admin-premium-card"><div class="card-header"><strong>Minuta de ata</strong></div><div class="card-body"><textarea name="minutes_draft" class="form-control" rows="16" required @disabled($record->review_status === 'approved')>{{ $record->minutes_draft }}</textarea>
                        @if($record->review_status !== 'approved')<div class="d-flex justify-content-end mt-3"><button class="btn btn-primary">Salvar revisão</button></div>@endif
                    </div></div>
                </form>
                @if($record->review_status === 'reviewed')
                    <form action="{{ route('admin.hearing-transcriptions.approve', $record) }}" method="POST" data-ajax-form class="mt-3">@csrf<button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Aprovar ata revisada</button></form>
                @elseif($record->review_status === 'approved')
                    <div class="alert alert-success mt-3">Ata aprovada por {{ $record->approver?->name }} em {{ $record->approved_at?->format('d/m/Y H:i') }}.</div>
                @endif
            </div>
        </div>
    </div></div>
@endsection
