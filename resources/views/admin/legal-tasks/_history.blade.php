<div class="admin-premium-form">
    <div class="mb-3">
        <div class="admin-entity-title">{{ $record->title }}</div>
        <div class="admin-entity-meta">Cada mudança abaixo preserva o estado e a origem da operação.</div>
    </div>

    <div class="list-group list-group-flush">
        @forelse($history as $entry)
            <div class="list-group-item px-0">
                <div class="d-flex justify-content-between gap-3">
                    <div>
                        <strong>{{ str($entry->action)->replace('_', ' ')->headline() }}</strong>
                        <span class="badge badge-soft-secondary ms-2">{{ $entry->source }}</span>
                        <div class="text-muted small mt-1">{{ $entry->user?->name ?: 'Sistema' }}</div>
                    </div>
                    <time class="text-muted small" datetime="{{ $entry->created_at?->toIso8601String() }}">{{ $entry->created_at?->format('d/m/Y H:i:s') }}</time>
                </div>
                @if(is_array($entry->changes) && $entry->changes !== [])
                    <div class="table-responsive mt-3">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Campo</th><th>Anterior</th><th>Novo</th></tr></thead>
                            <tbody>
                                @foreach($entry->changes as $field => $change)
                                    <tr>
                                        <td>{{ str($field)->replace('_', ' ')->headline() }}</td>
                                        <td>{{ is_scalar(data_get($change, 'from')) ? data_get($change, 'from') : json_encode(data_get($change, 'from'), JSON_UNESCAPED_UNICODE) }}</td>
                                        <td>{{ is_scalar(data_get($change, 'to')) ? data_get($change, 'to') : json_encode(data_get($change, 'to'), JSON_UNESCAPED_UNICODE) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center text-muted py-4">Nenhuma mudança registrada.</div>
        @endforelse
    </div>
    <div class="mt-3">{{ $history->links() }}</div>
</div>
