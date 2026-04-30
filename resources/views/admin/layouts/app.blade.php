@php
    $branding = branding_config();
    $preloader = preloader_config('admin');
    $currentUser = auth()->user();
    $userInitials = collect(explode(' ', trim((string) $currentUser?->name)))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
    $userInitials = $userInitials !== '' ? mb_strtoupper($userInitials) : 'PA';
    $userAvatarUrl = $currentUser?->avatar_path ? site_asset_url($currentUser->avatar_path) : null;
    $roleSummary = $currentUser && method_exists($currentUser, 'getRoleNames')
        ? $currentUser->getRoleNames()->implode(', ')
        : 'Usuário';
    $statusMessage = match (session('status')) {
        'profile-updated' => 'Perfil atualizado com sucesso.',
        'password-updated' => 'Senha atualizada com sucesso.',
        'verification-link-sent' => 'Link de verificação enviado para o e-mail cadastrado.',
        default => session('status'),
    };
    $contactNotificationEnabled = $currentUser?->can('contact-messages.manage') ?? false;
@endphp
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? config('app.name') }}</title>
    @if($branding['favicon_url'])
        <link rel="icon" href="{{ $branding['favicon_url'] }}">
        <link rel="apple-touch-icon" href="{{ $branding['favicon_url'] }}">
    @endif
    <script>
        window.addEventListener('load', () => {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistration('/')
                    .then((registration) => {
                        registration?.update?.();
                        registration?.active?.postMessage({ type: 'PUJANI_UPDATE_PWA' });
                    })
                    .catch(() => null);
            }

            if ('caches' in window) {
                caches.keys()
                    .then((keys) => Promise.all(
                        keys
                            .filter((key) => key.startsWith('pujani-'))
                            .map((key) => caches.delete(key))
                    ))
                    .catch(() => null);
            }
        });
    </script>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
