@php
    $branding = branding_config();
    $recaptcha = recaptcha_config();
    $statusMessage = session('status');
    $authPanel = [
        'eyebrow' => (string) setting('auth.panel_eyebrow', 'Painel administrativo'),
        'title' => (string) setting('auth.panel_title', 'Gestão jurídica com acesso seguro.'),
        'description' => (string) setting('auth.panel_description', 'Painel administrativo para conteúdo, agenda, mídias, usuários e permissões do escritório.'),
    ];

    $authMetrics = collect([1, 2, 3])
        ->map(fn (int $index): array => [
            'title' => (string) setting("auth.metric_{$index}_title", match ($index) {
                1 => 'Laravel 13',
                2 => 'ACL',
                default => 'PWA',
            }),
            'subtitle' => (string) setting("auth.metric_{$index}_subtitle", match ($index) {
                1 => 'Base atual',
                2 => 'Permissões',
                default => 'Experiência em app',
            }),
        ])
        ->filter(fn (array $metric): bool => filled($metric['title']) || filled($metric['subtitle']))
        ->values();
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-recaptcha-enabled="{{ $recaptcha['enabled'] ? '1' : '0' }}"
    data-recaptcha-site-key="{{ $recaptcha['enabled'] ? $recaptcha['site_key'] : '' }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $attributes->get('title', 'Acesso | '.$branding['brand_name']) }}</title>

        @if($branding['favicon_url'])
            <link rel="icon" href="{{ $branding['favicon_url'] }}">
            <link rel="apple-touch-icon" href="{{ $branding['favicon_url'] }}">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        @if ($statusMessage)
            <div data-page-toast data-type="success" data-message="{{ $statusMessage }}"></div>
        @endif
        @if (session('error'))
            <div data-page-toast data-type="error" data-message="{{ session('error') }}"></div>
        @endif
        @foreach ($errors->all() as $message)
            <div data-page-toast data-type="warning" data-message="{{ $message }}"></div>
        @endforeach

        <main class="auth-premium-shell">
            <section class="auth-premium-panel" aria-label="{{ $branding['brand_name'] }}">
                <div class="auth-premium-panel-bg"></div>
                <div class="auth-premium-panel-overlay"></div>
                <div class="auth-premium-panel-content">
                    <a href="{{ route('site.home') }}" class="auth-brand">
                        @if($branding['logo_url'])
                            <img
                                src="{{ $branding['logo_url'] }}"
                                alt="{{ $branding['brand_name'] }}"
                                class="auth-brand-logo"
                            >
                        @else
                            <span class="auth-brand-mark">{{ $branding['brand_short_name'] }}</span>
                        @endif
                        <span>
                            <strong>{{ $branding['brand_name'] }}</strong>
                            <small>{{ $branding['admin_subtitle'] }}</small>
                        </span>
                    </a>

                    <div class="auth-panel-copy">
                        @if(filled($authPanel['eyebrow']))
                            <span>{{ $authPanel['eyebrow'] }}</span>
                        @endif
                        @if(filled($authPanel['title']))
                            <h1>{{ $authPanel['title'] }}</h1>
                        @endif
                        @if(filled($authPanel['description']))
                            <p>{{ $authPanel['description'] }}</p>
                        @endif
                    </div>

                    @if($authMetrics->isNotEmpty())
                        <div class="auth-panel-metrics" aria-label="Recursos do painel">
                            @foreach($authMetrics as $metric)
                                <div>
                                    @if(filled($metric['title']))
                                        <strong>{{ $metric['title'] }}</strong>
                                    @endif
                                    @if(filled($metric['subtitle']))
                                        <span>{{ $metric['subtitle'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <section class="auth-form-panel">
                <div class="auth-form-card">
                    {{ $slot }}
                </div>
            </section>
        </main>
    </body>
</html>
