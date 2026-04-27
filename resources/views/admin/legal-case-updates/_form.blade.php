@php
    $isEdit = $record->exists;
    $formatDateTime = fn ($value) => $value ? $value->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i');
@endphp

<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form>
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3 admin-premium-form">
        <div class="col-md-7">
            <label class="form-label">Processo</label>
            <select name="legal_case_id" class="form-select" required>
                <option value="">Selecione</option>
                @foreach($cases as $case)
                    <option value="{{ $case->id }}" @selected((string) old('legal_case_id', $record->legal_case_id) === (string) $case->id)>{{ $case->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label">Data e hora</label>
            <input type="datetime-local" name="occurred_at" class="form-control" value="{{ old('occurred_at', $formatDateTime($record->occurred_at)) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Título do andamento</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $record->title) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Origem</label>
            <select name="source" class="form-select">
                @foreach($sourceLabels as $key => $label)
                    <option value="{{ $key }}" @selected(old('source', $record->source ?: 'manual') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Categoria</label>
            <select name="update_type" class="form-select">
                @foreach($typeLabels as $key => $label)
                    <option value="{{ $key }}" @selected(old('update_type', $record->update_type ?: 'procedural') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Descrição</label>
            <textarea name="body" class="form-control" data-editor="summernote" data-editor-height="240">{{ old('body', $record->body) }}</textarea>
        </div>

        <div class="col-md-5 form-check ps-5">
            <input type="checkbox" class="form-check-input" id="legal_case_update_visible" name="is_visible_to_client" value="1" @checked(old('is_visible_to_client', $record->is_visible_to_client ?? true))>
            <label class="form-check-label" for="legal_case_update_visible">Exibir este andamento ao cliente</label>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar andamento</button>
    </div>
</form>
