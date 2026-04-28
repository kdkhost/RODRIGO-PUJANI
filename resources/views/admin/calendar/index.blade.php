@php
    $statusLabels = [
        'scheduled' => 'Agendado',
        'confirmed' => 'Confirmado',
        'done' => 'Concluído',
        'canceled' => 'Cancelado',
    ];
    $visibilityLabels = [
        'private' => 'Privado',
        'team' => 'Equipe',
        'public' => 'Público',
    ];
    $displayLabels = [
        'auto' => 'Evento normal',
        'background' => 'Marcação de fundo',
        'inverse-background' => 'Bloqueio invertido',
    ];
@endphp

@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero admin-calendar-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Operação completa</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Painel vivo da agenda com compromissos, bloqueios, responsáveis e visão imediata da carga operacional.</p>
                </div>
                <button class="btn btn-primary admin-action-button" type="button" data-modal-url="{{ route('admin.calendar.create') }}" data-modal-title="Novo evento">
                    <i class="bi bi-calendar-plus me-1"></i>Novo evento
                </button>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="admin-calendar-kpis mb-4">
                <div class="admin-calendar-kpi">
                    <span>Total na agenda</span>
                    <strong>{{ number_format($eventStats['total'], 0, ',', '.') }}</strong>
                </div>
                <div class="admin-calendar-kpi">
                    <span>Hoje</span>
                    <strong>{{ number_format($eventStats['today'], 0, ',', '.') }}</strong>
                </div>
                <div class="admin-calendar-kpi">
                    <span>Próximos 7 dias</span>
                    <strong>{{ number_format($eventStats['upcoming'], 0, ',', '.') }}</strong>
                </div>
                <div class="admin-calendar-kpi">
                    <span>Dia inteiro</span>
                    <strong>{{ number_format($eventStats['all_day'], 0, ',', '.') }}</strong>
                </div>
                <div class="admin-calendar-kpi">
                    <span>Bloqueios visuais</span>
                    <strong>{{ number_format($eventStats['background'], 0, ',', '.') }}</strong>
                </div>
            </div>

            <form class="admin-table-toolbar mb-3" data-calendar-toolbar="#admin-calendar">
                <div class="admin-search-box">
                    <i class="bi bi-search"></i>
                    <input type="search" name="search" class="form-control" placeholder="Pesquisar eventos" data-calendar-filter data-table-search data-table-target="#admin-calendar-events-table">
                </div>
                <div class="admin-search-box">
                    <i class="bi bi-funnel"></i>
                    <input type="text" name="category" class="form-control" list="calendar-categories" placeholder="Filtrar por categoria" data-calendar-filter data-table-filter data-table-target="#admin-calendar-events-table">
                    <datalist id="calendar-categories">
                        @foreach ($categories as $category)
                            <option value="{{ $category }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <select name="status" class="form-select" data-calendar-filter data-table-filter data-table-target="#admin-calendar-events-table">
                    <option value="">Todos os status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</option>
                    @endforeach
                </select>
                <select name="visibility" class="form-select" data-calendar-filter data-table-filter data-table-target="#admin-calendar-events-table">
                    <option value="">Todas as visibilidades</option>
                    @foreach ($visibilities as $visibility)
                        <option value="{{ $visibility }}">{{ $visibilityLabels[$visibility] ?? ucfirst($visibility) }}</option>
                    @endforeach
                </select>
                <select name="display" class="form-select" data-calendar-filter data-table-filter data-table-target="#admin-calendar-events-table">
                    <option value="">Todos os formatos</option>
                    @foreach ($displays as $display)
                        <option value="{{ $display }}">{{ $displayLabels[$display] ?? ucfirst($display) }}</option>
                    @endforeach
                </select>
                <select name="owner_id" class="form-select" data-calendar-filter data-table-filter data-table-target="#admin-calendar-events-table">
                    <option value="">Todos os responsáveis</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" class="form-control" data-calendar-filter data-table-filter data-table-target="#admin-calendar-events-table" aria-label="Data inicial">
                <input type="date" name="date_to" class="form-control" data-calendar-filter data-table-filter data-table-target="#admin-calendar-events-table" aria-label="Data final">
                <select name="per_page" class="form-select" data-table-filter data-table-target="#admin-calendar-events-table">
                    @foreach ([10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected($size === 10)>{{ $size }} por página</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-secondary" type="reset" data-calendar-reset>
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Limpar
                </button>
            </form>

            <div class="admin-calendar-layout">
                <div class="card admin-calendar-card">
                    <div class="card-header">
                        <div>
                            <div class="admin-card-kicker">FullCalendar 4</div>
                            <h3 class="card-title">Agenda operacional</h3>
                        </div>
                        <div class="admin-calendar-legend">
                            <span><i style="background:#c49a3c"></i>Agendado</span>
                            <span><i style="background:#198754"></i>Confirmado</span>
                            <span><i style="background:#3b82f6"></i>Concluído</span>
                            <span><i style="background:#dc3545"></i>Cancelado</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="admin-calendar-shell">
                            <div
                                id="admin-calendar"
                                data-calendar
                                data-calendar-height="650"
                                data-calendar-content-height="570"
                                data-calendar-aspect-ratio="1.7"
                                data-calendar-events-url="{{ route('admin.calendar.events') }}"
                                data-calendar-create-url="{{ route('admin.calendar.create') }}"
                                data-calendar-toolbar="[data-calendar-toolbar='#admin-calendar']"
                            ></div>
                        </div>
                    </div>
                </div>

                <div class="admin-calendar-insights">
                    <div class="admin-calendar-side-card">
                        <div class="admin-card-kicker">Próximos compromissos</div>
                        <div class="admin-mini-stack">
                            @forelse($upcomingEvents as $event)
                                <button
                                    type="button"
                                    class="admin-calendar-record-item admin-calendar-record-item-button"
                                    data-modal-url="{{ route('admin.calendar.edit', $event) }}"
                                    data-modal-title="{{ $event->title }}"
                                >
                                    <div>
                                        <strong>{{ $event->title }}</strong>
                                        <span>{{ $event->category }} • {{ $event->owner?->name ?: 'Sem responsável' }}</span>
                                    </div>
                                    <small>{{ $event->all_day ? $event->start_at?->format('d/m/Y') : $event->start_at?->format('d/m/Y H:i') }}</small>
                                </button>
                            @empty
                                <div class="admin-calendar-empty-state">
                                    <strong>Sem compromissos futuros.</strong>
                                    <span>A agenda exibirá aqui os próximos eventos liberados para o seu acesso.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="admin-calendar-side-card">
                        <div class="admin-card-kicker">Carga por responsável</div>
                        <div class="admin-progress-list">
                            @forelse($ownerLoad as $row)
                                <div>
                                    <span>{{ $row['name'] }}</span>
                                    <strong>{{ number_format($row['total'], 0, ',', '.') }} evento(s)</strong>
                                </div>
                            @empty
                                <div class="admin-calendar-empty-state admin-calendar-empty-state-compact">
                                    <span>Nenhum responsável alocado ainda.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="card admin-calendar-records-card mt-4">
                <div class="card-header">
                    <div>
                        <div class="admin-card-kicker">Gestão detalhada</div>
                        <h3 class="card-title">Eventos cadastrados</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div
                        id="admin-calendar-events-table"
                        data-ajax-table
                        data-toolbar="[data-calendar-toolbar='#admin-calendar']"
                        data-url="{{ route('admin.calendar.records') }}"
                    >
                        <div class="py-4 text-center text-muted">Carregando eventos...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
