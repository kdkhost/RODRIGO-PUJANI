@php
    $branding = branding_config();
    $recaptcha = recaptcha_config();
    $portalFullWidth = trim($__env->yieldContent('portal_full_width')) !== '';
    $portalClient = $client ?? null;
    $portalAvatarUrl = $portalClient?->avatar_path ? site_asset_url($portalClient->avatar_path) : null;
    $portalClientInitials = collect(explode(' ', (string) ($portalClient?->name ?: $branding['brand_name'])))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');
    $portalSupport = $portalSupport ?? [
        'whatsapp_enabled' => false,
        'internal_enabled' => false,
        'whatsapp_contacts' => collect(),
        'messages_url' => route('portal.messages.index'),
        'compose_url' => route('portal.messages.store'),
    ];
    $portalWhatsappContacts = collect($portalSupport['whatsapp_contacts'] ?? []);
    $portalNotifications = $portalNotifications ?? ['unread_count' => 0, 'items' => []];
    $portalLoginBackgroundPath = (string) setting('portal.login_background_path', '');
    $portalLoginBackgroundUrl = $portalLoginBackgroundPath !== '' ? site_asset_url($portalLoginBackgroundPath) : '';
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
    <script>
        (function () {
            try {
                var stored = window.localStorage.getItem('portal-client-theme');
                var preferred = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                var theme = stored === 'dark' || stored === 'light' ? stored : preferred;
                document.documentElement.setAttribute('data-portal-theme', theme);
                document.documentElement.style.colorScheme = theme;
            } catch (error) {
                document.documentElement.setAttribute('data-portal-theme', 'light');
                document.documentElement.style.colorScheme = 'light';
            }
        })();
    </script>
    @vite(['resources/css/site.css', 'resources/js/site.js'])
    @if($portalFullWidth)
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css">
        <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
    @endif
</head>
<body class="portal-body {{ $portalFullWidth ? 'portal-client-body' : '' }}"
    @if($portalFullWidth)
        data-portal-client-id="{{ $portalClient?->id }}"
        data-portal-tour-enabled="true"
        data-portal-notifications-url="{{ route('portal.notifications.feed') }}"
        data-portal-notifications-read-url="{{ route('portal.notifications.read-all') }}"
    @endif
