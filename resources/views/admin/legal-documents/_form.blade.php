@php
    $isEdit = $record->exists;
@endphp

<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3 admin-premium-form">
        <div class="col-md-7">
            <label class="form-label">Título</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $record->title) }}" required>
        </div>
        <div class="col-md-5">
            <label class="form-label">Categoria</label>
            <select name="category" class="form-select">
                <option value="">Selecione</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(old('category', $record->category) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Processo</label>
            <select name="legal_case_id" class="form-select">
                <option value="">Sem processo</option>
                @foreach($cases as $case)
                    <option value="{{ $case->id }}" @selected((string) old('legal_case_id', $record->legal_case_id) === (string) $case->id)>{{ $case->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Cliente</label>
            <select name="client_id" class="form-select">
                <option value="">Sem cliente</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((string) old('client_id', $record->client_id) === (string) $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Arquivo</label>
            <input type="file" name="file" class="form-control" data-filepond data-accepted="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/png,image/jpeg,image/webp">
            @if($record->path)
                <div class="small text-muted mt-2">
                    Atual: <a href="{{ site_asset_url($record->path) }}" target="_blank" rel="noopener">{{ $record->original_name ?: $record->file_name }}</a>
                </div>
            @endif
        </div>

        <div class="col-12">
            <label class="form-label">Observações</label>
            <textarea name="notes" class="form-control" data-editor="summernote" data-editor-height="220">{{ old('notes', $record->notes) }}</textarea>
        </div>

        <div class="col-md-4 form-check ps-5">
            <input type="checkbox" class="form-check-input" id="legal_document_sensitive" name="is_sensitive" value="1" @checked(old('is_sensitive', $record->is_sensitive ?? true))>
            <label class="form-check-label" for="legal_document_sensitive">Documento sensível</label>
        </div>
        <div class="col-md-4 form-check ps-5">
            <input type="checkbox" class="form-check-input" id="legal_document_shared" name="shared_with_client" value="1" @checked(old('shared_with_client', $record->shared_with_client))>
            <label class="form-check-label" for="legal_document_shared">Compartilhar com cliente</label>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar documento</button>
    </div>
</form>
