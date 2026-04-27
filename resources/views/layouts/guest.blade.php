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
                        <span>Admin Suite</span>
                        <h1>Gestao juridica com acesso seguro.</h1>
                        <p>Painel administrativo para conteudo, agenda, midias, usuarios e permissoes do escritorio.</p>
                    </div>

                    <div class="auth-panel-metrics" aria-label="Recursos do painel">
                        <div>
                            <strong>Laravel 13</strong>
                            <span>Base atual</span>
                        </div>
                        <div>
                            <strong>ACL</strong>
                            <span>Permissoes</span>
                        </div>
                        <div>
                            <strong>PWA</strong>
                            <span>Experiencia app</span>
                        </div>
                    </div>
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
