@php
    $items = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'bi-speedometer2', 'permission' => null],
        ['label' => 'Paginas', 'route' => 'admin.pages.index', 'icon' => 'bi-window-stack', 'permission' => 'pages.manage'],
        ['label' => 'Secoes', 'route' => 'admin.page-sections.index', 'icon' => 'bi-layout-text-window-reverse', 'permission' => 'page-sections.manage'],
        ['label' => 'Areas de Atuacao', 'route' => 'admin.practice-areas.index', 'icon' => 'bi-briefcase', 'permission' => 'practice-areas.manage'],
        ['label' => 'Equipe', 'route' => 'admin.team-members.index', 'icon' => 'bi-people', 'permission' => 'team-members.manage'],
        ['label' => 'Depoimentos', 'route' => 'admin.testimonials.index', 'icon' => 'bi-chat-square-quote', 'permission' => 'testimonials.manage'],
        ['label' => 'Mensagens', 'route' => 'admin.contact-messages.index', 'icon' => 'bi-envelope', 'permission' => 'contact-messages.manage'],
        ['label' => 'Midias', 'route' => 'admin.media-assets.index', 'icon' => 'bi-images', 'permission' => 'media-assets.manage'],
        ['label' => 'SEO Global', 'route' => 'admin.seo-metas.index', 'icon' => 'bi-globe', 'permission' => 'seo-metas.manage'],
        ['label' => 'Analytics', 'route' => 'admin.analytics.index', 'icon' => 'bi-bar-chart', 'permission' => 'analytics.view'],
        ['label' => 'Usuarios', 'route' => 'admin.users.index', 'icon' => 'bi-person-gear', 'permission' => 'users.manage'],
        ['label' => 'Funcoes', 'route' => 'admin.roles.index', 'icon' => 'bi-shield-check', 'permission' => 'roles.manage'],
        ['label' => 'Permissoes', 'route' => 'admin.permissions.index', 'icon' => 'bi-key', 'permission' => 'permissions.manage'],
        ['label' => 'Configuracoes', 'route' => 'admin.settings.index', 'icon' => 'bi-sliders', 'permission' => 'settings.manage'],
        ['label' => 'Arquivos do Sistema', 'route' => 'admin.system-files.index', 'icon' => 'bi-file-earmark-code', 'permission' => 'system-files.manage'],
    ];
@endphp

<nav class="mt-2">
    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
        @foreach ($items as $item)
            @if($item['permission'] && ! auth()->user()?->can($item['permission']))
                @continue
            @endif
            <li class="nav-item">
                <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                    <i class="nav-icon bi {{ $item['icon'] }}"></i>
                    <p>{{ $item['label'] }}</p>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