>
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

    <main class="portal-shell {{ $portalFullWidth ? 'portal-shell-full' : '' }}">
        <section class="portal-panel">
            <div class="portal-panel-bg" @if($portalLoginBackgroundUrl !== '') style="background-image:url('{{ $portalLoginBackgroundUrl }}')" @endif></div>
            <div class="portal-panel-overlay"></div>
            <div class="portal-panel-content">
                <a href="{{ route('site.home') }}" class="portal-brand">
                    @if($portalPanel['brand']['logo_url'] ?? null)
                        <img src="{{ $portalPanel['brand']['logo_url'] }}" alt="{{ $portalPanel['brand']['name'] ?? $branding['brand_name'] }}" class="portal-brand-logo">
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

        @if($portalFullWidth)
            <section class="portal-client-app">
                <aside class="portal-client-sidebar" data-portal-tour-sidebar>
                    <a href="{{ route('portal.dashboard') }}" class="portal-client-brand">
                        @if($portalPanel['brand']['logo_url'] ?? null)
                            <img src="{{ $portalPanel['brand']['logo_url'] }}" alt="{{ $portalPanel['brand']['name'] ?? $branding['brand_name'] }}">
                        @else
                            <span>{{ $portalPanel['brand']['short'] ?? $branding['brand_short_name'] }}</span>
                        @endif
                        <strong>{{ $portalPanel['brand']['name'] ?? $branding['brand_name'] }}</strong>
                        <small>Portal do cliente</small>
                    </a>

                    <nav class="portal-client-nav" aria-label="Navegacao do portal do cliente" data-portal-tour-nav>
                        <a href="{{ route('portal.dashboard') }}" class="{{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i>
                            <span>Painel</span>
                        </a>
                        <a href="{{ route('portal.profile') }}" class="{{ request()->routeIs('portal.profile') ? 'active' : '' }}">
                            <i class="bi bi-person-circle"></i>
                            <span>Meu perfil</span>
                        </a>
                        <a href="{{ route('portal.dashboard') }}#portal-processos" class="{{ request()->routeIs('portal.cases.*') ? 'active' : '' }}">
                            <i class="bi bi-briefcase"></i>
                            <span>Processos</span>
                        </a>
                        <a href="{{ route('portal.documents.index') }}" class="{{ request()->routeIs('portal.documents.*') ? 'active' : '' }}">
                            <i class="bi bi-folder2-open"></i>
                            <span>Documentos</span>
                        </a>
                        <a href="{{ route('portal.messages.index') }}" class="{{ request()->routeIs('portal.messages.*') ? 'active' : '' }}">
                            <i class="bi bi-chat-left-text"></i>
                            <span>Mensagens</span>
                        </a>
                    </nav>
                </aside>

                <div class="portal-client-main">
                    <header class="portal-client-topbar" data-portal-tour-topbar>
                        <div class="portal-client-title">
                            <span>Portal do cliente</span>
                            <strong>{{ $pageTitle ?? 'Painel' }}</strong>
                        </div>
                        <div class="portal-client-user">
                            <button type="button" class="portal-client-icon-button" data-portal-restart-tour title="Reiniciar tour guiado" aria-label="Reiniciar tour guiado">
                                <i class="bi bi-signpost-split"></i>
                            </button>
                            <button type="button" class="portal-client-icon-button" data-portal-notifications-toggle title="Notificacoes" aria-label="Notificacoes">
                                <i class="bi bi-bell" data-portal-notifications-icon></i>
                                <span class="portal-notification-badge {{ ($portalNotifications['unread_count'] ?? 0) > 0 ? '' : 'is-hidden' }}" data-portal-notifications-badge>{{ (int) ($portalNotifications['unread_count'] ?? 0) }}</span>
                            </button>
                            <div class="portal-notification-dropdown" data-portal-notifications-dropdown>
                                <div class="portal-notification-dropdown-head">
                                    <strong>Notificacoes</strong>
                                    <button type="button" data-portal-notifications-read-all>Ler todas</button>
                                </div>
                                <div class="portal-notification-dropdown-list" data-portal-notifications-list>
                                    @forelse(($portalNotifications['items'] ?? []) as $notification)
                                        <a href="{{ $notification['url'] }}" class="portal-notification-item {{ !empty($notification['is_unread']) ? 'is-unread' : '' }}">
                                            <strong>{{ $notification['title'] }}</strong>
                                            <span>{{ $notification['subtitle'] }}</span>
                                            <small>{{ $notification['at_human'] }}</small>
                                        </a>
                                    @empty
                                        <div class="portal-notification-empty">Nenhuma notificacao pendente.</div>
                                    @endforelse
                                </div>
                            </div>
                            <button type="button" class="portal-client-icon-button" data-portal-theme-toggle title="Alternar tema" aria-label="Alternar tema">
                                <i class="bi bi-moon-stars" data-portal-theme-icon></i>
                            </button>
                            <a href="{{ route('portal.profile') }}" class="portal-client-avatar-link" aria-label="Abrir perfil">
                                @if($portalAvatarUrl)
                                    <img src="{{ $portalAvatarUrl }}" alt="{{ $portalClient?->name }}">
                                @else
                                    <span>{{ $portalClientInitials ?: 'CL' }}</span>
                                @endif
                            </a>
                            <div>
                                <strong>{{ $portalClient?->name }}</strong>
                                <small>{{ $portalClient?->email ?: 'Cliente' }}</small>
                            </div>
                            <form action="{{ route('portal.logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="portal-secondary-button">Sair</button>
                            </form>
                        </div>
                    </header>

                    <section class="portal-content" data-portal-tour-content>
                        <div class="portal-card">
                            @yield('content')
                        </div>
                    </section>

                    <footer class="portal-client-footer" data-portal-tour-footer>
                        <div>
                            <strong>Portal seguro do cliente</strong>
                            <span>Acompanhamento reservado de processos, documentos e prazos.</span>
                        </div>
                        <small>&copy; {{ now()->year }} {{ $portalPanel['brand']['name'] ?? $branding['brand_name'] }}</small>
                    </footer>

                    @if(($portalSupport['whatsapp_enabled'] ?? false) || ($portalSupport['internal_enabled'] ?? false))
                        <div class="portal-whatsapp-container" data-portal-tour-whatsapp>
                            <div id="whatsapp-support-box" class="portal-whatsapp-box">
                                <div class="portal-whatsapp-head">
                                    <span>Atendimento liberado</span>
                                    <strong>Suporte do processo</strong>
                                    <p>Canal exibido conforme configuracao do escritorio para o seu cadastro.</p>
                                </div>
                                <div class="portal-support-tabs">
                                    @if($portalSupport['whatsapp_enabled'] ?? false)
                                        <button type="button" class="portal-support-tab is-active" data-portal-support-tab="whatsapp">WhatsApp</button>
                                    @endif
                                    @if($portalSupport['internal_enabled'] ?? false)
                                        <button type="button" class="portal-support-tab {{ !($portalSupport['whatsapp_enabled'] ?? false) ? 'is-active' : '' }}" data-portal-support-tab="internal">Mensagem interna</button>
                                    @endif
                                </div>

                                @if($portalSupport['whatsapp_enabled'] ?? false)
                                    <div class="portal-support-panel is-active" data-portal-support-panel="whatsapp">
                                        <div class="portal-whatsapp-list">
                                            @foreach($portalWhatsappContacts as $contact)
                                                @php
                                                    $digits = preg_replace('/\D+/', '', (string) $contact['whatsapp']);
                                                    $waNumber = str_starts_with($digits, '55') ? $digits : '55'.$digits;
                                                    $message = rawurlencode('Ola, sou cliente do portal e gostaria de falar sobre o processo '.$contact['case'].'.');
                                                @endphp
                                                <a href="https://wa.me/{{ $waNumber }}?text={{ $message }}" class="portal-whatsapp-item" target="_blank" rel="noopener">
                                                    <span class="portal-whatsapp-avatar">
                                                        @if($contact['avatar_url'])
                                                            <img src="{{ $contact['avatar_url'] }}" alt="{{ $contact['name'] }}">
                                                        @else
                                                            <i class="bi bi-person"></i>
                                                        @endif
                                                    </span>
                                                    <span class="portal-whatsapp-info">
                                                        <strong>{{ $contact['name'] }}</strong>
                                                        <small>{{ $contact['role'] }}</small>
                                                        <em>{{ str($contact['case'])->limit(46) }}</em>
                                                    </span>
                                                    <span class="portal-whatsapp-action"><i class="bi bi-whatsapp"></i></span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if($portalSupport['internal_enabled'] ?? false)
                                    <div class="portal-support-panel {{ !($portalSupport['whatsapp_enabled'] ?? false) ? 'is-active' : '' }}" data-portal-support-panel="internal">
                                        <a href="{{ $portalSupport['messages_url'] }}" class="portal-link-button w-100 text-center">Abrir canal interno</a>
                                    </div>
                                @endif
                            </div>
                            <button type="button" id="whatsapp-toggle" class="portal-whatsapp-toggle" aria-label="Abrir suporte">
                                <i class="bi bi-headset"></i>
                            </button>
                        </div>
                    @endif
                </div>
            </section>
        @else
            <section class="portal-content">
                <div class="portal-card">
                    @yield('content')
                </div>
            </section>
        @endif
    </main>

    <button type="button" class="site-scroll-top" data-scroll-top aria-label="Voltar ao topo" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m18 15-6-6-6 6"></path>
        </svg>
    </button>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const flashMessages = [
            @if (session('portal_status'))
                { type: 'success', message: @json(session('portal_status')) },
            @endif
            @if (session('portal_error'))
                { type: 'error', message: @json(session('portal_error')) },
            @endif
            @if (session('status'))
                { type: 'success', message: @json(session('status')) },
            @endif
            @if (session('error'))
                { type: 'error', message: @json(session('error')) },
            @endif
        ];

        if (!flashMessages.length || !window.toastr) {
            return;
        }

        flashMessages.forEach(function (item) {
            if (!item || !item.message) {
                return;
            }

            const method = typeof window.toastr[item.type] === 'function' ? item.type : 'info';
            window.toastr[method](item.message);
        });
    });
</script>
</html>
