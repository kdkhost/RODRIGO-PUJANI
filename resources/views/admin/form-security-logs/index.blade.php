@extends('admin.layouts.app')

@section('title', 'Auditoria de formularios')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h1 class="m-0">Auditoria de formularios</h1>
                    <p class="text-muted mb-0">Registro avancado com dados de origem, dispositivo e bloqueio manual individual.</p>
                </div>
            </div>
        </div>
    </div>

    <section class="content pb-4">
        <div class="container-fluid">
            <div class="card admin-card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Rota</label>
                            <input type="text" class="form-control" name="route" value="{{ request('route') }}" placeholder="Ex: site.contact.submit">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">IP</label>
                            <input type="text" class="form-control" name="ip" value="{{ request('ip') }}" placeholder="Ex: 200.10.10.10">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">ID dispositivo</label>
                            <input type="text" class="form-control" name="device_id" value="{{ request('device_id') }}" placeholder="Ex: dev_abc123">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Fingerprint</label>
                            <input type="text" class="form-control" name="fingerprint" value="{{ request('fingerprint') }}" placeholder="Hash">
                        </div>
                        <div class="col-md-1">
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
                                <th>Origem</th>
                                <th>Dispositivo</th>
                                <th>Rede/IP</th>
                                <th>Status</th>
                                <th>Bloqueio manual</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                @php
                                    $location = collect([$log->city, $log->region, $log->country])->filter()->implode(' / ');
                                    $browser = trim(collect([$log->browser_name, $log->browser_version])->filter()->implode(' '));
                                    $os = trim(collect([$log->os_name, $log->os_version])->filter()->implode(' '));
                                @endphp
                                <tr>
                                    <td>
                                        <div>{{ $log->submitted_at?->format('d/m/Y H:i:s') }}</div>
                                        <small class="text-muted">{{ $log->method }} {{ $log->path }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $log->route_name ?: '-' }}</div>
                                        <small class="text-muted">{{ $log->host ?: '-' }}</small>
                                    </td>
                                    <td>
                                        <div class="small"><strong>Referer:</strong> {{ $log->referer ?: '-' }}</div>
                                        <div class="small"><strong>Origin:</strong> {{ $log->origin ?: '-' }}</div>
                                        <div class="small"><strong>UA:</strong> {{ \Illuminate\Support\Str::limit($log->user_agent ?: '-', 120) }}</div>
                                    </td>
                                    <td>
                                        <div class="small"><strong>ID:</strong> {{ $log->device_id ?: '-' }}</div>
                                        <div class="small"><strong>Tipo:</strong> {{ $log->device_type ?: '-' }}</div>
                                        <div class="small"><strong>Plataforma:</strong> {{ $log->device_platform ?: '-' }}</div>
                                        <div class="small"><strong>Modelo:</strong> {{ $log->device_model ?: '-' }}</div>
                                        <div class="small"><strong>Navegador:</strong> {{ $browser !== '' ? $browser : '-' }}</div>
                                        <div class="small"><strong>Sistema:</strong> {{ $os !== '' ? $os : '-' }}</div>
                                        <div class="small"><strong>Fingerprint:</strong> {{ \Illuminate\Support\Str::limit($log->device_fingerprint ?: '-', 24) }}</div>
                                        <div class="small"><strong>MAC:</strong> {{ $log->mac_address ?: 'Nao disponivel via navegador' }}</div>
                                    </td>
                                    <td>
                                        <div><strong>IP:</strong> {{ $log->ip_address ?: '-' }}</div>
                                        <div class="small text-muted">{{ $log->reverse_dns ?: '-' }}</div>
                                        <div class="small"><strong>Forwarded:</strong> {{ \Illuminate\Support\Str::limit($log->forwarded_for ?: '-', 60) }}</div>
                                        <div class="small"><strong>Geo:</strong> {{ $location !== '' ? $location : '-' }}</div>
                                        <div class="small"><strong>Coords:</strong> {{ $log->latitude && $log->longitude ? $log->latitude.', '.$log->longitude : '-' }}</div>
                                        <div class="small"><strong>ISP:</strong> {{ $log->isp ?: '-' }}</div>
                                        <div class="small"><strong>Org:</strong> {{ $log->organization ?: '-' }}</div>
                                        <div class="small"><strong>ASN:</strong> {{ $log->asn ?: '-' }}</div>
                                    </td>
                                    <td>
                                        @if($log->blocked)
                                            <span class="badge bg-danger">Bloqueado</span>
                                            <div class="small text-muted">{{ $log->block_reason ?: '-' }}</div>
                                        @else
                                            <span class="badge bg-success">Permitido</span>
                                        @endif
                                        @if(is_array($log->threats) && count($log->threats))
                                            <ul class="small mb-0 ps-3 mt-2">
                                                @foreach($log->threats as $threat)
                                                    <li>{{ $threat }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-grid gap-1 mb-2">
                                            @if($log->ip_address)
                                                <form method="POST" action="{{ route('admin.form-security-logs.block') }}">
                                                    @csrf
                                                    <input type="hidden" name="type" value="ip">
                                                    <input type="hidden" name="value" value="{{ $log->ip_address }}">
                                                    <input type="hidden" name="reason" value="Bloqueio manual por IP">
                                                    <button class="btn btn-sm btn-outline-danger w-100" type="submit">Bloquear IP</button>
                                                </form>
                                            @endif
                                            @if($log->device_id)
                                                <form method="POST" action="{{ route('admin.form-security-logs.block') }}">
                                                    @csrf
                                                    <input type="hidden" name="type" value="device_id">
                                                    <input type="hidden" name="value" value="{{ $log->device_id }}">
                                                    <input type="hidden" name="reason" value="Bloqueio manual por ID de dispositivo">
                                                    <button class="btn btn-sm btn-outline-danger w-100" type="submit">Bloquear dispositivo</button>
                                                </form>
                                            @endif
                                            @if($log->device_fingerprint)
                                                <form method="POST" action="{{ route('admin.form-security-logs.block') }}">
                                                    @csrf
                                                    <input type="hidden" name="type" value="device_fingerprint">
                                                    <input type="hidden" name="value" value="{{ $log->device_fingerprint }}">
                                                    <input type="hidden" name="reason" value="Bloqueio manual por fingerprint">
                                                    <button class="btn btn-sm btn-outline-danger w-100" type="submit">Bloquear fingerprint</button>
                                                </form>
                                            @endif
                                            @if($log->mac_address)
                                                <form method="POST" action="{{ route('admin.form-security-logs.block') }}">
                                                    @csrf
                                                    <input type="hidden" name="type" value="mac_address">
                                                    <input type="hidden" name="value" value="{{ $log->mac_address }}">
                                                    <input type="hidden" name="reason" value="Bloqueio manual por MAC">
                                                    <button class="btn btn-sm btn-outline-danger w-100" type="submit">Bloquear MAC</button>
                                                </form>
                                            @endif
                                        </div>
                                        @if($log->block)
                                            <div class="small text-muted mb-1">Regra ativa: {{ $log->block->type }} / {{ \Illuminate\Support\Str::limit($log->block->value, 24) }}</div>
                                            <form method="POST" action="{{ route('admin.form-security-logs.unblock', $log->block) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-success w-100" type="submit">Desbloquear regra</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Nenhum registro encontrado.</td>
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

