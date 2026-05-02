@extends('admin.layouts.app')

@section('title', 'Auditoria de formulários')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Segurança operacional</div>
                    <h1>Auditoria de formulários</h1>
                    <p>Rastreie tentativas de contato, login, redefinição de senha e demais envios com bloqueio manual por origem.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-shield-exclamation"></i>
                    <span>Root only</span>
                </div>
            </div>
        </div>
    </div>

    <section class="app-content pb-4">
        <div class="container-fluid">
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="card admin-table-card">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Camada de seguranca ativa</div>
                                <h3 class="card-title">Monitoramento e bloqueio em tempo real</h3>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-4 col-xl-2">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="small text-uppercase text-muted mb-1">Registros</div>
                                        <div class="fs-4 fw-bold">{{ number_format($securitySummary['total_logs'], 0, ',', '.') }}</div>
                                        <div class="small text-muted">Eventos auditados</div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xl-2">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="small text-uppercase text-muted mb-1">Bloqueados</div>
                                        <div class="fs-4 fw-bold text-danger">{{ number_format($securitySummary['blocked_logs'], 0, ',', '.') }}</div>
                                        <div class="small text-muted">Tentativas contidas</div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xl-2">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="small text-uppercase text-muted mb-1">IPs unicos</div>
                                        <div class="fs-4 fw-bold">{{ number_format($securitySummary['distinct_ips'], 0, ',', '.') }}</div>
                                        <div class="small text-muted">Origens distintas</div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xl-2">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="small text-uppercase text-muted mb-1">Dispositivos</div>
                                        <div class="fs-4 fw-bold">{{ number_format($securitySummary['distinct_devices'], 0, ',', '.') }}</div>
                                        <div class="small text-muted">IDs persistentes</div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xl-2">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="small text-uppercase text-muted mb-1">MAC informado</div>
                                        <div class="fs-4 fw-bold">{{ number_format($securitySummary['mac_informed'], 0, ',', '.') }}</div>
                                        <div class="small text-muted">Somente app/cliente</div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xl-2">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="small text-uppercase text-muted mb-1">Regras ativas</div>
                                        <div class="fs-4 fw-bold text-warning">{{ number_format($securitySummary['active_blocks'], 0, ',', '.') }}</div>
                                        <div class="small text-muted">Bloqueios vigentes</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-xl-7">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="small text-uppercase fw-semibold text-muted mb-2">O que esta sendo coletado ocultamente</div>
                                        <div class="row g-2 small">
                                            <div class="col-md-6">- IP, forwarded-for e DNS reverso</div>
                                            <div class="col-md-6">- ID persistente do dispositivo</div>
                                            <div class="col-md-6">- Fingerprint tecnico do navegador</div>
                                            <div class="col-md-6">- Navegador, sistema e plataforma</div>
                                            <div class="col-md-6">- Tamanho de tela, timezone e idioma</div>
                                            <div class="col-md-6">- Rede, ASN, ISP e geolocalizacao por IP</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-5">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="small text-uppercase fw-semibold text-muted mb-2">Regras de bloqueio manual em vigor</div>
                                        @if($activeBlocks->isEmpty())
                                            <div class="small text-muted">Nenhuma regra ativa no momento.</div>
                                        @else
                                            <div class="d-grid gap-2">
                                                @foreach($activeBlocks as $block)
                                                    <div class="rounded-3 border p-2 admin-security-block-card">
                                                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                                            <div>
                                                                <strong>{{ strtoupper(str_replace('_', ' ', $block->type)) }}</strong>
                                                                <div class="small mt-1">{{ \Illuminate\Support\Str::limit($block->value, 80) }}</div>
                                                            </div>
                                                            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                                                                <span class="badge text-bg-warning">Ativo</span>
                                                                <form method="POST" action="{{ route('admin.form-security-logs.unblock', $block) }}" class="m-0">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button
                                                                        type="submit"
                                                                        class="btn btn-sm btn-outline-success"
                                                                        data-confirm-submit="true"
                                                                        data-confirm-title="Desbloquear regra?"
                                                                        data-confirm-text="Esta origem voltará a poder enviar formulários conforme as demais regras de segurança."
                                                                        data-confirm-button="Desbloquear"
                                                                    >
                                                                        Desbloquear
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                        <div class="small text-muted mt-1">
                                                            Hits: {{ number_format((int) $block->hits, 0, ',', '.') }}
                                                            @if($block->last_hit_at)
                                                                - último: {{ $block->last_hit_at->format('d/m/Y H:i') }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card admin-table-card mb-4">
                <div class="card-body p-4">
                    <form method="GET" class="row g-3 admin-premium-form">
                        <div class="col-md-3">
                            <label class="form-label">Rota</label>
                            <input type="text" class="form-control" name="route" value="{{ request('route') }}" placeholder="Ex: login ou site.contact.submit">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">IP</label>
                            <input type="text" class="form-control" name="ip" value="{{ request('ip') }}" placeholder="Ex: 200.10.10.10">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">ID do dispositivo</label>
                            <input type="text" class="form-control" name="device_id" value="{{ request('device_id') }}" placeholder="Ex: dev_xpto">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Fingerprint</label>
                            <input type="text" class="form-control" name="fingerprint" value="{{ request('fingerprint') }}" placeholder="Hash do navegador">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Endereço MAC</label>
                            <input type="text" class="form-control" name="mac_address" value="{{ request('mac_address') }}" placeholder="Quando enviado por app/cliente">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="blocked">
                                <option value="">Todos</option>
                                <option value="1" @selected(request('blocked') === '1')>Bloqueado</option>
                                <option value="0" @selected(request('blocked') === '0')>Permitido</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Regra ativa</label>
                            <select class="form-select" name="block_type">
                                <option value="">Todas</option>
                                <option value="ip" @selected(request('block_type') === 'ip')>IP</option>
                                <option value="device_id" @selected(request('block_type') === 'device_id')>Dispositivo</option>
                                <option value="device_fingerprint" @selected(request('block_type') === 'device_fingerprint')>Fingerprint</option>
                                <option value="mac_address" @selected(request('block_type') === 'mac_address')>MAC</option>
                                <option value="asn" @selected(request('block_type') === 'asn')>ASN</option>
                                <option value="user_agent" @selected(request('block_type') === 'user_agent')>User agent</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Por página</label>
                            <select class="form-select" name="per_page">
                                @foreach([10, 25, 50, 100] as $option)
                                    <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} registros</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-2">
                            <button class="btn btn-primary flex-grow-1" type="submit">
                                <i class="bi bi-funnel me-1"></i>Filtrar
                            </button>
                            <a href="{{ route('admin.form-security-logs.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Limpar
                            </a>
                        </div>
                    </form>

                    <div class="alert alert-warning mt-3 mb-0">
                        <strong>Nota tecnica:</strong> navegadores web nao expoem o endereco MAC real da maquina. O sistema registra MAC apenas quando um cliente/app o envia explicitamente; para web o bloqueio efetivo fica por IP, ID persistente do dispositivo, fingerprint, ASN e user agent.
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="small text-muted">
                    Exibindo
                    <strong>{{ $logs->firstItem() ?? 0 }}</strong>
                    até
                    <strong>{{ $logs->lastItem() ?? 0 }}</strong>
                    de
                    <strong>{{ $logs->total() }}</strong>
                    registros.
                </div>
                @if($logs->hasPages())
                    <div>
                        {{ $logs->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>

            <div class="card admin-table-card admin-datatable-card">
                <div class="card-header admin-datatable-card-header">
                    <div>
                        <div class="admin-card-kicker">DataTables AdminLTE</div>
                        <h3 class="card-title">Registros auditados</h3>
                    </div>
                    <div class="admin-datatable-limit">
                        <i class="bi bi-table"></i>
                        <span>{{ $perPage }} por página</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table
                        class="table table-hover align-middle mb-0 admin-datatable-table"
                        data-admin-datatable
                        data-page-length="{{ $perPage }}"
                        data-datatable-paging="false"
                        data-datatable-search="false"
                        data-datatable-dom="t"
                    >
                        <thead>
                            <tr>
                                <th style="min-width: 150px;">Data</th>
                                <th style="min-width: 180px;">Rota</th>
                                <th style="min-width: 220px;">Origem</th>
                                <th style="min-width: 270px;">Dispositivo</th>
                                <th style="min-width: 250px;">Rede/IP</th>
                                <th style="min-width: 170px;">Status</th>
                                <th class="no-sort text-end" style="min-width: 220px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                @php
                                    $location = collect([$log->city, $log->region, $log->country])->filter()->implode(' / ');
                                    $browser = trim(collect([$log->browser_name, $log->browser_version])->filter()->implode(' '));
                                    $os = trim(collect([$log->os_name, $log->os_version])->filter()->implode(' '));
                                    $frontDevice = is_array($log->device_metadata['front_device'] ?? null) ? $log->device_metadata['front_device'] : [];
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $log->submitted_at?->format('d/m/Y H:i:s') }}</div>
                                        <small class="text-muted d-block">{{ $log->method }} {{ $log->path }}</small>
                                        <small class="text-muted d-block">Sessão: {{ \Illuminate\Support\Str::limit($log->session_id ?: '-', 18) }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $log->route_name ?: '-' }}</div>
                                        <small class="text-muted d-block">{{ $log->host ?: '-' }}</small>
                                        @if(is_array($log->payload_preview) && count($log->payload_preview))
                                            <details class="mt-2 small">
                                                <summary>Campos enviados ({{ $log->payload_field_count }})</summary>
                                                <div class="mt-2 p-2 rounded border bg-body-tertiary">
                                                    @foreach($log->payload_preview as $field => $value)
                                                        <div><strong>{{ $field }}:</strong> {{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</div>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small"><strong>Referer:</strong> {{ $log->referer ?: '-' }}</div>
                                        <div class="small"><strong>Origin:</strong> {{ $log->origin ?: '-' }}</div>
                                        <div class="small"><strong>UA:</strong> {{ \Illuminate\Support\Str::limit($log->user_agent ?: '-', 110) }}</div>
                                    </td>
                                    <td>
                                        <div class="small"><strong>ID:</strong> {{ $log->device_id ?: '-' }}</div>
                                        <div class="small"><strong>Tipo:</strong> {{ $log->device_type ?: '-' }}</div>
                                        <div class="small"><strong>Plataforma:</strong> {{ $log->device_platform ?: '-' }}</div>
                                        <div class="small"><strong>Modelo:</strong> {{ $log->device_model ?: '-' }}</div>
                                        <div class="small"><strong>Navegador:</strong> {{ $browser !== '' ? $browser : '-' }}</div>
                                        <div class="small"><strong>Sistema:</strong> {{ $os !== '' ? $os : '-' }}</div>
                                        <div class="small"><strong>Fingerprint:</strong> {{ \Illuminate\Support\Str::limit($log->device_fingerprint ?: '-', 40) }}</div>
                                        <div class="small"><strong>MAC:</strong> {{ $log->mac_address ?: 'Não disponível via navegador web' }}</div>
                                        <details class="mt-2 small">
                                            <summary>Metadados do aparelho</summary>
                                            <div class="mt-2 p-2 rounded border bg-body-tertiary">
                                                <div><strong>Tela:</strong> {{ $frontDevice['screen'] ?? '-' }}</div>
                                                <div><strong>Timezone:</strong> {{ $frontDevice['timezone'] ?? '-' }}</div>
                                                <div><strong>Idioma:</strong> {{ $frontDevice['language'] ?? '-' }}</div>
                                                <div><strong>Vendor:</strong> {{ $frontDevice['vendor'] ?? '-' }}</div>
                                                <div><strong>Touch points:</strong> {{ $frontDevice['touch_points'] ?? '-' }}</div>
                                                <div><strong>Rede:</strong> {{ $log->network_type ?: '-' }}</div>
                                            </div>
                                        </details>
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
                                            <span class="badge text-bg-danger">Bloqueado</span>
                                            <div class="small text-muted mt-1">{{ $log->block_reason ?: '-' }}</div>
                                        @else
                                            <span class="badge text-bg-success">Permitido</span>
                                        @endif

                                        @if(is_array($log->threats) && count($log->threats))
                                            <ul class="small mb-0 ps-3 mt-2">
                                                @foreach($log->threats as $threat)
                                                    <li>{{ $threat }}</li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        @if($log->block)
                                            <div class="small text-muted mt-2">
                                                Regra ativa: {{ $log->block->type }} / {{ \Illuminate\Support\Str::limit($log->block->value, 28) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-grid gap-2">
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

                                            @if($log->asn)
                                                <form method="POST" action="{{ route('admin.form-security-logs.block') }}">
                                                    @csrf
                                                    <input type="hidden" name="type" value="asn">
                                                    <input type="hidden" name="value" value="{{ $log->asn }}">
                                                    <input type="hidden" name="reason" value="Bloqueio manual por ASN">
                                                    <button class="btn btn-sm btn-outline-danger w-100" type="submit">Bloquear ASN</button>
                                                </form>
                                            @endif

                                            @if($log->user_agent)
                                                <form method="POST" action="{{ route('admin.form-security-logs.block') }}">
                                                    @csrf
                                                    <input type="hidden" name="type" value="user_agent">
                                                    <input type="hidden" name="value" value="{{ \Illuminate\Support\Str::limit($log->user_agent, 255, '') }}">
                                                    <input type="hidden" name="reason" value="Bloqueio manual por user agent">
                                                    <button class="btn btn-sm btn-outline-danger w-100" type="submit">Bloquear user agent</button>
                                                </form>
                                            @endif

                                            @if($log->block)
                                                <form method="POST" action="{{ route('admin.form-security-logs.unblock', $log->block) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-sm btn-outline-success w-100" type="submit">Desbloquear regra</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($logs->isEmpty())
                    <div class="admin-datatable-empty">
                        Nenhum registro encontrado para os filtros aplicados.
                    </div>
                @endif

                @if($logs->hasPages())
                    <div class="card-footer d-flex justify-content-end">
                        {{ $logs->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
