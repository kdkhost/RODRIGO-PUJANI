@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Integração oficial OAuth 2.0</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Sincronização bidirecional por usuário, sem duplicar eventos e sem apagar compromissos remotos automaticamente.</p>
                </div>
                <a class="btn btn-outline-secondary" href="{{ route('admin.calendar.index') }}"><i class="bi bi-arrow-left me-1"></i>Voltar à agenda</a>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            @if($connectionError)<div class="alert alert-warning">{{ $connectionError }}</div>@endif

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card admin-table-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Conta Google</div>
                                <h3 class="card-title">{{ $connection ? 'Integração conectada' : 'Conectar calendário' }}</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            @unless($integrationConfigured)
                                <div class="alert alert-warning mb-4">Configure o cliente OAuth e o segredo no ambiente antes de conectar uma conta.</div>
                            @endunless

                            @if(!$connection)
                                <p>A autorização usa somente os escopos necessários para identificar a conta, listar calendários e sincronizar eventos.</p>
                                @if(Route::has('admin.google-calendar.connect'))
                                    <a class="btn btn-primary" href="{{ route('admin.google-calendar.connect') }}" @disabled(!$integrationConfigured)>
                                        <i class="bi bi-google me-1"></i>Conectar com Google
                                    </a>
                                @endif
                            @else
                                <dl class="row mb-4">
                                    <dt class="col-sm-4">Conta</dt><dd class="col-sm-8">{{ $connection->google_account_email ?: 'Conta autorizada' }}</dd>
                                    <dt class="col-sm-4">Calendário</dt><dd class="col-sm-8">{{ $connection->calendar_name ?: $connection->calendar_id }}</dd>
                                    <dt class="col-sm-4">Última sincronização</dt><dd class="col-sm-8">{{ $connection->last_synced_at?->format('d/m/Y H:i:s') ?: 'Ainda não executada' }}</dd>
                                    <dt class="col-sm-4">Estado</dt><dd class="col-sm-8"><span class="badge {{ $connection->sync_enabled ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ $connection->sync_enabled ? 'Ativa' : 'Pausada' }}</span></dd>
                                </dl>

                                @if(Route::has('admin.google-calendar.update'))
                                    <form action="{{ route('admin.google-calendar.update') }}" method="POST" data-ajax-form class="row g-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-md-8">
                                            <label class="form-label">Calendário de destino</label>
                                            <select class="form-select" name="calendar_id" required>
                                                @foreach($calendars as $calendar)
                                                    @if(in_array($calendar['access_role'], ['owner', 'writer'], true))
                                                        <option value="{{ $calendar['id'] }}" @selected($connection->calendar_id === $calendar['id'])>{{ $calendar['summary'] }}{{ $calendar['primary'] ? ' (principal)' : '' }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="google_sync_enabled" name="sync_enabled" value="1" @checked($connection->sync_enabled)>
                                                <label class="form-check-label" for="google_sync_enabled">Sincronização ativa</label>
                                            </div>
                                        </div>
                                        <div class="col-12"><button class="btn btn-primary" type="submit">Salvar configuração</button></div>
                                    </form>
                                @endif

                                <div class="d-flex flex-wrap gap-2 mt-4">
                                    @if(Route::has('admin.google-calendar.sync'))
                                        <form action="{{ route('admin.google-calendar.sync') }}" method="POST" data-ajax-form>
                                            @csrf
                                            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Sincronizar agora</button>
                                        </form>
                                    @endif
                                    @if(Route::has('admin.google-calendar.disconnect'))
                                        <button class="btn btn-outline-danger" type="button" data-delete-url="{{ route('admin.google-calendar.disconnect') }}" data-confirm-text="A conexão local será removida. Nenhum evento remoto será apagado.">
                                            <i class="bi bi-link-45deg me-1"></i>Desconectar
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card admin-table-card h-100">
                        <div class="card-header"><h3 class="card-title">Configuração OAuth</h3></div>
                        <div class="card-body">
                            <p class="text-muted">Cadastre exatamente este URI de redirecionamento no Google Cloud Console:</p>
                            <code class="d-block text-break p-3 rounded bg-body-tertiary">{{ $redirectUri }}</code>
                            <hr>
                            <ul class="mb-0">
                                <li>Tokens são criptografados no banco.</li>
                                <li>O refresh ocorre automaticamente antes da expiração.</li>
                                <li>Mapeamentos impedem duplicidade de eventos.</li>
                                <li>Cancelamentos remotos são refletidos localmente; exclusões remotas nunca são automáticas.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
