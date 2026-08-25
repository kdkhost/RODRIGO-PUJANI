@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Operação jurídica</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Controle prazos, responsáveis, processos, lembretes e o vínculo canônico com a agenda.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(Route::has('admin.google-calendar.index') && auth()->user()?->can('google-calendar.manage'))
                        <a class="btn btn-outline-primary" href="{{ route('admin.google-calendar.index') }}">
                            <i class="bi bi-google me-1"></i>Google Calendar
                        </a>
                    @endif
                    <button type="button" class="btn btn-primary admin-action-button" data-modal-url="{{ $createUrl }}" data-modal-title="Cadastrar {{ $singularLabel }}">
                        <i class="bi bi-plus-circle me-1"></i>Novo prazo ou tarefa
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="admin-calendar-kpis mb-4">
                @foreach (['today' => 'Hoje', 'tomorrow' => 'Amanhã', 'week' => 'Nesta semana', 'overdue' => 'Vencidos'] as $scope => $label)
                    <a class="admin-calendar-kpi text-decoration-none" href="{{ request()->fullUrlWithQuery(['due_scope' => $scope, 'page' => null]) }}">
                        <span>{{ $label }}</span>
                        <strong>{{ number_format($deadlineStats[$scope], 0, ',', '.') }}</strong>
                    </a>
                @endforeach
            </div>

            <form id="{{ $toolbarId }}" class="admin-table-toolbar mb-3">
                <div class="admin-search-box">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" name="search" value="{{ $search }}" placeholder="Pesquisar prazos e tarefas" data-table-search data-table-target="#{{ $tableId }}">
                </div>
                <select name="due_scope" class="form-select" data-table-filter data-table-target="#{{ $tableId }}">
                    <option value="">Todos os períodos</option>
                    <option value="today" @selected(request('due_scope') === 'today')>Hoje</option>
                    <option value="tomorrow" @selected(request('due_scope') === 'tomorrow')>Amanhã</option>
                    <option value="week" @selected(request('due_scope') === 'week')>Nesta semana</option>
                    <option value="overdue" @selected(request('due_scope') === 'overdue')>Vencidos</option>
                </select>
                <select name="assigned_user_id" class="form-select" data-table-filter data-table-target="#{{ $tableId }}">
                    <option value="">Todos os responsáveis</option>
                    @foreach($filterUsers as $user)
                        <option value="{{ $user->id }}" @selected((string) request('assigned_user_id') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                <select name="legal_case_id" class="form-select" data-table-filter data-table-target="#{{ $tableId }}">
                    <option value="">Todos os processos</option>
                    @foreach($filterCases as $case)
                        <option value="{{ $case->id }}" @selected((string) request('legal_case_id') === (string) $case->id)>{{ $case->title }}{{ $case->process_number ? ' · '.$case->process_number : '' }}</option>
                    @endforeach
                </select>
                <select name="client_id" class="form-select" data-table-filter data-table-target="#{{ $tableId }}">
                    <option value="">Todos os clientes</option>
                    @foreach($filterClients as $client)
                        <option value="{{ $client->id }}" @selected((string) request('client_id') === (string) $client->id)>{{ $client->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select" data-table-filter data-table-target="#{{ $tableId }}">
                    <option value="">Todos os status</option>
                    @foreach($statusLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="task_type" class="form-select" data-table-filter data-table-target="#{{ $tableId }}">
                    <option value="">Todos os tipos</option>
                    @foreach($taskTypeLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('task_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="per_page" class="form-select" data-table-filter data-table-target="#{{ $tableId }}">
                    @foreach ([10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected(request('per_page', 10) == $size)>{{ $size }} por página</option>
                    @endforeach
                </select>
                <a class="btn btn-outline-secondary" href="{{ route($routeBase.'.index') }}"><i class="bi bi-arrow-counterclockwise me-1"></i>Limpar</a>
            </form>

            <div class="card admin-table-card">
                <div class="card-body">
                    <div id="{{ $tableId }}" data-ajax-table data-toolbar="#{{ $toolbarId }}" data-url="{{ $tableUrl }}">
                        @include($tableView)
                    </div>
                </div>
            </div>

            @if(Route::has('admin.legal-deadlines.preferences.update') && auth()->user()?->can('legal-deadlines.reminders'))
                <div class="card admin-table-card mt-4">
                    <div class="card-header">
                        <div>
                            <div class="admin-card-kicker">Notificações</div>
                            <h3 class="card-title">Lembretes e resumo diário</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.legal-deadlines.preferences.update') }}" method="POST" data-ajax-form class="row g-3 align-items-end">
                            @csrf
                            @method('PUT')
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="deadline_reminders_enabled" name="deadline_reminders_enabled" value="1" @checked($deadlinePreference->deadline_reminders_enabled)>
                                    <label class="form-check-label" for="deadline_reminders_enabled">Lembretes individuais</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input type="checkbox" class="form-check-input" id="daily_summary_enabled" name="daily_summary_enabled" value="1" @checked($deadlinePreference->daily_summary_enabled)>
                                    <label class="form-check-label" for="daily_summary_enabled">Resumo diário</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Horário do resumo</label>
                                <input type="time" class="form-control" name="daily_summary_time" value="{{ substr((string) $deadlinePreference->daily_summary_time, 0, 5) }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Antecedência (dias)</label>
                                <input type="number" class="form-control" name="daily_summary_days_ahead" min="1" max="30" value="{{ $deadlinePreference->daily_summary_days_ahead ?: 7 }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Fuso horário</label>
                                <select class="form-select" name="timezone" required>
                                    @foreach(['America/Sao_Paulo', 'America/Manaus', 'America/Cuiaba', 'America/Fortaleza', 'America/Recife', 'America/Belem', 'America/Rio_Branco'] as $timezone)
                                        <option value="{{ $timezone }}" @selected(($deadlinePreference->timezone ?: config('app.timezone')) === $timezone)>{{ $timezone }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">E-mail opcional</label>
                                <input type="email" class="form-control" name="email" value="{{ $deadlinePreference->email }}" placeholder="Usar e-mail do usuário">
                            </div>
                            <div class="col-md-1 d-grid">
                                <button class="btn btn-primary" type="submit">Salvar</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
