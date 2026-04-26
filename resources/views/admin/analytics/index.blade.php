@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Inteligencia do site</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Monitore acessos, dispositivos, origem de interesse e comportamento recente dos visitantes.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-activity"></i>
                    <span>{{ number_format($latestVisits->count(), 0, ',', '.') }} visitas recentes</span>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card admin-chart-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Distribuicao</div>
                                <h3 class="card-title">Dispositivos</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="devices-chart" height="140"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card admin-chart-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Comercial</div>
                                <h3 class="card-title">Leads por status</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="leads-chart" height="140"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card admin-table-card mb-4">
                <div class="card-header">
                    <div>
                        <div class="admin-card-kicker">Ranking</div>
                        <h3 class="card-title">Paginas mais acessadas</h3>
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
                                    <td><code>{{ $row->path }}</code></td>
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
                        <h3 class="card-title">Ultimas visitas</h3>
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
                                <th>Data</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($latestVisits as $visit)
                                <tr>
                                    <td><code>{{ $visit->path }}</code></td>
                                    <td>{{ $visit->device_type }}</td>
                                    <td>{{ $visit->browser }}</td>
                                    <td>{{ $visit->visited_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Sem dados.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.Chart) return;

            const devicesChart = document.getElementById('devices-chart');
            if (devicesChart) {
                new window.Chart(devicesChart, {
                    type: 'doughnut',
                    data: {
                        labels: @json($devices->pluck('device_type')),
                        datasets: [{
                            data: @json($devices->pluck('total')),
                            backgroundColor: ['#C49A3C', '#3b82f6', '#198754', '#dc3545'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        plugins: {
                            legend: { position: 'bottom' },
                        },
                    },
                });
            }

            const leadsChart = document.getElementById('leads-chart');
            if (leadsChart) {
                new window.Chart(leadsChart, {
                    type: 'bar',
                    data: {
                        labels: @json($leadStats->pluck('status')),
                        datasets: [{
                            data: @json($leadStats->pluck('total')),
                            backgroundColor: '#C49A3C',
                            borderRadius: 8,
                        }],
                    },
                    options: {
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                        },
                    },
                });
            }
        });
    </script>
@endsection
