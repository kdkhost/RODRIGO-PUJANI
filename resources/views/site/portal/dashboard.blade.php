@extends('site.portal.layout')

@php
    $pageTitle = 'Meu acompanhamento';
@endphp

@section('portal_full_width', true)

@section('content')
    @php
        $caseStatusChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => collect($caseStatusBreakdown)->pluck('label')->values(),
                'datasets' => [[
                    'data' => collect($caseStatusBreakdown)->pluck('total')->values(),
                    'backgroundColor' => ['#C49A3C', '#1d4ed8', '#198754', '#dc3545', '#7c3aed', '#0891b2'],
                    'borderWidth' => 0,
                ]],
            ],
            'options' => [
                'plugins' => [
                    'legend' => ['position' => 'bottom'],
                ],
            ],
        ];

        $updatesTrendChart = [
            'type' => 'line',
            'data' => [
                'labels' => collect($updatesTrend)->pluck('label')->values(),
                'datasets' => [[
                    'label' => 'Atualizações',
                    'data' => collect($updatesTrend)->pluck('total')->values(),
                    'borderColor' => '#C49A3C',
                    'backgroundColor' => 'rgba(196,154,60,0.18)',
                    'fill' => true,
                    'tension' => 0.35,
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'x' => ['grid' => ['display' => false]],
                    'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                ],
            ],
        ];

        $documentCategoryChart = [
            'type' => 'bar',
            'data' => [
                'labels' => collect($documentCategoryBreakdown)->pluck('label')->values(),
                'datasets' => [[
                    'label' => 'Documentos',
                    'data' => collect($documentCategoryBreakdown)->pluck('total')->values(),
                    'backgroundColor' => ['#1d4ed8', '#198754', '#C49A3C', '#7c3aed', '#0891b2'],
                    'borderRadius' => 8,
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'x' => ['grid' => ['display' => false]],
                    'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                ],
            ],
        ];
    @endphp

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

    <div class="portal-stats-grid portal-stats-grid-expanded">
        <div class="portal-stat-card">
            <span>Processos visíveis</span>
            <strong>{{ number_format($stats['cases'], 0, ',', '.') }}</strong>
        </div>
        <div class="portal-stat-card">
            <span>Prazos mapeados</span>
            <strong>{{ number_format($stats['deadlines'], 0, ',', '.') }}</strong>
        </div>
        <div class="portal-stat-card">
            <span>Audiências</span>
            <strong>{{ number_format($stats['hearings'], 0, ',', '.') }}</strong>
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

    <div class="portal-dashboard-analytics">
        <section class="portal-section">
            <div class="portal-section-heading">
                <h3>Status da carteira</h3>
            </div>
            <div class="portal-chart-frame">
                <canvas data-site-chart='@json($caseStatusChart)'></canvas>
            </div>
        </section>

        <section class="portal-section">
            <div class="portal-section-heading">
                <h3>Atualizações por mês</h3>
            </div>
            <div class="portal-chart-frame">
                <canvas data-site-chart='@json($updatesTrendChart)'></canvas>
            </div>
        </section>

        <section class="portal-section">
            <div class="portal-section-heading">
                <h3>Documentos por categoria</h3>
            </div>
            <div class="portal-chart-frame">
                <canvas data-site-chart='@json($documentCategoryChart)'></canvas>
            </div>
        </section>
    </div>

    <div class="portal-dashboard-grid">
        <section class="portal-section">
            <div class="portal-section-heading">
                <h3>Próximos marcos</h3>
            </div>

            <div class="portal-timeline portal-timeline-dense">
                @forelse($upcomingMilestones as $milestone)
                    <article class="portal-timeline-item">
                        <div class="portal-timeline-dot"></div>
                        <div>
                            <div class="portal-timeline-header">
                                <strong>{{ $milestone['type'] }}</strong>
                                <small>{{ $milestone['at']->format('d/m/Y H:i') }}</small>
                            </div>
                            <div class="portal-timeline-body">
                                <strong>{{ $milestone['title'] }}</strong>
                                <span>{{ $milestone['subtitle'] }}</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="portal-empty-state portal-empty-state-compact">
                        <span>Nenhum prazo ou audiência futura foi liberado para consulta.</span>
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
                            <span>{{ $document->created_at?->format('d/m/Y') }} • {{ $document->category ?: 'Documento' }}</span>
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

    <div class="portal-section portal-section-spaced">
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

    <div class="portal-section portal-section-spaced">
        <div class="portal-section-heading">
            <h3>Últimas movimentações</h3>
        </div>

        <div class="portal-timeline">
            @forelse($recentUpdates as $update)
                <article class="portal-timeline-item">
                    <div class="portal-timeline-dot"></div>
                    <div>
                        <strong>{{ $update->title }}</strong>
                        <span>{{ $update->legalCase?->title ?: 'Processo' }} • {{ $update->occurred_at?->format('d/m/Y H:i') }}</span>
                    </div>
                </article>
            @empty
                <div class="portal-empty-state portal-empty-state-compact">
                    <span>Sem movimentações recentes.</span>
                </div>
            @endforelse
        </div>
    </div>
@endsection
