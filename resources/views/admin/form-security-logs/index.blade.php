@extends('admin.layouts.app')

@section('title', 'Auditoria de formularios')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h1 class="m-0">Auditoria de formulários</h1>
                    <p class="text-muted mb-0">Registro avançado de envios e bloqueios de segurança.</p>
                </div>
            </div>
        </div>
    </div>

    <section class="content pb-4">
        <div class="container-fluid">
            <div class="card admin-card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Rota</label>
                            <input type="text" class="form-control" name="route" value="{{ request('route') }}" placeholder="Ex: site.contact.submit">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">IP</label>
                            <input type="text" class="form-control" name="ip" value="{{ request('ip') }}" placeholder="Ex: 200.10.10.10">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="blocked">
                                <option value="">Todos</option>
                                <option value="1" @selected(request('blocked') === '1')>Bloqueado</option>
                                <option value="0" @selected(request('blocked') === '0')>Permitido</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card admin-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Rota</th>
                                <th>IP</th>
                                <th>Origem</th>
                                <th>Localização (IP)</th>
                                <th>Provedor</th>
                                <th>Status</th>
                                <th>Ameaças</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->submitted_at?->format('d/m/Y H:i:s') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $log->route_name ?: '-' }}</div>
                                        <small class="text-muted">{{ $log->method }} {{ $log->path }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $log->ip_address ?: '-' }}</div>
                                        <small class="text-muted">{{ $log->reverse_dns ?: '-' }}</small>
                                    </td>
                                    <td>
                                        <div class="small text-muted">{{ $log->origin ?: '-' }}</div>
                                        <div class="small text-muted">{{ $log->referer ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ collect([$log->city, $log->region, $log->country])->filter()->implode(' / ') ?: '-' }}</div>
                                        <small class="text-muted">{{ $log->latitude && $log->longitude ? $log->latitude.', '.$log->longitude : '-' }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $log->isp ?: '-' }}</div>
                                        <small class="text-muted">{{ $log->organization ?: '-' }}</small>
                                    </td>
                                    <td>
                                        @if($log->blocked)
                                            <span class="badge bg-danger">Bloqueado</span>
                                            <div class="small text-muted">{{ $log->block_reason }}</div>
                                        @else
                                            <span class="badge bg-success">Permitido</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(is_array($log->threats) && count($log->threats))
                                            <ul class="small mb-0 ps-3">
                                                @foreach($log->threats as $threat)
                                                    <li>{{ $threat }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Nenhum registro encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($logs->hasPages())
                    <div class="card-footer">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection

