@extends('admin.layouts.app')

@php
    $pageTitle = 'Revisão de publicação do DJEN';
    $statusLabels = ['pending' => 'Pendente', 'approved' => 'Aprovada', 'rejected' => 'Rejeitada'];
    $statusClasses = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'secondary'];
@endphp

@section('content')
<div class="app-content-header"><div class="container-fluid d-flex justify-content-between align-items-center gap-3"><div><h1 class="mb-1">Revisão de publicação</h1><p class="text-muted mb-0">Comunicação {{ $publication->communication_number ?: '#'.$publication->id }} · {{ $publication->tribunal ?: 'Tribunal não informado' }}</p></div><a class="btn btn-outline-secondary" href="{{ route('admin.djen-publications.index') }}"><i class="bi bi-arrow-left me-1"></i>Voltar</a></div></div>

<div class="app-content"><div class="container-fluid"><div class="row g-4">
    <div class="col-xl-8">
        <div class="card mb-4"><div class="card-header d-flex justify-content-between align-items-center"><strong>Teor bruto preservado</strong><span class="badge text-bg-{{ $statusClasses[$publication->review_status] ?? 'secondary' }}">{{ $statusLabels[$publication->review_status] ?? $publication->review_status }}</span></div><div class="card-body"><div class="alert alert-warning"><i class="bi bi-eye-slash me-2"></i>Este conteúdo não é visível ao cliente enquanto estiver pendente ou rejeitado.</div><div class="border rounded p-3 bg-body-tertiary" style="white-space: pre-wrap">{{ $publication->raw_text ?: 'A API não forneceu teor textual para esta comunicação.' }}</div></div></div>
        @if($publication->legalCaseUpdate)
            @php
                $summaryStatusLabels = ['draft' => 'Rascunho por IA', 'reviewed' => 'Revisado', 'approved' => 'Aprovado', 'published' => 'Publicado ao cliente', 'rejected' => 'Rejeitado'];
                $summaryStatusClasses = ['draft' => 'warning', 'reviewed' => 'info', 'approved' => 'primary', 'published' => 'success', 'rejected' => 'secondary'];
            @endphp
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center gap-3">
                    <div><strong>Resumo assistido para o cliente</strong><div class="text-muted small">O texto original permanece preservado. Nenhuma versão chega ao portal sem revisão e publicação humana.</div></div>
                    @if($canGenerateSummary)
                        <form method="POST" action="{{ route('admin.legal-update-summaries.generate', $publication->legalCaseUpdate) }}" data-ajax-form>
                            @csrf
                            <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-stars me-1"></i>Gerar nova versão</button>
                        </form>
                    @endif
                </div>
                <div class="card-body">
                    @forelse($publication->legalCaseUpdate->summaries->sortByDesc('version') as $summary)
                        <section class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                <strong>Versão {{ $summary->version }}</strong>
                                <span class="badge text-bg-{{ $summaryStatusClasses[$summary->status] ?? 'secondary' }}">{{ $summaryStatusLabels[$summary->status] ?? $summary->status }}</span>
                            </div>
                            <p class="small text-muted">Provedor/modelo: {{ $summary->provider }} / {{ $summary->model }} · Gerado em {{ $summary->generated_at?->format('d/m/Y H:i') ?: 'não informado' }}</p>

                            @if($canReviewSummary && in_array($summary->status, ['draft', 'reviewed'], true))
                                <form method="POST" action="{{ route('admin.legal-update-summaries.update', $summary) }}" data-ajax-form class="mb-3">
                                    @csrf
                                    @method('PUT')
                                    <label class="form-label" for="summary-text-{{ $summary->id }}">Texto sob revisão humana</label>
                                    <textarea class="form-control mb-2" id="summary-text-{{ $summary->id }}" name="summary_text" rows="6" maxlength="30000" required>{{ $summary->summary_text }}</textarea>
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Salvar revisão</button>
                                </form>
                                <div class="d-flex flex-wrap gap-2">
                                    @if($canApproveSummary)<form method="POST" action="{{ route('admin.legal-update-summaries.approve', $summary) }}" data-ajax-form>@csrf<button class="btn btn-sm btn-success" type="submit">Aprovar</button></form>@endif
                                    <form method="POST" action="{{ route('admin.legal-update-summaries.reject', $summary) }}" data-ajax-form class="d-flex gap-2">@csrf<input class="form-control form-control-sm" name="reason" maxlength="2000" placeholder="Motivo da rejeição" required><button class="btn btn-sm btn-outline-danger" type="submit">Rejeitar</button></form>
                                </div>
                            @else
                                <div class="border rounded bg-body-tertiary p-3 mb-3" style="white-space: pre-wrap">{{ $summary->summary_text }}</div>
                                @if($summary->status === 'approved' && $canPublishSummary)
                                    <form method="POST" action="{{ route('admin.legal-update-summaries.publish', $summary) }}" data-ajax-form>@csrf<button class="btn btn-sm btn-success" type="submit" data-confirm-submit="true" data-confirm-title="Publicar resumo no portal?" data-confirm-text="Somente esta versão revisada será exibida ao cliente.">Publicar ao cliente</button></form>
                                @endif
                            @endif
                        </section>
                    @empty
                        <p class="text-muted mb-0">Nenhum resumo foi gerado. A publicação original continua disponível somente para revisão administrativa.</p>
                    @endforelse
                </div>
            </div>
        @endif

        <div class="card"><div class="card-header"><strong>Resposta original da API</strong></div><div class="card-body"><p class="text-muted small">Registro integral para conferência e auditoria administrativa. Não é encaminhado ao portal.</p><pre class="border rounded bg-dark text-light p-3 mb-0 overflow-auto" style="max-height: 34rem"><code>{{ json_encode($publication->raw_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre></div></div>
    </div>
    <div class="col-xl-4">
        <div class="card mb-4"><div class="card-header"><strong>Metadados</strong></div><div class="card-body"><dl class="row mb-0"><dt class="col-5">Processo</dt><dd class="col-7">{{ $publication->process_number_normalized ?: 'Não informado' }}</dd><dt class="col-5">Vínculo</dt><dd class="col-7">{{ $publication->legalCase?->title ?: 'Não vinculado' }}</dd><dt class="col-5">Cliente</dt><dd class="col-7">{{ $publication->client?->name ?: 'Não vinculado' }}</dd><dt class="col-5">Data</dt><dd class="col-7">{{ $publication->availability_date?->format('d/m/Y') ?: 'Não informada' }}</dd><dt class="col-5">Tipo</dt><dd class="col-7">{{ $publication->communication_type ?: 'Não informado' }}</dd><dt class="col-5">Órgão</dt><dd class="col-7">{{ $publication->court_body ?: 'Não informado' }}</dd><dt class="col-5">Hash fonte</dt><dd class="col-7 text-break"><code>{{ $publication->source_hash ?: 'Não informado' }}</code></dd><dt class="col-5">SHA-256 local</dt><dd class="col-7 text-break"><code>{{ $publication->content_hash }}</code></dd></dl>@if($publication->source_link)<hr><p class="small mb-0"><strong>Referência informada pelo CNJ</strong><br><span class="text-break">{{ $publication->source_link }}</span></p>@endif</div></div>

        @if($canReview)
        <div class="card"><div class="card-header"><strong>Ação de revisão</strong></div><div class="card-body">
            @if($publication->review_status === 'pending')
                <form method="POST" action="{{ route('admin.djen-publications.approve', $publication) }}" class="mb-4">@csrf<label class="form-label">Processo de destino</label><select name="legal_case_id" class="form-select mb-3"><option value="">Usar vínculo identificado automaticamente</option>@foreach($legalCases as $case)<option value="{{ $case->id }}" @selected($publication->legal_case_id === $case->id)>{{ $case->title }} · {{ $case->process_number }}</option>@endforeach</select><label class="form-label">Observações</label><textarea name="notes" class="form-control mb-3" rows="3" maxlength="4000"></textarea><button class="btn btn-success w-100" type="submit"><i class="bi bi-check2-circle me-1"></i>Aprovar e liberar ao cliente</button></form>
                <form method="POST" action="{{ route('admin.djen-publications.reject', $publication) }}">@csrf<label class="form-label">Motivo da rejeição</label><textarea name="notes" class="form-control mb-3" rows="3" minlength="5" maxlength="4000" required></textarea><button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-slash-circle me-1"></i>Rejeitar sem publicar</button></form>
            @else
                <p><strong>Revisado por:</strong> {{ $publication->reviewer?->name ?: 'Não identificado' }}<br><strong>Em:</strong> {{ $publication->reviewed_at?->format('d/m/Y H:i:s') ?: 'Não informado' }}</p>@if($publication->review_notes)<div class="border rounded p-3 bg-body-tertiary mb-3">{{ $publication->review_notes }}</div>@endif<form method="POST" action="{{ route('admin.djen-publications.reopen', $publication) }}">@csrf<textarea name="notes" class="form-control mb-3" rows="2" maxlength="4000" placeholder="Motivo para reabrir a revisão"></textarea><button class="btn btn-outline-warning w-100" type="submit"><i class="bi bi-arrow-counterclockwise me-1"></i>Reabrir e ocultar do cliente</button></form>
            @endif
        </div></div>
        @endif
    </div>
</div></div></div>
@endsection
