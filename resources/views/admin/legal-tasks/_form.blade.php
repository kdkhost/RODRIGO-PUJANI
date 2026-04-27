@php
    $isEdit = $record->exists;
    $formatDateTime = fn ($value) => $value ? $value->format('Y-m-d\TH:i') : '';
@endphp

<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form>
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3 admin-premium-form">
        <div class="col-md-7">
            <label class="form-label">Título</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $record->title) }}" required>
        </div>
        <div class="col-md-5">
            <label class="form-label">Responsável</label>
            <select name="assigned_user_id" class="form-select">
                <option value="">Definir depois</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected((string) old('assigned_user_id', $record->assigned_user_id) === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-5">
            <label class="form-label">Processo</label>
            <select name="legal_case_id" class="form-select">
                <option value="">Sem processo</option>
                @foreach($cases as $case)
                    <option value="{{ $case->id }}" @selected((string) old('legal_case_id', $record->legal_case_id) === (string) $case->id)>{{ $case->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Cliente</label>
            <select name="client_id" class="form-select">
                <option value="">Sem cliente</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((string) old('client_id', $record->client_id) === (string) $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select name="task_type" class="form-select">
                @foreach($taskTypes as $key => $label)
                    <option value="{{ $key }}" @selected(old('task_type', $record->task_type ?: 'follow_up') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $record->status ?: 'pending') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Prioridade</label>
            <select name="priority" class="form-select">
                @foreach($priorities as $key => $label)
                    <option value="{{ $key }}" @selected(old('priority', $record->priority ?: 'medium') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Lembrete (min)</label>
            <input type="number" min="0" max="10080" name="reminder_minutes" class="form-control" value="{{ old('reminder_minutes', $record->reminder_minutes) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tempo faturável (min)</label>
            <input type="number" min="0" max="1440" name="billable_minutes" class="form-control" value="{{ old('billable_minutes', $record->billable_minutes) }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">Início</label>
            <input type="datetime-local" name="start_at" class="form-control" value="{{ old('start_at', $formatDateTime($record->start_at)) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Prazo</label>
            <input type="datetime-local" name="due_at" class="form-control" value="{{ old('due_at', $formatDateTime($record->due_at)) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Local</label>
            <input type="text" name="location" class="form-control" value="{{ old('location', $record->location) }}">
        </div>

        <div class="col-12">
            <label class="form-label">Descrição operacional</label>
            <textarea name="description" class="form-control" data-editor="summernote" data-editor-height="220">{{ old('description', $record->description) }}</textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Resultado / andamento</label>
            <textarea name="result_notes" class="form-control" data-editor="summernote" data-editor-height="220">{{ old('result_notes', $record->result_notes) }}</textarea>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar tarefa</button>
    </div>
</form>
