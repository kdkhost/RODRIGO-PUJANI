@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Governança de acesso</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Organize a malha de acesso do sistema com nomenclatura técnica consistente, visão por módulo e leitura mais clara das regras.</p>
                </div>
                <button type="button" class="btn btn-primary admin-action-button" data-modal-url="{{ $createUrl }}" data-modal-title="Nova {{ $singularLabel }}">
                    <i class="bi bi-plus-circle me-1"></i>Nova {{ $singularLabel }}
                </button>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="admin-permission-kpis mb-4">
                <div class="admin-permission-kpi-card">
                    <span>Total mapeado</span>
                    <strong>{{ number_format($permissionStats['total'], 0, ',', '.') }}</strong>
                    <small>Permissões registradas no ACL</small>
                </div>
                <div class="admin-permission-kpi-card">
                    <span>Módulos cobertos</span>
                    <strong>{{ number_format($permissionStats['modules'], 0, ',', '.') }}</strong>
                    <small>Áreas com isolamento de acesso</small>
                </div>
                <div class="admin-permission-kpi-card">
                    <span>Contextos ativos</span>
                    <strong>{{ number_format($permissionStats['guards'], 0, ',', '.') }}</strong>
                    <small>Contextos de autenticação em uso</small>
                </div>
                <div class="admin-permission-kpi-card">
                    <span>Itens sensíveis</span>
                    <strong>{{ number_format($permissionStats['sensitive'], 0, ',', '.') }}</strong>
                    <small>Rotinas críticas para revisão</small>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card admin-table-card">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Catálogo técnico</div>
                                <h3 class="card-title">Mapa completo de permissões</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="{{ $toolbarId }}" class="admin-table-toolbar mb-4">
                                <div class="admin-search-box">
                                    <i class="bi bi-search"></i>
                                    <input type="search"
                                        class="form-control"
                                        name="search"
                                        value="{{ $search }}"
                                        placeholder="Pesquisar por chave técnica, módulo ou guard"
                                        data-table-search
                                        data-table-target="#{{ $tableId }}">
                                </div>
                                <select name="per_page" class="form-select" data-table-filter data-table-target="#{{ $tableId }}">
                                    @foreach ([10, 15, 25, 50] as $size)
                                        <option value="{{ $size }}" @selected(request('per_page', 10) == $size)>{{ $size }} por página</option>
                                    @endforeach
                                </select>
                            </form>

                            <div id="{{ $tableId }}" data-ajax-table data-toolbar="#{{ $toolbarId }}" data-url="{{ $tableUrl }}">
                                @include($tableView)
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card admin-insight-card mb-4">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Padrão sugerido</div>
                                <h3 class="card-title">Nomenclatura consistente</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-insight-copy">
                                <p>Use a chave técnica no formato <code>modulo.acao</code> para manter regras previsíveis e simples de auditar.</p>
                            </div>
                            <div class="admin-permission-patterns">
                                <div><strong>Listagem</strong><code>pages.manage</code></div>
                                <div><strong>Edição</strong><code>settings.manage</code></div>
                                <div><strong>Agenda</strong><code>calendar.manage</code></div>
                                <div><strong>Acesso avançado</strong><code>system-files.manage</code></div>
                            </div>
                        </div>
                    </div>

                    <div class="card admin-insight-card mb-4">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Módulos mais densos</div>
                                <h3 class="card-title">Distribuição por área</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-insight-stack">
                                @foreach ($moduleHighlights as $module)
                                    <div class="admin-insight-row">
                                        <span>{{ $module['label'] }}</span>
                                        <strong>{{ number_format($module['count'], 0, ',', '.') }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card admin-insight-card">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Ações predominantes</div>
                                <h3 class="card-title">Verbos mais usados</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-permission-badges">
                                @foreach ($actionHighlights as $action)
                                    <span class="admin-permission-badge">
                                        {{ $action['label'] }}: {{ number_format($action['count'], 0, ',', '.') }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
