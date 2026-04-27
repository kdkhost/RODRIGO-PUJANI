<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $attributes->get('title', config('app.name', 'Pujani Advogados')) }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        @php
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

        <main class="auth-premium-shell">
            <section class="auth-premium-panel" aria-label="Pujani Advogados">
                <div class="auth-premium-panel-bg"></div>
                <div class="auth-premium-panel-overlay"></div>
                <div class="auth-premium-panel-content">
                    <a href="{{ route('site.home') }}" class="auth-brand">
                        <span class="auth-brand-mark">P</span>
                        <span>
                            <strong>Pujani</strong>
                            <small>Advogados</small>
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