</head>
<body class="layout-fixed header-fixed sidebar-expand-lg bg-body-tertiary admin-premium-shell" 
      data-user-id="{{ $currentUser?->id }}"
      data-user-role="{{ $currentUser?->roles->first()?->name ?? 'Usuário' }}"
      data-user-timezone="{{ $currentUser?->timezone ?: config('app.timezone') }}"
      data-onboarding-completed="{{ $currentUser?->tour_completed_at ? 'true' : 'false' }}"
      data-onboarding-url="{{ route('admin.documentation.complete-tour') }}"
      data-onboarding-reset-url="{{ route('admin.documentation.reset-tour') }}">
    @if ($statusMessage)
        <div data-page-toast data-type="success" data-message="{{ $statusMessage }}"></div>
    @endif
    @if (session('error'))
        <div data-page-toast data-type="error" data-message="{{ session('error') }}"></div>
    @endif
    @foreach ($errors->all() as $message)
        <div data-page-toast data-type="warning" data-message="{{ $message }}"></div>
    @endforeach
    @if ($preloader['enabled'])
        @include('shared.preloader', ['preloader' => $preloader])
    @endif

    <div class="app-wrapper">
        @if(session('impersonator_id'))
            <div class="admin-impersonation-bar" role="status" aria-live="polite">
                <span class="admin-impersonation-icon"><i class="bi bi-person-check"></i></span>
                <div>
                    <strong>Acesso assistido ativo</strong>
                    <span>Você está acessando como {{ $currentUser?->name }}. Operador original: {{ session('impersonator_name') }}.</span>
                </div>
                <form method="POST" action="{{ route('impersonate.stop') }}">
                    @csrf
                    <button class="btn btn-sm btn-dark admin-impersonation-exit" type="submit">Encerrar</button>
                </form>
            </div>
        @endif

        <nav class="app-header navbar navbar-expand admin-topbar">
            <div class="container-fluid">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item">
                        <a class="nav-link admin-icon-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Alternar menu">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-flex ms-2">
                        <div class="admin-topbar-title">
                            <span class="admin-topbar-kicker">{{ $branding['admin_subtitle'] }}</span>
                            <strong>{{ $pageTitle ?? $branding['brand_name'] }}</strong>
                        </div>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item d-none d-lg-block me-2">
                        <a class="btn btn-sm btn-outline-secondary admin-ghost-button" href="{{ route('site.home') }}" target="_blank" rel="noopener">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Ver site
                        </a>
                    </li>
                    <li class="nav-item me-2">
                        <button
                            class="btn btn-sm btn-outline-secondary admin-topbar-square-button admin-tour-restart-button"
                            type="button"
                            data-restart-tour
                            title="Reiniciar tour guiado"
                            aria-label="Reiniciar tour guiado"
                        >
                            <i class="bi bi-signpost-split"></i>
                        </button>
                    </li>
                    @if($contactNotificationEnabled)
                        <li class="nav-item dropdown me-2">
                            <button
                                class="btn btn-sm btn-outline-secondary admin-notification-toggle admin-topbar-square-button"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                data-admin-notifications-toggle
                                data-notifications-feed-url="{{ route('admin.contact-messages.notifications') }}"
                                data-notifications-mark-url-template="{{ route('admin.contact-messages.mark-viewed', '__ID__') }}"
                            >
                                <i class="bi bi-bell"></i>
                                <span class="admin-notification-badge d-none" data-admin-notifications-badge>0</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end admin-dropdown admin-notification-dropdown">
                                <div class="admin-notification-head">
                                    <div>
                                        <strong>Notificações</strong>
                                        <small>Mensagens do formulário em tempo real</small>
                                    </div>
                                    <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-sm btn-outline-secondary">Abrir</a>
                                </div>
                                <div class="admin-notification-list" data-admin-notifications-list>
                                    <div class="admin-notification-empty">
                                        <i class="bi bi-bell-slash"></i>
                                        <strong>Nenhuma mensagem nova.</strong>
                                        <span>As novas entradas do formulário do site aparecerão aqui.</span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endif
                    <li class="nav-item me-2">
                        <button class="btn btn-sm btn-outline-secondary admin-theme-toggle admin-topbar-square-button" type="button" id="theme-toggle" aria-label="Alternar tema">
                            <i class="bi bi-moon-stars"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle admin-user-menu" data-bs-toggle="dropdown" href="#">
                            @if($userAvatarUrl)
                                <img class="admin-avatar admin-avatar-sm" src="{{ $userAvatarUrl }}" alt="{{ $currentUser?->name }}">
                            @else
                                <span class="admin-avatar admin-avatar-sm">{{ $userInitials }}</span>
                            @endif
                            <span class="admin-user-copy d-none d-sm-flex">
                                <span>{{ $currentUser?->name }}</span>
                                <small>{{ $roleSummary ?: 'Usuário' }}</small>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end admin-dropdown">
                            <div class="dropdown-header">
                                <div class="fw-semibold">{{ $currentUser?->name }}</div>
                                <div class="small text-muted">{{ $currentUser?->email }}</div>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="dropdown-item">
                                <i class="bi bi-person-circle me-2"></i>Perfil
                            </a>
                            <button type="button" class="dropdown-item" data-restart-tour>
                                <i class="bi bi-signpost-split me-2"></i>Reiniciar tour guiado
                            </button>
                            <a href="{{ route('site.home') }}" class="dropdown-item" target="_blank" rel="noopener">
                                <i class="bi bi-globe2 me-2"></i>Ver site
                            </a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                                </button>
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar shadow" data-bs-theme="dark">
            <div class="sidebar-brand">
                <a href="{{ route('admin.dashboard') }}" class="brand-link text-decoration-none">
                    @if($branding['logo_url'])
                        <img class="brand-mark-image" src="{{ $branding['logo_url'] }}" alt="{{ $branding['brand_name'] }}">
                    @else
                        <span class="brand-mark">{{ $branding['brand_short_name'] }}</span>
                    @endif
                    <span class="brand-copy">
                        <span class="brand-text fw-semibold">{{ $branding['brand_name'] }}</span>
                        <small>{{ $branding['admin_subtitle'] }}</small>
                    </span>
                </a>
            </div>
            <div class="sidebar-wrapper">
                @include('admin.partials.sidebar')
            </div>
        </aside>

        <main class="app-main">
            @yield('content')
        </main>

        <button type="button" class="admin-scroll-top" data-admin-scroll-top aria-label="Voltar ao topo" aria-hidden="true">
            <i class="bi bi-arrow-up"></i>
        </button>

        <footer class="app-footer admin-app-footer">
            <div class="container-fluid py-2">

                <div>
                    <strong>{{ $branding['admin_footer_text'] }}</strong>
                    <small>{{ $branding['admin_footer_meta'] }}</small>
                </div>
                <small>&copy; {{ now()->year }} {{ $branding['brand_name'] }}</small>
            </div>
        </footer>
    </div>

    <script>
        (() => {
            const root = document.documentElement;
            const stored = localStorage.getItem('admin-theme');
            if (stored) {
                root.setAttribute('data-bs-theme', stored);
            }

            document.addEventListener('DOMContentLoaded', () => {
                const toggle = document.getElementById('theme-toggle');
                if (!toggle) return;

                toggle.addEventListener('click', () => {
                    const current = root.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
                    const next = current === 'dark' ? 'light' : 'dark';
                    root.setAttribute('data-bs-theme', next);
                    localStorage.setItem('admin-theme', next);
                });
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
