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

            <form class="admin-table-toolbar mb-3" id="admin-calendar-filters" data-calendar-toolbar="#admin-calendar">
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
                <button class="btn btn-outline-secondary" type="reset" data-calendar-reset>
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Limpar
                </button>
            </form>

            <div class="admin-calendar-layout">
                <div class="card admin-calendar-card">
                    <div class="card-header border-0 pb-0">
                        <div>
                            <div class="admin-card-kicker">Visão Dinâmica</div>
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
                            <div
                                id="admin-calendar"
                                class="admin-calendar"
                                data-calendar
                                data-calendar-managed="inline"
                                data-calendar-version="6"
                                data-calendar-toolbar="#admin-calendar-filters"
                                data-events-url="{{ route('admin.calendar.events') }}"
                                data-create-url="{{ route('admin.calendar.create') }}"
                                data-records-target="#admin-calendar-events-table"
                                data-calendar-height="650"
                                data-calendar-content-height="590"
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
                        data-ajax-managed="inline"
                        data-toolbar="#admin-calendar-filters"
                        data-url="{{ route('admin.calendar.records') }}"
                    >
                        @include('admin.calendar._table', ['items' => $records])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const calendarElement = document.getElementById('admin-calendar');
            const recordsElement = document.getElementById('admin-calendar-events-table');
            const filtersForm = document.getElementById('admin-calendar-filters');

            if (!calendarElement || !recordsElement || !filtersForm) {
                return;
            }

            const adminUI = window.AdminUI || null;
            const compactQuery = window.matchMedia('(max-width: 767.98px)');
            let calendarInstance = null;
            let searchTimer = null;

            const request = async (url) => {
                if (window.axios) {
                    const response = await window.axios.get(url);
                    return response.data;
                }

                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Falha na requisicao.');
                }

                return response.json();
            };

            const readFilters = () => {
                const params = new URLSearchParams();

                new FormData(filtersForm).forEach((value, key) => {
                    const normalized = typeof value === 'string' ? value.trim() : value;

                    if (normalized !== '' && normalized !== null && normalized !== undefined) {
                        params.set(key, normalized);
                    }
                });

                return params;
            };

            const loadRecords = async (url = recordsElement.dataset.url) => {
                if (!url) {
                    return;
                }

                recordsElement.classList.add('opacity-50');

                try {
                    const requestUrl = new URL(url, window.location.origin);
                    readFilters().forEach((value, key) => requestUrl.searchParams.set(key, value));
                    const payload = await request(requestUrl.toString());
                    recordsElement.innerHTML = payload.html || '<div class="py-4 text-center text-muted">Nenhum evento encontrado.</div>';
                    adminUI?.initPlugins?.(recordsElement);
                } catch (error) {
                    console.error('Falha ao carregar a tabela da agenda.', error);
                    recordsElement.innerHTML = '<div class="py-4 text-center text-muted">Nao foi possivel carregar os eventos.</div>';
                } finally {
                    recordsElement.classList.remove('opacity-50');
                }
            };

            const ensureCalendarDependencies = async () => {
                if (window.FullCalendar?.Calendar) {
                    return window.FullCalendar;
                }

                throw new Error('FullCalendar nao esta disponivel no painel.');
            };

            const mountCalendar = async () => {
                try {
                    const fullCalendar = await ensureCalendarDependencies();
                    const locale = fullCalendar.locales?.['pt-br'];

                    calendarInstance = new fullCalendar.Calendar(calendarElement, {
                        plugins: fullCalendar.plugins || [],
                        ...(locale ? { locales: [locale] } : {}),
                        locale: 'pt-br',
                        timeZone: 'local',
                        initialView: compactQuery.matches ? 'listWeek' : 'dayGridMonth',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: compactQuery.matches
                                ? 'dayGridMonth,listWeek'
                                : 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
                        },
                        buttonText: {
                            today: 'Hoje',
                            month: 'Mês',
                            week: 'Semana',
                            day: 'Dia',
                            list: 'Lista',
                        },
                        allDayText: 'Dia inteiro',
                        noEventsMessage: 'Nenhum evento encontrado.',
                        height: compactQuery.matches ? 'auto' : Number(calendarElement.dataset.calendarHeight || 650),
                        contentHeight: compactQuery.matches ? 'auto' : Number(calendarElement.dataset.calendarContentHeight || 590),
                        fixedWeekCount: false,
                        showNonCurrentDates: true,
                        dayMaxEvents: compactQuery.matches ? 2 : 4,
                        editable: true,
                        eventStartEditable: true,
                        eventDurationEditable: true,
                        selectable: true,
                        selectMirror: true,
                        nowIndicator: true,
                        navLinks: true,
                        businessHours: true,
                        events: async (fetchInfo, successCallback, failureCallback) => {
                            try {
                                const requestUrl = new URL(calendarElement.dataset.eventsUrl, window.location.origin);
                                requestUrl.searchParams.set('start', fetchInfo.startStr);
                                requestUrl.searchParams.set('end', fetchInfo.endStr);
                                requestUrl.searchParams.set('timeZone', fetchInfo.timeZone);
                                readFilters().forEach((value, key) => requestUrl.searchParams.set(key, value));
                                const payload = await request(requestUrl.toString());
                                successCallback(Array.isArray(payload) ? payload : []);
                            } catch (error) {
                                console.error('Falha ao carregar o feed da agenda.', error);
                                failureCallback(error);
                            }
                        },
                        loading: (state) => {
                            calendarElement.classList.toggle('is-loading', state);
                        },
                        select: (info) => {
                            const createUrl = calendarElement.dataset.createUrl;

                            if (!createUrl || !adminUI?.loadModal) {
                                calendarInstance.unselect();
                                return;
                            }

                            const modalUrl = new URL(createUrl, window.location.origin);
                            modalUrl.searchParams.set('start', info.startStr);
                            modalUrl.searchParams.set('end', info.endStr);
                            modalUrl.searchParams.set('all_day', info.allDay ? '1' : '0');
                            adminUI.loadModal(modalUrl.toString(), 'Novo evento');
                            calendarInstance.unselect();
                        },
                        eventClick: (info) => {
                            info.jsEvent.preventDefault();
                            adminUI?.showCalendarEventPanel?.(info.event, info.jsEvent);
                        },
                        eventContent: (info) => {
                            try {
                                return adminUI?.renderCalendarEventContent?.(info);
                            } catch (eventContentError) {
                                console.error('Falha ao renderizar o conteudo visual do evento.', eventContentError);
                                return undefined;
                            }
                        },
                        eventDidMount: (info) => {
                            adminUI?.decorateCalendarEvent?.(info);
                        },
                        eventDrop: (info) => {
                            adminUI?.updateCalendarEventPosition?.(info, calendarElement);
                        },
                        eventResize: (info) => {
                            adminUI?.updateCalendarEventPosition?.(info, calendarElement);
                        },
                    });

                    calendarElement._fullCalendar = calendarInstance;
                    calendarInstance.render();
                    window.requestAnimationFrame(() => calendarInstance.updateSize());
                } catch (error) {
                    console.error('Falha ao montar a agenda inline.', error);

                    try {
                        const fullCalendar = await ensureCalendarDependencies();
                        const locale = fullCalendar.locales?.['pt-br'];

                        calendarElement.innerHTML = '';
                        calendarInstance = new fullCalendar.Calendar(calendarElement, {
                            plugins: fullCalendar.plugins || [],
                            ...(locale ? { locales: [locale] } : {}),
                            locale: 'pt-br',
                            timeZone: 'local',
                            initialView: compactQuery.matches ? 'listWeek' : 'dayGridMonth',
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: compactQuery.matches
                                    ? 'dayGridMonth,listWeek'
                                    : 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
                            },
                            buttonText: {
                                today: 'Hoje',
                                month: 'Mes',
                                week: 'Semana',
                                day: 'Dia',
                                list: 'Lista',
                            },
                            allDayText: 'Dia inteiro',
                            noEventsMessage: 'Nenhum evento encontrado.',
                            height: compactQuery.matches ? 'auto' : Number(calendarElement.dataset.calendarHeight || 650),
                            contentHeight: compactQuery.matches ? 'auto' : Number(calendarElement.dataset.calendarContentHeight || 590),
                            fixedWeekCount: false,
                            showNonCurrentDates: true,
                            dayMaxEvents: compactQuery.matches ? 2 : 4,
                            editable: true,
                            eventStartEditable: true,
                            eventDurationEditable: true,
                            selectable: true,
                            selectMirror: true,
                            nowIndicator: true,
                            navLinks: true,
                            businessHours: true,
                            events: async (fetchInfo, successCallback, failureCallback) => {
                                try {
                                    const requestUrl = new URL(calendarElement.dataset.eventsUrl, window.location.origin);
                                    requestUrl.searchParams.set('start', fetchInfo.startStr);
                                    requestUrl.searchParams.set('end', fetchInfo.endStr);
                                    requestUrl.searchParams.set('timeZone', fetchInfo.timeZone);
                                    readFilters().forEach((value, key) => requestUrl.searchParams.set(key, value));
                                    const payload = await request(requestUrl.toString());
                                    successCallback(Array.isArray(payload) ? payload : []);
                                } catch (fallbackError) {
                                    console.error('Falha ao carregar o feed da agenda no fallback.', fallbackError);
                                    failureCallback(fallbackError);
                                }
                            },
                            loading: (state) => {
                                calendarElement.classList.toggle('is-loading', state);
                            },
                        });

                        calendarElement._fullCalendar = calendarInstance;
                        calendarInstance.render();
                        window.requestAnimationFrame(() => calendarInstance.updateSize());
                    } catch (fallbackRenderError) {
                        console.error('Falha ao montar fallback da agenda.', fallbackRenderError);
                        calendarElement.innerHTML = '<div class="admin-calendar-fallback">Nao foi possivel carregar a agenda.</div>';
                    }
                }
            };

            filtersForm.addEventListener('input', (event) => {
                if (!event.target.closest('[data-table-search]')) {
                    return;
                }

                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(() => {
                    loadRecords();
                    calendarInstance?.refetchEvents();
                }, 350);
            });

            filtersForm.addEventListener('change', (event) => {
                if (!event.target.closest('[data-table-filter]')) {
                    return;
                }

                loadRecords();
                calendarInstance?.refetchEvents();
            });

            filtersForm.addEventListener('reset', () => {
                window.setTimeout(() => {
                    loadRecords();
                    calendarInstance?.refetchEvents();
                }, 0);
            });

            recordsElement.addEventListener('click', (event) => {
                const link = event.target.closest('.pagination a');

                if (!link) {
                    return;
                }

                event.preventDefault();
                loadRecords(link.href);
            });

            if (typeof compactQuery.addEventListener === 'function') {
                compactQuery.addEventListener('change', () => window.location.reload());
            } else if (typeof compactQuery.addListener === 'function') {
                compactQuery.addListener(() => window.location.reload());
            }

            mountCalendar();
            loadRecords();
        })();
    </script>
@endpush
