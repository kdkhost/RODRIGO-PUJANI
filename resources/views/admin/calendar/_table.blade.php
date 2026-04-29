@php
    $statusLabels = [
        'scheduled' => 'Agendado',
        'confirmed' => 'Confirmado',
        'done' => 'Concluído',
        'canceled' => 'Cancelado',
    ];
    $statusBadges = [
        'scheduled' => 'badge-soft-warning',
        'confirmed' => 'badge-soft-success',
        'done' => 'badge-soft-info',
        'canceled' => 'badge-soft-danger',
    ];
    $visibilityLabels = [
        'private' => 'Privado',
        'team' => 'Equipe',
        'public' => 'Público',
    ];
    $displayLabels = [
        'auto' => 'Evento normal',
        'background' => 'Marcação de fundo',
        'inverse-background' => 'Bloqueio invertido',
    ];
@endphp

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Evento</th>
                <th>Quando</th>
                <th>Responsável</th>
                <th>Formato</th>
                <th>Status</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>
                        <div class="admin-entity-title">{{ $item->title }}</div>
                        <div class="admin-entity-meta d-flex flex-wrap gap-2 mt-2">
                            <span>{{ $item->category }}</span>
                            @if($item->location)
                                <span>{{ $item->location }}</span>
                            @endif
                            <span>{{ $visibilityLabels[$item->visibility ?: 'team'] ?? ucfirst($item->visibility ?: 'team') }}</span>
                        </div>
                    </td>
                    <td>
                        @if($item->all_day)
                            <div class="admin-entity-title">
                                {{ $item->start_at?->format('d/m/Y') }}
                                @if($item->end_at && ! $item->start_at?->isSameDay($item->end_at->copy()->subSecond()))
                                    até {{ $item->end_at->copy()->subDay()->format('d/m/Y') }}
                                @endif
                            </div>
                            <div class="admin-entity-meta">Dia inteiro</div>
                        @else
                            <div class="admin-entity-title">{{ $item->start_at?->format('d/m/Y H:i') }}</div>
                            <div class="admin-entity-meta">
                                @if($item->end_at)
                                    até {{ $item->end_at->format('d/m/Y H:i') }}
                                @else
                                    Sem horário final
                                @endif
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="admin-entity-title">{{ $item->owner?->name ?: 'Sem responsável' }}</div>
                        <div class="admin-entity-meta">Criado por {{ $item->creator?->name ?: 'sistema' }}</div>
                    </td>
                    <td>
                        <span class="badge {{ ($item->display ?: 'auto') === 'auto' ? 'badge-soft-secondary' : 'badge-soft-warning' }}">
                            {{ $displayLabels[$item->display ?: 'auto'] ?? ucfirst($item->display ?: 'auto') }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $statusBadges[$item->status ?: 'scheduled'] ?? 'badge-soft-secondary' }}">
                            {{ $statusLabels[$item->status ?: 'scheduled'] ?? ucfirst($item->status ?: 'scheduled') }}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" type="button" data-modal-url="{{ route('admin.calendar.edit', $item) }}">Editar</button>
                            <button class="btn btn-sm btn-outline-danger" type="button" data-delete-url="{{ route('admin.calendar.destroy', $item) }}" data-table-target="#admin-calendar-events-table" data-calendar-target="#admin-calendar">Excluir</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Nenhum evento encontrado para os filtros atuais.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
