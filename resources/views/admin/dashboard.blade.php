@extends('admin.layouts.app')

@section('content')
    @php
        $visitsChart = [
            'type' => 'line',
            'data' => [
                'labels' => $visitsByDay->pluck('day')->values(),
                'datasets' => [[
                    'label' => 'Visitas',
                    'data' => $visitsByDay->pluck('total')->values(),
                    'borderColor' => '#C49A3C',
                    'backgroundColor' => 'rgba(196,154,60,0.18)',
                    'pointBackgroundColor' => '#C49A3C',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
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

        $caseStatusChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $caseStatusBreakdown->pluck('label')->values(),
                'datasets' => [[
                    'data' => $caseStatusBreakdown->pluck('total')->values(),
                    'backgroundColor' => ['#C49A3C', '#198754', '#3b82f6', '#dc3545', '#7c3aed', '#0891b2'],
                    'borderWidth' => 0,
                ]],
            ],
            'options' => [
                'plugins' => [
                    'legend' => ['position' => 'bottom'],
                ],
            ],
        ];

        $taskStatusChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $taskStatusBreakdown->pluck('label')->values(),
                'datasets' => [[
                    'label' => 'Tarefas',
                    'data' => $taskStatusBreakdown->pluck('total')->values(),
                    'backgroundColor' => ['#C49A3C', '#3b82f6', '#7c3aed', '#198754', '#dc3545'],
                    'borderRadius' => 10,
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

    <div class="app-content-header admin-page-hero admin-dashboard-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Centro de comando</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>{{ $dashboardScopeLabel }} com prazos, agenda, produção e sinais de demanda em uma visão única.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-calendar2-check"></i>
                    <div>
                        <strong>{{ now()->format('d/m/Y') }}</strong>
                        <small>{{ now()->format('H:i') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-3 mb-4">
                @foreach ($overviewCards as $card)
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card admin-stat-card admin-stat-{{ $card['tone'] }} h-100">
                            <div class="card-body">
                                <div class="admin-stat-icon"><i class="bi {{ $card['icon'] }}"></i></div>
                                <div class="admin-stat-label">{{ $card['label'] }}</div>
                                <div class="admin-stat-value">{{ number_format($card['value'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="admin-dashboard-highlight-row mb-4">
                @foreach ($operationalHighlights as $highlight)
                    <div class="admin-dashboard-highlight-card">
                        <span>{{ $highlight['label'] }}</span>
                        <strong>{{ number_format($highlight['value'], 0, ',', '.') }}</strong>
                    </div>
                @endforeach
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-12 col-xl-6">
                    <div class="card admin-chart-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Audiência</div>
                                <h3 class="card-title">Visitas dos últimos 14 dias</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-chart-frame">
                                <canvas data-admin-chart='@json($visitsChart)'></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-6 col-xl-3">
                    <div class="card admin-chart-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Carteira</div>
                                <h3 class="card-title">Status dos processos</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-chart-frame admin-chart-frame-sm">
                                <canvas data-admin-chart='@json($caseStatusChart)'></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-6 col-xl-3">
                    <div class="card admin-chart-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Execução</div>
                                <h3 class="card-title">Pipeline de tarefas</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-chart-frame admin-chart-frame-sm">
                                <canvas data-admin-chart='@json($taskStatusChart)'></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-6">
                    <div class="card admin-list-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Prioridade</div>
                                <h3 class="card-title">Prazos monitorados</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-mini-stack">
                                @forelse ($upcomingDeadlines as $legalCase)
                                    <div class="admin-dashboard-list-item">
                                        <div>
                                            <strong>{{ $legalCase->title }}</strong>
                                            <span>{{ $legalCase->client?->name ?: 'Cliente não informado' }} • {{ $legalCase->primaryLawyer?->name ?: 'Sem responsável' }}</span>
                                        </div>
                                        <small>{{ $legalCase->next_deadline_at?->format('d/m/Y H:i') ?: 'Sem prazo' }}</small>
                                    </div>
                                @empty
                                    <div class="admin-calendar-empty-state admin-calendar-empty-state-compact">
                                        <span>Nenhum prazo relevante encontrado.</span>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card admin-list-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Agenda</div>
                                <h3 class="card-title">Próximos compromissos</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-mini-stack">
                                @forelse ($todayAgenda as $event)
                                    <div class="admin-dashboard-list-item">
                                        <div>
                                            <strong>{{ $event->title }}</strong>
                                            <span>{{ $event->category }} • {{ $event->owner?->name ?: 'Sem responsável' }}</span>
                                        </div>
                                        <small>{{ $event->all_day ? $event->start_at?->format('d/m/Y') : $event->start_at?->format('d/m/Y H:i') }}</small>
                                    </div>
                                @empty
                                    <div class="admin-calendar-empty-state admin-calendar-empty-state-compact">
                                        <span>Sem eventos futuros cadastrados.</span>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($isRestrictedLegalScope)
                <div class="card admin-list-card">
                    <div class="card-header">
                        <div>
                            <div class="admin-card-kicker">Carteira</div>
                            <h3 class="card-title">Andamentos recentes</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="admin-mini-stack">
                            @forelse ($latestUpdates as $update)
                                <div class="admin-dashboard-list-item">
                                    <div>
                                        <strong>{{ $update->title }}</strong>
                                        <span>{{ $update->legalCase?->title ?: 'Processo' }} • {{ $update->client?->name ?: 'Cliente' }}</span>
                                    </div>
                                    <small>{{ $update->occurred_at?->format('d/m/Y H:i') }}</small>
                                </div>
                            @empty
                                <div class="admin-calendar-empty-state admin-calendar-empty-state-compact">
                                    <span>Sem andamentos recentes na sua carteira.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @else
                <div class="row g-4">
                    <div class="col-xl-7">
                        <div class="card admin-list-card h-100">
                            <div class="card-header">
                                <div>
                                    <div class="admin-card-kicker">Atendimento</div>
                                    <h3 class="card-title">Últimas mensagens</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Status</th>
                                            <th>Canal</th>
                                            <th>Data</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse ($latestContacts as $contact)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $contact->name }}</div>
                                                    <div class="small text-muted">{{ $contact->subject ?: ($contact->area_interest ?: 'Sem assunto') }}</div>
                                                </td>
                                                <td><span class="badge badge-soft-info">{{ str($contact->status ?: 'novo')->replace('_', ' ')->headline() }}</span></td>
                                                <td>{{ $contact->email ?: ($contact->phone ?: 'Não informado') }}</td>
                                                <td>{{ $contact->created_at?->format('d/m/Y H:i') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">Nenhuma mensagem registrada.</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-5">
                        <div class="card admin-list-card h-100">
                            <div class="card-header">
                                <div>
                                    <div class="admin-card-kicker">Equipe</div>
                                    <h3 class="card-title">Carga por advogado</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="admin-progress-list">
                                    @forelse ($workloadByLawyer as $lawyer)
                                        <div>
                                            <span>{{ $lawyer->name }}</span>
                                            <strong>{{ $lawyer->open_cases_count }} processo(s) • {{ $lawyer->open_tasks_count }} tarefa(s)</strong>
                                        </div>
                                    @empty
                                        <div class="admin-calendar-empty-state admin-calendar-empty-state-compact">
                                            <span>Nenhuma carga distribuída até o momento.</span>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
