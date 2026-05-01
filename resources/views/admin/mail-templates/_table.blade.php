<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
        <tr>
            <th>Template</th>
            <th>Vínculo</th>
            <th>Assunto</th>
            <th>Status</th>
            <th class="text-end">Ações</th>
        </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>
                    <div class="fw-semibold text-white">{{ $item->name }}</div>
                    <div class="text-muted small">{{ $item->slug }}</div>
                    @if($item->description)
                        <div class="text-muted small mt-1">{{ $item->description }}</div>
                    @endif
                </td>
                <td>
                    @if($item->system_key)
                        <span class="badge text-bg-warning">{{ \App\Models\MailTemplate::systemKeyOptions()[$item->system_key] ?? $item->system_key }}</span>
                    @else
                        <span class="badge text-bg-secondary">Personalizado</span>
                    @endif
                </td>
                <td>{{ $item->subject ?: 'Sem assunto definido' }}</td>
                <td>
                    <div class="d-flex flex-column gap-1 align-items-start">
                        <span class="badge {{ $item->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $item->is_active ? 'Ativo' : 'Inativo' }}</span>
                        @if($item->is_default)
                            <span class="badge text-bg-dark">Padrão do sistema</span>
                        @endif
                    </div>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Editar</button>
                    @unless($item->is_default)
                        <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button>
                    @endunless
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">Nenhum template de e-mail cadastrado.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
