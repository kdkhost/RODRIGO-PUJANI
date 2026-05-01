@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">{{ $sectionMeta['label'] }}</div>
                    <h1>{{ $sectionMeta['title'] }}</h1>
                    <p>{{ $sectionMeta['description'] }}</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.system-settings.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-grid me-1"></i>Central de configuracoes
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-12 col-xl-3">
                    <div class="card admin-table-card">
                        <div class="card-body p-3 d-grid gap-2">
                            @foreach($sections as $item)
                                <a href="{{ $item['url'] }}" class="btn {{ $item['key'] === $sectionKey ? 'btn-primary' : 'btn-outline-secondary' }} text-start">
                                    <i class="bi {{ $item['icon'] }} me-2"></i>{{ $item['title'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-9">
                    <form action="{{ route('admin.system-settings.update', $sectionKey) }}" method="POST" data-ajax-form enctype="multipart/form-data" class="d-grid gap-4">
                        @csrf
                        @method('PUT')

                        @includeIf('admin.system-settings.sections.'.$sectionKey)

                        <div class="card admin-table-card">
                            <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 p-4">
                                <div>
                                    <div class="admin-card-kicker mb-1">Aplicacao das alteracoes</div>
                                    <strong>Salvar {{ strtolower($sectionMeta['title']) }}</strong>
                                    <p class="text-muted mb-0">Esta pagina grava somente os campos relacionados a este modulo.</p>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>Salvar configuracoes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@includeWhen($sectionKey === 'mail', 'admin.system-settings.partials.mail-scripts')
