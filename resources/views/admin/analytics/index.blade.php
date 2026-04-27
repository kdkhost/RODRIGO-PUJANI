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

        $devicesChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $devices->pluck('label')->values(),
                'datasets' => [[
                    'data' => $devices->pluck('total')->values(),
                    'backgroundColor' => ['#C49A3C', '#3b82f6', '#198754', '#dc3545', '#7c3aed'],
                    'borderWidth' => 0,
                ]],
            ],
            'options' => ['plugins' => ['legend' => ['position' => 'bottom']]],
        ];

        $browsersChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $browsers->take(6)->pluck('label')->values(),
                'datasets' => [[
                    'label' => 'Navegadores',
                    'data' => $browsers->take(6)->pluck('total')->values(),
                    'backgroundColor' => ['#3b82f6', '#C49A3C', '#198754', '#7c3aed', '#0891b2', '#dc3545'],
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

        $leadsChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $leadStats->pluck('label')->values(),
                'datasets' => [[
                    'label' => 'Leads',
                    'data' => $leadStats->pluck('total')->values(),
                    'backgroundColor' => '#C49A3C',
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

    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Inteligência do site</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Visão premium do tráfego, da captação comercial e do comportamento recente dos visitantes.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-activity"></i>
                    <div>
                        <strong>Janela de {{ $window }} dias</strong>
                        <small>{{ number_format($latestVisits->count(), 0, ',', '.') }} visitas recentes</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form method="GET" class="admin-table-toolbar mb-4">
                <div class="admin-search-box">
                    <i class="bi bi-calendar3"></i>
                    <input type="text" class="form-control" value="Análise consolidada do período selecionado" readonly>
                </div>
                <select name="window" class="form-select" onchange="this.form.submit()">
                    @foreach($availableWindows as $days)
                        <option value="{{ $days }}" @selected($window === $days)>{{ $days }} dias</option>
                    @endforeach
                </select>
            </form>

            <div class="row g-3 mb-4">
                @foreach ($kpis as $card)
                    <div class="col-md-6 col-xl-3">
                        <div class="card admin-stat-card admin-stat-{{ $card['tone'] }} h-100">
                            <div class="card-body">
                                <div class="admin-stat-icon"><i class="bi {{ $card['icon'] }}"></i></div>
                                <div class="admin-stat-label">{{ $card['label'] }}</div>
                                <div class="admin-stat-value">
                                    {{ number_format($card['value'], isset($card['suffix']) ? 2 : 0, ',', '.') }}@if(!empty($card['suffix'])){{ $card['suffix'] }}@endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-6">
                    <div class="card admin-chart-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Tendência</div>
                                <h3 class="card-title">Volume diário de visitas</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-chart-frame">
                                <canvas data-admin-chart='@json($visitsChart)'></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card admin-chart-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Distribuição</div>
                                <h3 class="card-title">Dispositivos</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-chart-frame admin-chart-frame-sm">
                                <canvas data-admin-chart='@json($devicesChart)'></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card admin-chart-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Comercial</div>
                                <h3 class="card-title">Leads por status</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-chart-frame admin-chart-frame-sm">
                                <canvas data-admin-chart='@json($leadsChart)'></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-6">
                    <div class="card admin-chart-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Tecnologia</div>
                                <h3 class="card-title">Navegadores mais usados</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-chart-frame">
                                <canvas data-admin-chart='@json($browsersChart)'></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3">
                    <div class="card admin-list-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Plataformas</div>
                                <h3 class="card-title">Ambientes</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-progress-list">
                                @forelse ($platforms as $platform)
                                    <div>
                                        <span>{{ $platform['label'] }}</span>
                                        <strong>{{ number_format($platform['total'], 0, ',', '.') }} visita(s)</strong>
                                    </div>
                                @empty
                                    <div class="admin-calendar-empty-state admin-calendar-empty-state-compact">
                                        <span>Sem plataformas mapeadas.</span>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3">
                    <div class="card admin-list-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Origem</div>
                                <h3 class="card-title">Referrers</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-progress-list">
                                @forelse ($topReferrers as $referrer)
                                    <div>
                                        <span>{{ $referrer['label'] }}</span>
                                        <strong>{{ number_format($referrer['total'], 0, ',', '.') }} acesso(s)</strong>
                                    </div>
                                @empty
                                    <div class="admin-calendar-empty-state admin-calendar-empty-state-compact">
                                        <span>Sem origem externa relevante no período.</span>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card admin-table-card mb-4">
                <div class="card-header">
                    <div>
                        <div class="admin-card-kicker">Ranking</div>
                        <h3 class="card-title">Páginas mais acessadas</h3>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Rota</th>
                                <th>Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($visitsByPage as $row)
                                <tr>
                                    <td><code>{{ $row->path ?: '/' }}</code></td>
                                    <td class="fw-semibold">{{ number_format($row->total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-4">Sem dados.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-transparent">{{ $visitsByPage->links() }}</div>
            </div>

            <div class="card admin-table-card">
                <div class="card-header">
                    <div>
                        <div class="admin-card-kicker">Tempo real</div>
                        <h3 class="card-title">Últimas visitas</h3>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th>URL</th>
                                <th>Dispositivo</th>
                                <th>Navegador</th>
                                <th>Plataforma</th>
                                <th>Data</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($latestVisits as $visit)
                                <tr>
                                    <td><code>{{ $visit->path ?: '/' }}</code></td>
                                    <td>{{ $visit->device_type ?: 'Não identificado' }}</td>
                                    <td>{{ $visit->browser ?: 'Desconhecido' }}</td>
                                    <td>{{ $visit->platform ?: 'Desconhecida' }}</td>
                                    <td>{{ $visit->visited_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Sem dados.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
