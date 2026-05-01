@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Centro de configuracoes</div>
                    <h1>Configuracoes do sistema</h1>
                    <p>Agora cada conjunto de configuracoes possui sua propria pagina, com fluxo operacional separado e mais facil de manter.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-sliders2"></i>
                    <div>
                        <strong>{{ count($sections) }} modulos</strong>
                        <small>{{ $branding['brand_name'] }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-4">
                @foreach($sections as $item)
                    <div class="col-12 col-md-6 col-xxl-4">
                        <a href="{{ $item['url'] }}" class="text-decoration-none">
                            <div class="card admin-table-card h-100">
                                <div class="card-body p-4 d-flex flex-column gap-3">
                                    <div class="d-flex align-items-start justify-content-between gap-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="admin-system-preview-mark"><i class="bi {{ $item['icon'] }}"></i></span>
                                            <div>
                                                <div class="admin-card-kicker">{{ $item['label'] }}</div>
                                                <h3 class="card-title mb-0">{{ $item['title'] }}</h3>
                                            </div>
                                        </div>
                                        <i class="bi bi-arrow-up-right text-muted"></i>
                                    </div>
                                    <p class="text-muted mb-0">{{ $item['description'] }}</p>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="row g-4 mt-1">
                <div class="col-12 col-xl-8">
                    <div class="card admin-table-card">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Resumo operacional</div>
                                <h3 class="card-title">Base atual do sistema</h3>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-4"><div class="admin-brand-preview-card"><span>Usuarios</span><strong class="mt-2">{{ number_format($stats['users'], 0, ',', '.') }}</strong></div></div>
                                <div class="col-md-4"><div class="admin-brand-preview-card"><span>Clientes</span><strong class="mt-2">{{ number_format($stats['clients'], 0, ',', '.') }}</strong></div></div>
                                <div class="col-md-4"><div class="admin-brand-preview-card"><span>Processos</span><strong class="mt-2">{{ number_format($stats['cases'], 0, ',', '.') }}</strong></div></div>
                                <div class="col-md-4"><div class="admin-brand-preview-card"><span>Tarefas</span><strong class="mt-2">{{ number_format($stats['tasks'], 0, ',', '.') }}</strong></div></div>
                                <div class="col-md-4"><div class="admin-brand-preview-card"><span>Andamentos</span><strong class="mt-2">{{ number_format($stats['updates'], 0, ',', '.') }}</strong></div></div>
                                <div class="col-md-4"><div class="admin-brand-preview-card"><span>Agenda</span><strong class="mt-2">{{ number_format($stats['calendar_events'], 0, ',', '.') }}</strong></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card admin-table-card">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Acesso rapido</div>
                                <h3 class="card-title">Acoes relacionadas</h3>
                            </div>
                        </div>
                        <div class="card-body p-4 d-grid gap-3">
                            <a href="{{ route('admin.system-settings.show', 'mail') }}" class="btn btn-outline-primary">Configurar SMTP e e-mails</a>
                            <a href="{{ route('admin.system-settings.show', 'pwa') }}" class="btn btn-outline-primary">Configurar PWA</a>
                            <form action="{{ route('admin.system-settings.seed-demo-data') }}" method="POST" data-ajax-form>
                                @csrf
                                <button type="submit" class="btn btn-primary w-100" data-confirm-submit="true" data-confirm-title="Popular base de demonstracao?" data-confirm-text="Os registros de exemplo serao criados ou atualizados." data-confirm-button="Popular agora">
                                    <i class="bi bi-stars me-1"></i>Popular dados de exemplo
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
