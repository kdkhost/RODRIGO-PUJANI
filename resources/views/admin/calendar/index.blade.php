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

@push('styles')
    {{-- FullCalendar 6 Styles --}}
@endpush

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

            <form class="admin-table-toolbar mb-3" id="admin-calendar-filters">
                <div class="admin-search-box">
                    <i class="bi bi-search"></i>
                    <input type="search" name="search" class="form-control" placeholder="Pesquisar eventos" data-table-search data-table-target="#admin-calendar-events-table">
                </div>
                <div class="admin-search-box">
                    <i class="bi bi-funnel"></i>
                    <input type="text" name="category" class="form-control" list="calendar-categories" placeholder="Filtrar por categoria" data-table-filter data-table-target="#admin-calendar-events-table">
                    <datalist id="calendar-categories">
                        @foreach ($categories as $category)
                            <option value="{{ $category }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <select name="status" class="form-select" data-table-filter data-table-target="#admin-calendar-events-table">
                    <option value="">Todos os status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</option>
                    @endforeach
                </select>
                <select name="visibility" class="form-select" data-table-filter data-table-target="#admin-calendar-events-table">
                    <option value="">Todas as visibilidades</option>
                    @foreach ($visibilities as $visibility)
                        <option value="{{ $visibility }}">{{ $visibilityLabels[$visibility] ?? ucfirst($visibility) }}</option>
                    @endforeach
                </select>
                <select name="owner_id" class="form-select" data-table-filter data-table-target="#admin-calendar-events-table">
                    <option value="">Todos os responsáveis</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" class="form-control" data-table-filter data-table-target="#admin-calendar-events-table" aria-label="Data inicial">
                <input type="date" name="date_to" class="form-control" data-table-filter data-table-target="#admin-calendar-events-table" aria-label="Data final">
                <button class="btn btn-outline-secondary" type="reset">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Limpar
                </button>
            </form>

            <div class="admin-calendar-layout">
                <div class="card admin-calendar-card">
                    <div class="card-header border-0 pb-0">
                        <div>
                            <div class="admin-card-kicker">Visão Dinâmica v6</div>
                            <h3 class="card-title">Agenda operacional</h3>
                        </div>
                        <div class="admin-calendar-legend d-none d-md-flex">
                            <span><i style="background:#c49a3c"></i>Agendado</span>
                            <span><i style="background:#198754"></i>Confirmado</span>
                            <span><i style="background:#3b82f6"></i>Concluído</span>
                            <span><i style="background:#dc3545"></i>Cancelado</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="admin-calendar-shell">
                            <div id="admin-calendar-v6"></div>
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
                        data-toolbar="#admin-calendar-filters"
                        data-url="{{ route('admin.calendar.records') }}"
                    >
                        <div class="py-4 text-center text-muted">Carregando eventos...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- FullCalendar 6 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('admin-calendar-v6');
            const filterForm = document.getElementById('admin-calendar-filters');
            
            if (!calendarEl) return;

            const readFilters = () => {
                const formData = new FormData(filterForm);
                const params = {};
                formData.forEach((value, key) => {
                    if (value) params[key] = value;
                });
                return params;
            };

            const calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'pt-br',
                timeZone: 'local',
                initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth',
                height: window.innerWidth < 768 ? 'auto' : 650,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    week: 'Semana',
                    day: 'Dia',
                    list: 'Lista'
                },
                editable: true,
                selectable: true,
                nowIndicator: true,
                dayMaxEvents: true,
                navLinks: true,
                businessHours: true,
                eventSources: [{
                    url: "{{ route('admin.calendar.events') }}",
                    extraParams: readFilters,
                    failure: function() {
                        if (window.toastr) window.toastr.error('Erro ao carregar agenda');
                    }
                }],
                loading: function(isLoading) {
                    calendarEl.classList.toggle('is-loading', isLoading);
                },
                select: function(info) {
                    const url = new URL("{{ route('admin.calendar.create') }}", window.location.origin);
                    url.searchParams.set('start', info.startStr);
                    url.searchParams.set('end', info.endStr);
                    url.searchParams.set('all_day', info.allDay ? '1' : '0');
                    
                    if (window.AdminUI) {
                        window.AdminUI.loadModal(url.toString(), 'Novo evento');
                    }
                    calendar.unselect();
                },
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if (window.AdminUI) {
                        window.AdminUI.showCalendarEventPanel(info.event, info.jsEvent);
                    }
                },
                eventContent: function(info) {
                    const props = info.event.extendedProps || {};
                    const timeText = info.timeText ? `<span class="admin-calendar-event-time">${info.timeText}</span>` : '';
                    const titleText = `<span class="admin-calendar-event-title">${info.event.title}</span>`;
                    
                    const html = `
                        <div class="admin-calendar-event-shell">
                            <div class="admin-calendar-event-heading">${timeText}${titleText}</div>
                        </div>
                    `;
                    
                    return { html: html };
                },
                eventDidMount: function(info) {
                    const props = info.event.extendedProps || {};
                    if (props.status) {
                        info.el.setAttribute('data-status', props.status);
                    }
                }
            });

            calendar.render();

            // Refresh on filters
            filterForm.querySelectorAll('input, select').forEach(input => {
                input.addEventListener('change', () => calendar.refetchEvents());
                if (input.tagName === 'INPUT' && input.type === 'search') {
                    input.addEventListener('input', () => {
                        clearTimeout(input._timer);
                        input._timer = setTimeout(() => calendar.refetchEvents(), 500);
                    });
                }
            });

            filterForm.addEventListener('reset', () => {
                setTimeout(() => calendar.refetchEvents(), 100);
            });

            // Global access
            window.adminCalendar = calendar;
        });
    </script>
@endpush
