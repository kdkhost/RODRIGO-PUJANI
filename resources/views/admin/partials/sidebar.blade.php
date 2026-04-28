@php
    $groups = [
        'Visão geral' => [
            ['label' => 'Painel', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'bi-speedometer2', 'permission' => 'admin.access'],
            ['label' => 'Análises', 'route' => 'admin.analytics.index', 'active' => 'admin.analytics.*', 'icon' => 'bi-bar-chart', 'permission' => 'analytics.view'],
            ['label' => 'Meu perfil', 'route' => 'profile.edit', 'active' => 'profile.*', 'icon' => 'bi-person-circle', 'permission' => null],
        ],
        'Jurídico' => [
            ['label' => 'Agenda', 'route' => 'admin.calendar.index', 'active' => 'admin.calendar.*', 'icon' => 'bi-calendar3', 'permission' => 'calendar.manage'],
            ['label' => 'Clientes', 'route' => 'admin.clients.index', 'active' => 'admin.clients.*', 'icon' => 'bi-person-vcard', 'permission' => 'clients.manage'],
            ['label' => 'Processos', 'route' => 'admin.legal-cases.index', 'active' => 'admin.legal-cases.*', 'icon' => 'bi-briefcase', 'permission' => 'legal-cases.manage'],
            ['label' => 'Andamentos', 'route' => 'admin.legal-case-updates.index', 'active' => 'admin.legal-case-updates.*', 'icon' => 'bi-journal-text', 'permission' => 'legal-case-updates.manage'],
            ['label' => 'Tarefas e prazos', 'route' => 'admin.legal-tasks.index', 'active' => 'admin.legal-tasks.*', 'icon' => 'bi-list-check', 'permission' => 'legal-tasks.manage'],
            ['label' => 'Documentos', 'route' => 'admin.legal-documents.index', 'active' => 'admin.legal-documents.*', 'icon' => 'bi-folder2-open', 'permission' => 'legal-documents.manage'],
        ],
        'Conteúdo' => [
            ['label' => 'Páginas', 'route' => 'admin.pages.index', 'active' => 'admin.pages.*', 'icon' => 'bi-window-stack', 'permission' => 'pages.manage'],
            ['label' => 'Seções', 'route' => 'admin.page-sections.index', 'active' => 'admin.page-sections.*', 'icon' => 'bi-layout-text-window-reverse', 'permission' => 'page-sections.manage'],
            ['label' => 'Áreas de atuação', 'route' => 'admin.practice-areas.index', 'active' => 'admin.practice-areas.*', 'icon' => 'bi-briefcase', 'permission' => 'practice-areas.manage'],
            ['label' => 'Equipe', 'route' => 'admin.team-members.index', 'active' => 'admin.team-members.*', 'icon' => 'bi-people', 'permission' => 'team-members.manage'],
            ['label' => 'Depoimentos', 'route' => 'admin.testimonials.index', 'active' => 'admin.testimonials.*', 'icon' => 'bi-chat-square-quote', 'permission' => 'testimonials.manage'],
            ['label' => 'Mídias', 'route' => 'admin.media-assets.index', 'active' => 'admin.media-assets.*', 'icon' => 'bi-images', 'permission' => 'media-assets.manage'],
            ['label' => 'SEO global', 'route' => 'admin.seo-metas.index', 'active' => 'admin.seo-metas.*', 'icon' => 'bi-globe', 'permission' => 'seo-metas.manage'],
        ],
        'Operação' => [
            ['label' => 'Mensagens', 'route' => 'admin.contact-messages.index', 'active' => 'admin.contact-messages.*', 'icon' => 'bi-envelope', 'permission' => 'contact-messages.manage'],
            ['label' => 'Portal do cliente', 'route' => 'admin.client-portal.index', 'active' => 'admin.client-portal.*', 'icon' => 'bi-phone', 'permission' => 'client-portal.manage'],
            ['label' => 'Tela de login', 'route' => 'admin.auth-appearance.index', 'active' => 'admin.auth-appearance.*', 'icon' => 'bi-window-sidebar', 'permission' => 'settings.manage'],
            ['label' => 'Sistema', 'route' => 'admin.system-settings.index', 'active' => 'admin.system-settings.*', 'icon' => 'bi-gear-wide-connected', 'permission' => 'settings.manage'],
            ['label' => 'Configurações', 'route' => 'admin.settings.index', 'active' => 'admin.settings.*', 'icon' => 'bi-sliders', 'permission' => 'settings.manage'],
            ['label' => 'Pré-carregador', 'route' => 'admin.preloader.index', 'active' => 'admin.preloader.*', 'icon' => 'bi-hourglass-split', 'permission' => 'preloader.manage'],
        ],
        'Segurança' => [
            ['label' => 'Usuários', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'bi-person-gear', 'permission' => 'users.manage'],
            ['label' => 'Funções', 'route' => 'admin.roles.index', 'active' => 'admin.roles.*', 'icon' => 'bi-shield-check', 'permission' => 'roles.manage'],
            ['label' => 'Permissões', 'route' => 'admin.permissions.index', 'active' => 'admin.permissions.*', 'icon' => 'bi-key', 'permission' => 'permissions.manage'],
            ['label' => 'Arquivos do sistema', 'route' => 'admin.system-files.index', 'active' => 'admin.system-files.*', 'icon' => 'bi-file-earmark-code', 'permission' => 'system-files.manage', 'super_admin_only' => true],
        ],
        'Ajuda' => [
            ['label' => 'Documentação', 'route' => 'admin.documentation.index', 'active' => 'admin.documentation.*', 'icon' => 'bi-journal-bookmark', 'permission' => null],
        ],
    ];

    $groupIcons = [
        'Visão geral' => 'bi-grid-1x2',
        'Jurídico' => 'bi-bank',
        'Conteúdo' => 'bi-layout-text-sidebar-reverse',
        'Operação' => 'bi-briefcase',
        'Segurança' => 'bi-shield-lock',
        'Ajuda' => 'bi-question-circle',
    ];

    $preparedGroups = collect($groups)
        ->map(function (array $items, string $label) use ($groupIcons): array {
            $visibleItems = collect($items)
                ->filter(function (array $item): bool {
                    $user = auth()->user();

                    if (($item['super_admin_only'] ?? false) && ! $user?->isSuperAdmin()) {
                        return false;
                    }

                    return ! $item['permission'] || $user?->can($item['permission']);
                })
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
