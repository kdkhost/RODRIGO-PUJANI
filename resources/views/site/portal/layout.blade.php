@php
    $branding = branding_config();
    $recaptcha = recaptcha_config();
@endphp
<!DOCTYPE html>
<html
    lang="pt-BR"
    data-recaptcha-enabled="{{ $recaptcha['enabled'] ? '1' : '0' }}"
    data-recaptcha-site-key="{{ $recaptcha['enabled'] ? $recaptcha['site_key'] : '' }}"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Portal do cliente' }} - {{ $branding['brand_name'] }}</title>
    @if($branding['favicon_url'])
        <link rel="icon" href="{{ $branding['favicon_url'] }}">
        <link rel="apple-touch-icon" href="{{ $branding['favicon_url'] }}">
    @endif
    @vite(['resources/css/site.css', 'resources/js/site.js'])
</head>
<body class="portal-body">
    @if (session('portal_status'))
        <div data-page-toast data-type="success" data-message="{{ session('portal_status') }}"></div>
    @endif
    @if (session('portal_error'))
        <div data-page-toast data-type="error" data-message="{{ session('portal_error') }}"></div>
    @endif
    @if (session('status'))
        <div data-page-toast data-type="success" data-message="{{ session('status') }}"></div>
    @endif
    @if (session('error'))
        <div data-page-toast data-type="error" data-message="{{ session('error') }}"></div>
    @endif
    @foreach ($errors->all() as $message)
        <div data-page-toast data-type="warning" data-message="{{ $message }}"></div>
    @endforeach

    <main class="portal-shell @hasSection('portal_full_width') portal-shell-full @endif">
        <section class="portal-panel">
            <div class="portal-panel-bg"></div>
            <div class="portal-panel-overlay"></div>
            <div class="portal-panel-content">
                <a href="{{ route('site.home') }}" class="portal-brand">
                    @if($portalPanel['brand']['logo_url'] ?? null)
                        <img
                            src="{{ $portalPanel['brand']['logo_url'] }}"
                            alt="{{ $portalPanel['brand']['name'] ?? $branding['brand_name'] }}"
                            class="portal-brand-logo"
                        >
                    @else
                        <span class="portal-brand-mark">{{ $portalPanel['brand']['short'] ?? $branding['brand_short_name'] }}</span>
                    @endif
                    <span>
                        <strong>{{ $portalPanel['brand']['name'] ?? $branding['brand_name'] }}</strong>
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

    <button type="button" class="site-scroll-top" data-scroll-top aria-label="Voltar ao topo" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m18 15-6-6-6 6"></path>
        </svg>
    </button>
</body>
</html>
