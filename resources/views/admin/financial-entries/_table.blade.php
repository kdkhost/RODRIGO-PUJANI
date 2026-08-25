@isset($financialSummary)
    <div class="row g-3 mb-4">
        @foreach([
            ['Receber', $financialSummary['receivable'], 'warning'],
            ['Recebido', $financialSummary['received'], 'success'],
            ['Pagar', $financialSummary['payable'], 'danger'],
            ['Despesas pagas', $financialSummary['paid_expenses'], 'info'],
        ] as [$label, $value, $tone])
            <div class="col-6 col-xl-3">
                <div class="admin-stat-card h-100">
                    <span class="badge badge-soft-{{ $tone }} mb-2">{{ $label }}</span>
                    <div class="fs-5 fw-semibold">R$ {{ number_format((float) $value, 2, ',', '.') }}</div>
                </div>
            </div>
        @endforeach
    </div>
@endisset

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Lançamento</th>
                <th>Cliente / processo</th>
                <th>Vencimento</th>
                <th>Status</th>
                <th class="text-end">Valor</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                @php($effectiveStatus = $item->effectiveStatus())
                <tr>
                    <td>
                        <div class="admin-entity-title">{{ $item->description }}</div>
                        <div class="admin-entity-meta">
                            {{ str($item->category)->replace('_', ' ')->headline() }}
                            @if($item->installment_count > 1) · {{ $item->installment_number }}/{{ $item->installment_count }} @endif
                        </div>
                    </td>
                    <td>
                        <div class="admin-entity-title">{{ $item->client?->name }}</div>
                        <div class="admin-entity-meta">{{ $item->legalCase?->title ?: 'Sem processo' }}</div>
                    </td>
                    <td>{{ $item->due_date?->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge badge-soft-{{ match($effectiveStatus) { 'paid' => 'success', 'overdue' => 'danger', 'canceled' => 'secondary', default => 'warning' } }}">
                            {{ match($effectiveStatus) { 'paid' => 'Pago', 'overdue' => 'Vencido', 'canceled' => 'Cancelado', default => 'Pendente' } }}
                        </span>
                    </td>
                    <td class="text-end fw-semibold {{ $item->entry_type === 'income' ? 'text-success' : 'text-danger' }}">
                        {{ $item->entry_type === 'income' ? '+' : '-' }} R$ {{ number_format((float) $item->amount, 2, ',', '.') }}
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" data-modal-url="{{ route($routeBase.'.edit', $item->id) }}">Editar</button>
                            <button class="btn btn-sm btn-outline-danger" data-delete-url="{{ route($routeBase.'.destroy', $item->id) }}" data-table-target="#admin-resource-table">Excluir</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum lançamento financeiro cadastrado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div>{{ $items->links() }}</div>
