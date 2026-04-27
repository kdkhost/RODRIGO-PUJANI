@extends('admin.layouts.app')

@section('content')
    @php
        $statMeta = [
            'pages' => ['label' => 'Páginas', 'icon' => 'bi-window-stack', 'tone' => 'gold'],
            'areas' => ['label' => 'Áreas', 'icon' => 'bi-briefcase', 'tone' => 'blue'],
            'team' => ['label' => 'Equipe', 'icon' => 'bi-people', 'tone' => 'green'],
            'testimonials' => ['label' => 'Depoimentos', 'icon' => 'bi-chat-square-quote', 'tone' => 'purple'],
            'contacts' => ['label' => 'Mensagens', 'icon' => 'bi-envelope', 'tone' => 'red'],
            'visits' => ['label' => 'Visitas', 'icon' => 'bi-graph-up-arrow', 'tone' => 'cyan'],
        ];
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
    @endphp

    <div class="app-content-header admin-page-hero admin-dashboard-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Centro de comando</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Acompanhe o conteúdo, os contatos e os principais sinais de atividade do site.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-calendar2-check"></i>
                    <span>{{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-3 mb-4">
                @foreach ($stats as $key => $value)
                    @php
                        $meta = $statMeta[$key] ?? ['label' => ucfirst($key), 'icon' => 'bi-circle', 'tone' => 'gold'];
                    @endphp
                    <div class="col-md-4 col-xl-2">
                        <div class="card admin-stat-card admin-stat-{{ $meta['tone'] }} h-100">
                            <div class="card-body">
                                <div class="admin-stat-icon"><i class="bi {{ $meta['icon'] }}"></i></div>
                                <div class="admin-stat-label">{{ $meta['label'] }}</div>
                                <div class="admin-stat-value">{{ number_format($value, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card admin-chart-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Audiência</div>
                                <h3 class="card-title">Visitas dos últimos 7 dias</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-chart-frame">
                                <canvas id="visits-chart" data-admin-chart='@json($visitsChart)'></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
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
                                        <th>Data</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse ($latestContacts as $contact)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $contact->name }}</div>
                                                <div class="small text-muted">{{ $contact->email ?: $contact->phone }}</div>
                                            </td>
                                            <td><span class="badge badge-soft-info">{{ $contact->status }}</span></td>
                                            <td>{{ $contact->created_at?->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">Nenhuma mensagem registrada.</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
