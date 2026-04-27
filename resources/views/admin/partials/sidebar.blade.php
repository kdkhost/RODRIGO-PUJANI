@php
    $groups = [
        'Visao geral' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'bi-speedometer2', 'permission' => 'admin.access'],
            ['label' => 'Analytics', 'route' => 'admin.analytics.index', 'active' => 'admin.analytics.*', 'icon' => 'bi-bar-chart', 'permission' => 'analytics.view'],
            ['label' => 'Agenda', 'route' => 'admin.calendar.index', 'active' => 'admin.calendar.*', 'icon' => 'bi-calendar3', 'permission' => 'calendar.manage'],
            ['label' => 'Meu Perfil', 'route' => 'profile.edit', 'active' => 'profile.*', 'icon' => 'bi-person-circle', 'permission' => null],
        ],
        'Conteudo' => [
            ['label' => 'Paginas', 'route' => 'admin.pages.index', 'active' => 'admin.pages.*', 'icon' => 'bi-window-stack', 'permission' => 'pages.manage'],
            ['label' => 'Secoes', 'route' => 'admin.page-sections.index', 'active' => 'admin.page-sections.*', 'icon' => 'bi-layout-text-window-reverse', 'permission' => 'page-sections.manage'],
            ['label' => 'Areas de Atuacao', 'route' => 'admin.practice-areas.index', 'active' => 'admin.practice-areas.*', 'icon' => 'bi-briefcase', 'permission' => 'practice-areas.manage'],
            ['label' => 'Equipe', 'route' => 'admin.team-members.index', 'active' => 'admin.team-members.*', 'icon' => 'bi-people', 'permission' => 'team-members.manage'],
            ['label' => 'Depoimentos', 'route' => 'admin.testimonials.index', 'active' => 'admin.testimonials.*', 'icon' => 'bi-chat-square-quote', 'permission' => 'testimonials.manage'],
            ['label' => 'Midias', 'route' => 'admin.media-assets.index', 'active' => 'admin.media-assets.*', 'icon' => 'bi-images', 'permission' => 'media-assets.manage'],
            ['label' => 'SEO Global', 'route' => 'admin.seo-metas.index', 'active' => 'admin.seo-metas.*', 'icon' => 'bi-globe', 'permission' => 'seo-metas.manage'],
        ],
        'Operacao' => [
            ['label' => 'Mensagens', 'route' => 'admin.contact-messages.index', 'active' => 'admin.contact-messages.*', 'icon' => 'bi-envelope', 'permission' => 'contact-messages.manage'],
            ['label' => 'Tela de Login', 'route' => 'admin.auth-appearance.index', 'active' => 'admin.auth-appearance.*', 'icon' => 'bi-window-sidebar', 'permission' => 'settings.manage'],
            ['label' => 'Configuracoes', 'route' => 'admin.settings.index', 'active' => 'admin.settings.*', 'icon' => 'bi-sliders', 'permission' => 'settings.manage'],
            ['label' => 'Preloader', 'route' => 'admin.preloader.index', 'active' => 'admin.preloader.*', 'icon' => 'bi-hourglass-split', 'permission' => 'preloader.manage'],
        ],
        'Seguranca' => [
            ['label' => 'Usuarios', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'bi-person-gear', 'permission' => 'users.manage'],
            ['label' => 'Funcoes', 'route' => 'admin.roles.index', 'active' => 'admin.roles.*', 'icon' => 'bi-shield-check', 'permission' => 'roles.manage'],
            ['label' => 'Permissoes', 'route' => 'admin.permissions.index', 'active' => 'admin.permissions.*', 'icon' => 'bi-key', 'permission' => 'permissions.manage'],
            ['label' => 'Arquivos do Sistema', 'route' => 'admin.system-files.index', 'active' => 'admin.system-files.*', 'icon' => 'bi-file-earmark-code', 'permission' => 'system-files.manage'],
        ],
    ];

    $groupIcons = [
        'Visao geral' => 'bi-grid-1x2',
        'Conteudo' => 'bi-layout-text-sidebar-reverse',
        'Operacao' => 'bi-briefcase',
        'Seguranca' => 'bi-shield-lock',
    ];

    $preparedGroups = collect($groups)
        ->map(function (array $items, string $label) use ($groupIcons): array {
            $visibleItems = collect($items)
                ->filter(fn (array $item): bool => ! $item['permission'] || auth()->user()?->can($item['permission']))
                ->values();

            return [
                'label' => $label,
                'icon' => $groupIcons[$label] ?? 'bi-folder2',
                'items' => $visibleItems,
                'active' => $visibleItems->contains(fn (array $item): bool => request()->routeIs($item['active'])),
            ];
        })
        ->filter(fn (array $group): bool => $group['items']->isNotEmpty())
        ->values();

    $openIndex = $preparedGroups->search(fn (array $group): bool => $group['active']);
    $openIndex = $openIndex === false ? 0 : $openIndex;
@endphp

<nav class="mt-3 px-2" aria-label="Menu administrativo">
    <ul
        class="nav sidebar-menu admin-sidebar-menu flex-column"
        data-lte-toggle="treeview"
        data-accordion="true"
        role="menu"
    >
        @foreach ($preparedGroups as $group)
            @php
                $isOpen = $loop->index === $openIndex;
            @endphp

            <li class="nav-item {{ $isOpen ? 'menu-open' : '' }}">
                <a href="#" class="nav-link admin-sidebar-parent-link {{ $group['active'] ? 'active' : '' }}">
                    <i class="nav-icon bi {{ $group['icon'] }}"></i>
                    <p>
                        <span>{{ $group['label'] }}</span>
                        <span class="admin-sidebar-count">{{ $group['items']->count() }}</span>
                        <i class="nav-arrow bi bi-chevron-right"></i>
                    </p>
                </a>

                <ul class="nav nav-treeview">
                    @foreach ($group['items'] as $item)
                        <li class="nav-item">
                            <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['active']) ? 'active' : '' }}">
                                <i class="nav-icon bi {{ $item['icon'] }}"></i>
                                <p>{{ $item['label'] }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>
        @endforeach
    </ul>
</nav>
