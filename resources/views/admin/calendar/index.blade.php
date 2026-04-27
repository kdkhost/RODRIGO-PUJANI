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
@endphp

@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero admin-calendar-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Operação completa</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Visualize, crie, arraste, redimensione, filtre e organize eventos com FullCalendar 4 integrado ao banco do sistema.</p>
                </div>
                <button class="btn btn-primary admin-action-button" type="button" data-modal-url="{{ route('admin.calendar.create') }}" data-modal-title="Novo evento">
                    <i class="bi bi-calendar-plus me-1"></i>Novo evento
                </button>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form class="admin-table-toolbar mb-3" data-calendar-toolbar="#admin-calendar">
                <div class="admin-search-box">
                    <i class="bi bi-funnel"></i>
                    <input type="text" name="category" class="form-control" list="calendar-categories" placeholder="Filtrar por categoria" data-calendar-filter>
                    <datalist id="calendar-categories">
                        @foreach ($categories as $category)
                            <option value="{{ $category }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <select name="status" class="form-select" data-calendar-filter>
                    <option value="">Todos os status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</option>
                    @endforeach
                </select>
                <select name="visibility" class="form-select" data-calendar-filter>
                    <option value="">Todas as visibilidades</option>
                    @foreach ($visibilities as $visibility)
                        <option value="{{ $visibility }}">{{ $visibilityLabels[$visibility] ?? ucfirst($visibility) }}</option>
                    @endforeach
                </select>
                <select name="owner_id" class="form-select" data-calendar-filter>
                    <option value="">Todos os responsáveis</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-secondary" type="reset" data-calendar-reset>
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Limpar
                </button>
            </form>

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
                    <div
                        id="admin-calendar"
                        class="admin-calendar"
                        data-calendar
                        data-calendar-height="640"
                        data-calendar-content-height="560"
                        data-calendar-aspect-ratio="1.72"
                        data-calendar-events-url="{{ route('admin.calendar.events') }}"
                        data-calendar-create-url="{{ route('admin.calendar.create') }}"
                        data-calendar-toolbar="[data-calendar-toolbar='#admin-calendar']"
                    ></div>
                </div>
            </div>
        </div>
    </div>
@endsection
