@extends('site.portal.layout')

@php($pageTitle = 'Meu acompanhamento')

@section('content')
    <div class="portal-dashboard-header">
        <div>
            <span>Portal do cliente</span>
            <h2>{{ $client->name }}</h2>
            <p>{{ $client->assignedLawyer?->name ? 'Responsável: '.$client->assignedLawyer->name : 'Acompanhamento jurídico disponível no painel reservado.' }}</p>
        </div>
        <form action="{{ route('portal.logout') }}" method="POST">
            @csrf
            <button type="submit" class="portal-secondary-button">Sair</button>
        </form>
    </div>

    <div class="portal-stats-grid">
        <div class="portal-stat-card">
            <span>Processos visíveis</span>
            <strong>{{ number_format($stats['cases'], 0, ',', '.') }}</strong>
        </div>
        <div class="portal-stat-card">
            <span>Prazos mapeados</span>
            <strong>{{ number_format($stats['deadlines'], 0, ',', '.') }}</strong>
        </div>
        <div class="portal-stat-card">
            <span>Documentos</span>
            <strong>{{ number_format($stats['documents'], 0, ',', '.') }}</strong>
        </div>
        <div class="portal-stat-card">
            <span>Atualizações recentes</span>
            <strong>{{ number_format($stats['updates'], 0, ',', '.') }}</strong>
        </div>
    </div>

    <div class="portal-section">
        <div class="portal-section-heading">
            <h3>Processos em acompanhamento</h3>
        </div>

        <div class="portal-case-grid">
            @forelse($cases as $legalCase)
                <article class="portal-case-card">
                    <div class="portal-case-topline">
                        <span class="portal-chip">{{ str($legalCase->status)->replace('_', ' ')->headline() }}</span>
                        @if($legalCase->latest_court_update_at)
                            <small>CNJ: {{ $legalCase->latest_court_update_at->format('d/m/Y H:i') }}</small>
                        @endif
                    </div>
                    <h4>{{ $legalCase->title }}</h4>
                    <p>{{ $legalCase->process_number ?: 'Número interno: '.($legalCase->internal_code ?: 'não informado') }}</p>
                    <dl class="portal-case-meta">
                        <div>
                            <dt>Área</dt>
                            <dd>{{ $legalCase->practice_area ?: 'Não informada' }}</dd>
                        </div>
                        <div>
                            <dt>Prazo</dt>
                            <dd>{{ $legalCase->next_deadline_at?->format('d/m/Y H:i') ?: 'Sem prazo aberto' }}</dd>
                        </div>
                        <div>
                            <dt>Documentos</dt>
                            <dd>{{ $legalCase->shared_documents_count }}</dd>
                        </div>
                        <div>
                            <dt>Andamentos</dt>
                            <dd>{{ $legalCase->visible_updates_count }}</dd>
                        </div>
                    </dl>
                    <a href="{{ route('portal.cases.show', $legalCase->id) }}" class="portal-link-button">Abrir processo</a>
                </article>
            @empty
                <div class="portal-empty-state">
                    <strong>Nenhum processo liberado para visualização.</strong>
                    <span>Quando o escritório compartilhar um caso com seu acesso, ele aparecerá aqui.</span>
                </div>
            @endforelse
        </div>
    </div>

    <div class="portal-dashboard-grid">
        <section class="portal-section">
            <div class="portal-section-heading">
                <h3>Últimas movimentações</h3>
            </div>

            <div class="portal-timeline">
                @forelse($recentUpdates as $update)
                    <article class="portal-timeline-item">
                        <div class="portal-timeline-dot"></div>
                        <div>
                            <strong>{{ $update->title }}</strong>
                            <span>{{ $update->legalCase?->title ?: 'Processo' }} · {{ $update->occurred_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    </article>
                @empty
                    <div class="portal-empty-state portal-empty-state-compact">
                        <span>Sem movimentações recentes.</span>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="portal-section">
            <div class="portal-section-heading">
                <h3>Documentos compartilhados</h3>
            </div>

            <div class="portal-document-list">
                @forelse($sharedDocuments as $document)
                    <a href="{{ route('portal.documents.download', $document->id) }}" class="portal-document-item">
                        <div>
                            <strong>{{ $document->title }}</strong>
                            <span>{{ $document->created_at?->format('d/m/Y') }} · {{ $document->category ?: 'Documento' }}</span>
                        </div>
                        <small>{{ strtoupper($document->extension ?: 'arquivo') }}</small>
                    </a>
                @empty
                    <div class="portal-empty-state portal-empty-state-compact">
                        <span>Nenhum documento compartilhado até o momento.</span>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
