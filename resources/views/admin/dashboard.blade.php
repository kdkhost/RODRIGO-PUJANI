@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="mb-0">{{ $pageTitle }}</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-3 mb-4">
                @foreach ($stats as $label => $value)
                    <div class="col-md-4 col-xl-2">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="text-uppercase text-muted small mb-2">{{ ucfirst($label) }}</div>
                                <div class="display-6 fw-semibold">{{ number_format($value, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Visitas dos últimos 7 dias</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="visits-chart" height="120"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Últimas mensagens</h3>
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
                                            <td>{{ $contact->name }}</td>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('visits-chart');
            if (!el || !window.Chart) return;

            new window.Chart(el, {
                type: 'line',
                data: {
                    labels: @json($visitsByDay->pluck('day')),
                    datasets: [{
                        label: 'Visitas',
                        data: @json($visitsByDay->pluck('total')),
                        borderColor: '#C49A3C',
                        backgroundColor: 'rgba(196,154,60,0.18)',
                        fill: true,
                        tension: 0.35,
                    }],
                },
            });
        });
    </script>
@endsection
