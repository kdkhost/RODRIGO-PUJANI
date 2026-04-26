@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header">
        <div class="container-fluid d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <h1 class="mb-0">{{ $pageTitle }}</h1>
            <button type="button" class="btn btn-primary" data-modal-url="{{ $createUrl }}" data-modal-title="Nova {{ $singularLabel }}">
                <i class="bi bi-plus-circle me-1"></i>Nova {{ $singularLabel }}
            </button>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form id="{{ $toolbarId }}" class="admin-table-toolbar row g-2 mb-3">
                <div class="col-md-5 col-lg-4">
                    <input type="search"
                           class="form-control"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Pesquisar {{ strtolower($pluralLabel) }}"
                           data-table-search
                           data-table-target="#{{ $tableId }}">
                </div>
                <div class="col-auto">
                    <select name="per_page" class="form-select" data-table-filter data-table-target="#{{ $tableId }}">
                        @foreach ([10, 15, 25, 50] as $size)
                            <option value="{{ $size }}" @selected(request('per_page', 10) == $size)>{{ $size }} por página</option>
                        @endforeach
                    </select>
                </div>
            </form>

            <div class="card">
                <div class="card-body">
                    <div id="{{ $tableId }}" data-ajax-table data-toolbar="#{{ $toolbarId }}" data-url="{{ $tableUrl }}">
                        @include($tableView)
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
