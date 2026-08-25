@extends('admin.layouts.app')

@php
    $pageTitle = 'Intimações e publicações do DJEN';
    $statusLabels = ['pending' => 'Pendente', 'approved' => 'Aprovada', 'rejected' => 'Rejeitada'];
    $statusClasses = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'secondary'];
@endphp

@section('content')
<div class="app-content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="mb-1">Intimações e publicações do DJEN</h1>
            <p class="text-muted mb-0">Monitoramento oficial do CNJ com revisão humana obrigatória antes da publicação no portal.</p>
        </div>
        <span class="badge text-bg-info px-3 py-2"><i class="bi bi-shield-check me-1"></i>Conteúdo bruto restrito ao administrativo</span>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <div class="row g-3 mb-4">
            @foreach([
                ['label' => 'Total importado', 'value' => $summary['total'], 'icon' => 'bi-journal-text', 'class' => 'primary'],
                ['label' => 'Aguardando revisão', 'value' => $summary['pending'], 'icon' => 'bi-hourglass-split', 'class' => 'warning'],
                ['label' => 'Aprovadas', 'value' => $summary['approved'], 'icon' => 'bi-check2-circle', 'class' => 'success'],
                ['label' => 'Rejeitadas', 'value' => $summary['rejected'], 'icon' => 'bi-slash-circle', 'class' => 'secondary'],
            ] as $card)
                <div class="col-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm"><div class="card-body d-flex align-items-center gap-3">
                        <span class="btn btn-{{ $card['class'] }} disabled"><i class="bi {{ $card['icon'] }}"></i></span>
                        <div><div class="fs-3 fw-semibold">{{ $card['value'] }}</div><div class="text-muted small">{{ $card['label'] }}</div></div>
                    </div></div>
                </div>
            @endforeach
        </div>

        <div class="card mb-4">
            <div class="card-header"><strong><i class="bi bi-funnel me-2"></i>Filtros</strong></div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.djen-publications.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-3"><label class="form-label" for="djen-search">Busca</label><input id="djen-search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Teor, órgão ou comunicação"></div>
                    <div class="col-md-2"><label class="form-label" for="djen-status">Revisão</label><select id="djen-status" name="status" class="form-select"><option value="">Todos</option>@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label" for="djen-tribunal">Tribunal</label><select id="djen-tribunal" name="tribunal" class="form-select"><option value="">Todos</option>@foreach($tribunals as $tribunal)<option value="{{ $tribunal }}" @selected(request('tribunal') === $tribunal)>{{ $tribunal }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label" for="djen-process">Processo</label><input id="djen-process" name="process_number" class="form-control" value="{{ request('process_number') }}" placeholder="0000000-00.0000.0.00.0000"></div>
                    <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-fill" type="submit">Filtrar</button><a class="btn btn-outline-secondary" href="{{ route('admin.djen-publications.index') }}" aria-label="Limpar filtros"><i class="bi bi-x-lg"></i></a></div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center"><strong>Central de revisão</strong><span class="text-muted small">Nada é liberado automaticamente ao cliente.</span></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th class="ps-3">Disponibilização</th><th>Processo</th><th>Publicação</th><th>Tribunal</th><th>Revisão</th><th class="text-end pe-3">Ação</th></tr></thead>
                    <tbody>
                    @forelse($publications as $publication)
                        <tr>
                            <td class="ps-3 text-nowrap">{{ $publication->availability_date?->format('d/m/Y') ?: 'Não informada' }}</td>
                            <td><strong>{{ $publication->legalCase?->title ?: 'Não vinculado' }}</strong><small class="d-block text-muted">{{ $publication->process_number_normalized ?: 'Sem número CNJ' }}</small></td>
                            <td><strong>{{ $publication->communication_type ?: 'Comunicação processual' }}</strong><span class="d-block text-muted small">{{ IlluminateSupportStr::limit($publication->raw_text ?: 'Sem teor textual.', 120) }}</span></td>
                            <td>{{ $publication->tribunal ?: '—' }}<small class="d-block text-muted">{{ $publication->court_body ?: 'Órgão não informado' }}</small></td>
                            <td><span class="badge text-bg-{{ $statusClasses[$publication->review_status] ?? 'secondary' }}">{{ $statusLabels[$publication->review_status] ?? $publication->review_status }}</span>@if($publication->reviewer)<small class="d-block text-muted mt-1">{{ $publication->reviewer->name }}</small>@endif</td>
                            <td class="text-end pe-3"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.djen-publications.show', $publication) }}"><i class="bi bi-eye me-1"></i>Revisar</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">Nenhuma publicação encontrada.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($publications->hasPages())<div class="card-footer">{{ $publications->links() }}</div>@endif
        </div>

        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header"><strong><i class="bi bi-radar me-2"></i>Monitores configurados</strong></div>
                    <div class="card-body table-responsive p-0">
                        <table class="table align-middle mb-0"><thead><tr><th class="ps-3">Monitor</th><th>Consulta</th><th>Última execução</th><th>Status</th>@if($canManageMonitors)<th class="text-end pe-3">Ação</th>@endif</tr></thead><tbody>
                        @forelse($monitors as $monitor)
                            @php($lastRun = $monitor->syncRuns->first())
                            <tr><td class="ps-3"><strong>{{ $monitor->label }}</strong><small class="d-block text-muted">A cada {{ $monitor->sync_interval_minutes }} min</small></td><td>@if($monitor->type === 'process')Processo: {{ $monitor->process_number_normalized }}@else OAB {{ $monitor->oab_number_normalized }}/{{ $monitor->oab_state }}@endif</td><td>{{ $lastRun?->created_at?->format('d/m/Y H:i') ?: 'Nunca' }}<small class="d-block text-muted">{{ $lastRun?->status ?: 'Sem execução' }}</small></td><td><span class="badge text-bg-{{ $monitor->enabled ? 'success' : 'secondary' }}">{{ $monitor->enabled ? 'Ativo' : 'Inativo' }}</span>@if($monitor->rate_limited_until?->isFuture())<small class="d-block text-danger">Pausa até {{ $monitor->rate_limited_until->format('d/m H:i') }}</small>@endif</td>@if($canManageMonitors)<td class="text-end pe-3"><form method="POST" action="{{ route('admin.djen-monitors.sync', $monitor) }}">@csrf<button class="btn btn-sm btn-outline-primary" type="submit" @disabled(!$monitor->enabled)><i class="bi bi-arrow-repeat"></i></button></form></td>@endif</tr>
                        @empty<tr><td colspan="5" class="text-center text-muted py-4">Nenhum monitor configurado.</td></tr>@endforelse
                        </tbody></table>
                    </div>
                </div>
            </div>

            @if($canManageMonitors)
            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-header"><strong><i class="bi bi-plus-circle me-2"></i>Novo monitor</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.djen-monitors.store') }}" class="row g-3">@csrf
                            <div class="col-md-6"><label class="form-label">Tipo</label><select name="type" class="form-select" required><option value="process">Número do processo</option><option value="oab">OAB e UF</option></select></div>
                            <div class="col-md-6"><label class="form-label">Nome do monitor</label><input name="label" class="form-control" maxlength="255" required></div>
                            <div class="col-12"><label class="form-label">Processo</label><select name="legal_case_id" class="form-select"><option value="">Selecione quando o tipo for processo</option>@foreach($legalCases as $case)<option value="{{ $case->id }}">{{ $case->title }} · {{ $case->process_number }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Número da OAB</label><input name="oab_number" class="form-control" maxlength="30" autocomplete="off"></div>
                            <div class="col-md-3"><label class="form-label">UF</label><input name="oab_state" class="form-control text-uppercase" maxlength="2" pattern="[A-Za-z]{2}" autocomplete="off"></div>
                            <div class="col-md-3"><label class="form-label">Intervalo</label><select name="sync_interval_minutes" class="form-select"><option value="30">30 min</option><option value="60" selected>1 hora</option><option value="180">3 horas</option><option value="360">6 horas</option></select></div>
                            <div class="col-md-4"><label class="form-label">Busca inicial</label><select name="lookback_days" class="form-select"><option value="7">7 dias</option><option value="30" selected>30 dias</option><option value="90">90 dias</option></select></div>
                            <div class="col-md-4"><label class="form-label">Sobreposição</label><select name="overlap_days" class="form-select"><option value="1">1 dia</option><option value="2" selected>2 dias</option><option value="3">3 dias</option></select></div>
                            <div class="col-md-4"><label class="form-label">Início opcional</label><input type="date" name="starts_at" class="form-control"></div>
                            <div class="col-12"><div class="alert alert-info small mb-0"><i class="bi bi-info-circle me-1"></i>A consulta por OAB usa os parâmetros oficiais <strong>numeroOab</strong> e <strong>ufOab</strong>. O resultado sempre passa pela revisão.</div></div>
                            <div class="col-12"><button class="btn btn-primary w-100" type="submit">Criar monitor</button></div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
