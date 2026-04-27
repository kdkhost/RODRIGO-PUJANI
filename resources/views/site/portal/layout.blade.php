<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Portal do cliente' }} - {{ config('app.name') }}</title>
    @vite(['resources/css/site.css', 'resources/js/site.js'])
</head>
<body class="portal-body">
    <main class="portal-shell">
        <section class="portal-panel">
            <div class="portal-panel-bg"></div>
            <div class="portal-panel-overlay"></div>
            <div class="portal-panel-content">
                <a href="{{ route('site.home') }}" class="portal-brand">
                    <span class="portal-brand-mark">{{ $portalPanel['brand']['short'] ?? 'P' }}</span>
                    <span>
                        <strong>Pujani</strong>
                        <small>Portal do cliente</small>
                    </span>
                </a>

                <div class="portal-copy">
                    @if(filled($portalPanel['eyebrow'] ?? null))
                        <span>{{ $portalPanel['eyebrow'] }}</span>
                    @endif
                    <h1>{{ $portalPanel['title'] }}</h1>
                    <p>{{ $portalPanel['description'] }}</p>
                </div>

                @if(($portalPanel['metrics'] ?? collect())->isNotEmpty())
                    <div class="portal-metrics">
                        @foreach($portalPanel['metrics'] as $metric)
                            <div>
                                <strong>{{ $metric['title'] }}</strong>
                                <span>{{ $metric['subtitle'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="portal-content">
            <div class="portal-card">
                @yield('content')
            </div>
        </section>
    </main>
</body>
</html>
