<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? config('app.name') }}</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    @if (session('status'))
        <div data-page-toast data-type="success" data-message="{{ session('status') }}"></div>
    @endif
    @if (session('error'))
        <div data-page-toast data-type="error" data-message="{{ session('error') }}"></div>
    @endif

    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-2">
                        <button class="btn btn-sm btn-outline-secondary" type="button" id="theme-toggle">
                            <i class="bi bi-moon-stars"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" href="#">
                            <span class="fw-semibold">{{ auth()->user()->name }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="{{ route('profile.edit') }}" class="dropdown-item">Perfil</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Sair</button>
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar shadow" data-bs-theme="dark">
            <div class="sidebar-brand">
                <a href="{{ route('admin.dashboard') }}" class="brand-link text-decoration-none">
                    <span class="brand-text fw-semibold">Pujani Admin</span>
                </a>
            </div>
            <div class="sidebar-wrapper">
                @include('admin.partials.sidebar')
            </div>
        </aside>

        <main class="app-main">
            @yield('content')
        </main>
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
</body>
</html>
