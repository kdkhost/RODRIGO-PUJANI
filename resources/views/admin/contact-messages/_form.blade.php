@php
    $isEdit = $record->exists;
    $statusLabels = [
        'new' => 'Novo',
        'in_progress' => 'Em andamento',
        'answered' => 'Respondido',
        'archived' => 'Arquivado',
    ];
@endphp

<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form>
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nome</label><input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}"></div>
        <div class="col-md-6"><label class="form-label">Área</label><input type="text" name="area_interest" class="form-control" value="{{ old('area_interest', $record->area_interest) }}"></div>
        <div class="col-md-6"><label class="form-label">E-mail</label><input type="email" name="email" class="form-control" value="{{ old('email', $record->email) }}"></div>
        <div class="col-md-6"><label class="form-label">Telefone</label><input type="text" name="phone" data-mask="phone" class="form-control" value="{{ old('phone', $record->phone) }}"></div>
        <div class="col-md-6"><label class="form-label">Assunto</label><input type="text" name="subject" class="form-control" value="{{ old('subject', $record->subject) }}"></div>
        <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(['new', 'in_progress', 'answered', 'archived'] as $status)<option value="{{ $status }}" @selected(old('status', $record->status ?: 'new') === $status)>{{ $statusLabels[$status] ?? $status }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">Mensagem</label><textarea name="message" class="form-control" rows="4">{{ old('message', $record->message) }}</textarea></div>
        <div class="col-12"><label class="form-label">Observações internas</label><textarea name="notes" class="form-control" rows="3" data-editor="summernote" data-editor-height="220">{{ old('notes', $record->notes) }}</textarea></div>
        <div class="col-md-4"><label class="form-label">Contatado em</label><input type="datetime-local" name="contacted_at" class="form-control" value="{{ old('contacted_at', optional($record->contacted_at)->format('Y-m-d\TH:i')) }}"></div>
        <div class="col-md-4 form-check mt-5"><input type="checkbox" class="form-check-input" id="consent_contact" name="consent" value="1" @checked(old('consent', $record->consent))><label class="form-check-label" for="consent_contact">Consentimento LGPD</label></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form>
