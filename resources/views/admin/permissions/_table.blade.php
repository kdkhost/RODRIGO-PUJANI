@php
    $moduleLabels = $permissionDictionaries['modules'] ?? [];
    $actionLabels = $permissionDictionaries['actions'] ?? [];
@endphp

<div class="admin-table-inline-summary">
    @if ($items->total() > 0)
        Exibindo {{ number_format($items->firstItem(), 0, ',', '.') }}-{{ number_format($items->lastItem(), 0, ',', '.') }}
        de {{ number_format($items->total(), 0, ',', '.') }} permissões.
    @else
        Nenhuma permissão encontrada com os filtros atuais.
    @endif
</div>

@if ($items->isEmpty())
    <div class="admin-permission-empty">
        <i class="bi bi-shield-lock"></i>
        <strong>Nenhuma permissão encontrada</strong>
        <span>Revise a busca ou cadastre uma nova regra de acesso.</span>
    </div>
@else
    <div class="admin-permission-list">
        @foreach($items as $item)
            @php
                [$module, $action] = array_pad(explode('.', $item->name, 2), 2, 'manage');
                $friendlyModule = $moduleLabels[$module] ?? \Illuminate\Support\Str::of($module)->replace(['-', '.'], ' ')->headline();
                $friendlyAction = $actionLabels[$action] ?? \Illuminate\Support\Str::of($action)->replace(['-', '.'], ' ')->headline();
            @endphp

            <article class="admin-permission-card">
                <div class="admin-permission-card-main">
                    <div class="admin-permission-card-header">
                        <div>
                            <div class="admin-permission-card-title">{{ $friendlyModule }}</div>
                            <div class="admin-permission-meta">{{ $friendlyAction }}</div>
                        </div>
                        <div class="admin-permission-badges">
                            <span class="admin-permission-badge">{{ $module }}</span>
                            <span class="badge badge-soft-info">{{ $item->guard_name }}</span>
                        </div>
                    </div>

                    <code class="admin-permission-code">{{ $item->name }}</code>
                </div>

                <div class="admin-permission-card-actions">
                    <button class="btn btn-outline-primary" type="button" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}" data-modal-title="Editar {{ $singularLabel }}">
                        <i class="bi bi-pencil-square"></i>
                        <span>Editar</span>
                    </button>
                    <button class="btn btn-outline-danger" type="button" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">
                        <i class="bi bi-trash3"></i>
                        <span>Excluir</span>
                    </button>
                </div>
            </article>
        @endforeach
    </div>

    <div class="px-1 pt-3">{{ $items->links() }}</div>
@endif
