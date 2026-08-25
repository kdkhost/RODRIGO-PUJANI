@php
    $isEdit = $record->exists;
    $formatDateTime = fn ($value) => $value ? $value->format('Y-m-d\TH:i') : '';
@endphp

<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form>
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3 admin-premium-form">
        <div class="col-md-4">
            <label class="form-label">Tipo</label>
            <select name="entry_type" class="form-select" required>
                @foreach($entryTypes as $key => $label)
                    <option value="{{ $key }}" @selected(old('entry_type', $record->entry_type ?: 'income') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Categoria</label>
            <select name="category" class="form-select" required>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(old('category', $record->category ?: 'fees') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $record->status ?: 'pending') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Cliente</label>
            <select name="client_id" class="form-select" required>
                <option value="">Selecione</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((string) old('client_id', $record->client_id) === (string) $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Processo</label>
            <select name="legal_case_id" class="form-select">
                <option value="">Sem processo</option>
                @foreach($cases as $case)
                    <option value="{{ $case->id }}" data-client-id="{{ $case->client_id }}" @selected((string) old('legal_case_id', $record->legal_case_id) === (string) $case->id)>{{ $case->title }}</option>
                @endforeach
            </select>
            <div class="form-text">O processo deve pertencer ao cliente selecionado.</div>
        </div>

        <div class="col-md-8">
            <label class="form-label">Descrição</label>
            <input type="text" name="description" class="form-control" maxlength="255" value="{{ old('description', $record->description) }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Referência</label>
            <input type="text" name="reference" class="form-control" maxlength="120" value="{{ old('reference', $record->reference) }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">{{ $isEdit ? 'Valor' : 'Valor total' }}</label>
            <input type="text" name="amount" class="form-control" data-mask="money" inputmode="decimal" value="{{ old('amount', $record->amount) }}" required>
        </div>
        @unless($isEdit)
            <div class="col-md-3">
                <label class="form-label">Parcelas</label>
                <input type="number" name="installment_count" class="form-control" min="1" max="120" value="{{ old('installment_count', 1) }}" required>
            </div>
        @endunless
        <div class="col-md-3">
            <label class="form-label">Vencimento</label>
            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $record->due_date?->format('Y-m-d')) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Pago em</label>
            <input type="datetime-local" name="paid_at" class="form-control" value="{{ old('paid_at', $formatDateTime($record->paid_at)) }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Responsável</label>
            <select name="responsible_user_id" class="form-select">
                <option value="">Não definido</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected((string) old('responsible_user_id', $record->responsible_user_id) === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Forma de pagamento</label>
            <input type="text" name="payment_method" class="form-control" maxlength="40" value="{{ old('payment_method', $record->payment_method) }}" placeholder="PIX, boleto, transferência...">
        </div>
        <div class="col-12">
            <label class="form-label">Observações</label>
            <textarea name="notes" class="form-control" rows="4" maxlength="10000">{{ old('notes', $record->notes) }}</textarea>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar lançamento</button>
    </div>
</form>
