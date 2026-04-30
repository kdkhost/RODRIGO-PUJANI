@extends('site.portal.layout')

@php
    $pageTitle = $legalCase->title;
@endphp

@section('portal_full_width', true)

@section('content')
    <div class="portal-dashboard-header">
        <div>
            <span>Processo</span>
            <h2>{{ $legalCase->title }}</h2>
            <p>{{ $legalCase->process_number ?: 'Sem numeração CNJ informada' }}</p>
        </div>
        <div class="portal-header-actions">
            <a href="{{ route('portal.dashboard') }}" class="portal-secondary-button">Voltar ao painel</a>
        </div>
    </div>

    <div class="portal-case-detail-grid">
        <section class="portal-section">
            <div class="portal-section-heading">
                <h3>Resumo do caso</h3>
            </div>

            <div class="portal-rich-text">
                {!! $legalCase->portal_summary ?: ($legalCase->summary ?: '<p>O escritório ainda não publicou um resumo específico para este processo.</p>') !!}
            </div>

            <dl class="portal-case-meta portal-case-meta-wide">
                <div>
                    <dt>Status</dt>
                    <dd>{{ str($legalCase->status)->replace('_', ' ')->headline() }}</dd>
                </div>
                <div>
                    <dt>Fase</dt>
                    <dd>{{ str($legalCase->phase)->replace('_', ' ')->headline() }}</dd>
                </div>
                <div>
                    <dt>Próximo prazo</dt>
                    <dd>{{ $legalCase->next_deadline_at?->format('d/m/Y H:i') ?: 'Sem prazo aberto' }}</dd>
                </div>
                <div>
                    <dt>Próxima audiência</dt>
                    <dd>{{ $legalCase->next_hearing_at?->format('d/m/Y H:i') ?: 'Sem audiência marcada' }}</dd>
                </div>
                <div>
                    <dt>Advogado responsável</dt>
                    <dd>{{ $legalCase->primaryLawyer?->name ?: 'Não definido' }}</dd>
                </div>
                <div>
                    <dt>Parte contrária</dt>
                    <dd>{{ $legalCase->counterparty ?: 'Não informada' }}</dd>
                </div>
            </dl>
        </section>

        <aside class="portal-section">
            <div class="portal-section-heading">
                <h3>Documentos</h3>
            </div>

            <div class="portal-document-list">
                @forelse($documents as $document)
                    <a href="{{ route('portal.documents.download', $document->id) }}" class="portal-document-item">
                        <div>
                            <strong>{{ $document->title }}</strong>
                            <span>{{ $document->created_at?->format('d/m/Y') }} · {{ $document->category ?: 'Documento' }}</span>
                        </div>
                        <small>{{ strtoupper($document->extension ?: 'arquivo') }}</small>
                    </a>
                @empty
                    <div class="portal-empty-state portal-empty-state-compact">
                        <span>Nenhum documento compartilhado para este processo.</span>
                    </div>
                @endforelse
            </div>
        </aside>
    </div>

    <section class="portal-section portal-section-spaced">
        <div class="portal-section-heading">
            <h3>Histórico de andamentos</h3>
        </div>

        <div class="portal-timeline portal-timeline-dense">
            @forelse($updates as $update)
                <article class="portal-timeline-item">
                    <div class="portal-timeline-dot"></div>
                    <div class="portal-timeline-body">
                        <div class="portal-timeline-header">
                            <strong>{{ $update->title }}</strong>
                            <small>{{ $update->occurred_at?->format('d/m/Y H:i') }}</small>
                        </div>
                        @if(filled($update->body))
                            <div class="portal-rich-text portal-rich-text-sm">{!! $update->body !!}</div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="portal-empty-state portal-empty-state-compact">
                    <span>O histórico deste processo ainda não possui andamentos publicados.</span>
                </div>
            @endforelse
        </div>
    </section>
@endsection
