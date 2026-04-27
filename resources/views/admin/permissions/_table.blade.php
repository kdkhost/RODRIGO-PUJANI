<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Permissao</th>
                <th>Modulo</th>
                <th>Guard</th>
                <th class="text-end">Acoes</th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            @php
                [$module, $action] = array_pad(explode('.', $item->name, 2), 2, 'manage');
                $friendlyAction = \Illuminate\Support\Str::of($action)->replace(['-', '.'], ' ')->title();
            @endphp
            <tr>
                <td>
                    <div class="admin-permission-name">{{ $item->name }}</div>
                    <div class="admin-permission-meta">{{ $friendlyAction }}</div>
                </td>
                <td><span class="admin-permission-badge">{{ $module }}</span></td>
                <td><span class="badge badge-soft-info">{{ $item->guard_name }}</span></td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Acoes">
                        <button class="btn btn-outline-primary" type="button" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}" data-modal-title="Editar {{ $singularLabel }}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-outline-danger" type="button" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center py-4 text-muted">Nenhuma permissao encontrada.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="px-3 py-2">{{ $items->links() }}</div>
