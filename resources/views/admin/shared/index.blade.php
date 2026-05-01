@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Gestão operacional</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Organize, revise e publique registros com uma rotina administrativa objetiva.</p>
                </div>
                @if(($canCreate ?? true) && !empty($createUrl))
                    <button type="button" class="btn btn-primary admin-action-button" data-modal-url="{{ $createUrl }}" data-modal-title="Cadastrar {{ $singularLabel }}">
                        <i class="bi bi-plus-circle me-1"></i>Cadastrar {{ $singularLabel }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form id="{{ $toolbarId }}" class="admin-table-toolbar mb-3">
                <div class="admin-search-box">
                    <i class="bi bi-search"></i>
                    <input type="search"
                        class="form-control"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Pesquisar {{ strtolower($pluralLabel) }}"
                        data-table-search
                        data-table-target="#{{ $tableId }}">
                </div>
                <select name="per_page" class="form-select" data-table-filter data-table-target="#{{ $tableId }}">
                    @foreach ([10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected(request('per_page', 10) == $size)>{{ $size }} por página</option>
                    @endforeach
                </select>
            </form>

            <div class="card admin-table-card">
                <div class="card-body">
                    <div id="{{ $tableId }}" data-ajax-table data-toolbar="#{{ $toolbarId }}" data-url="{{ $tableUrl }}">
                        @include($tableView)
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
