@php
    $isEdit = $record->exists;
    $formatDate = fn ($value) => $value ? $value->format('Y-m-d\TH:i') : '';
    $extendedProps = old('extended_props_text', $record->extended_props ? json_encode($record->extended_props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '');
    $statusLabels = [
        'scheduled' => 'Agendado',
        'confirmed' => 'Confirmado',
        'done' => 'Concluído',
        'canceled' => 'Cancelado',
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

<form action="{{ $isEdit ? route('admin.calendar.update', $record) : route('admin.calendar.store') }}" method="POST" data-ajax-form>
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3 admin-premium-form">
        <div class="col-md-8">
            <label class="form-label">Título</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $record->title) }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Categoria</label>
            <input type="text" name="category" class="form-control" value="{{ old('category', $record->category ?: 'Atendimento') }}" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $record->status ?: 'scheduled') === $status)>{{ $statusLabels[$status] ?? ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Visibilidade</label>
            <select name="visibility" class="form-select" required>
                @foreach ($visibilities as $visibility)
                    <option value="{{ $visibility }}" @selected(old('visibility', $record->visibility ?: 'team') === $visibility)>{{ $visibilityLabels[$visibility] ?? ucfirst($visibility) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Responsável</label>
            <select name="owner_id" class="form-select" @disabled(! $canChooseOwner)>
                <option value="">Sem responsável</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((string) old('owner_id', $record->owner_id) === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            @if(! $canChooseOwner)
                <input type="hidden" name="owner_id" value="{{ old('owner_id', $record->owner_id ?: auth()->id()) }}">
            @endif
        </div>

        <div class="col-md-6">
            <label class="form-label">Início</label>
            <input type="datetime-local" name="start_at" class="form-control" value="{{ old('start_at', $formatDate($record->start_at)) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Fim</label>
            <input type="datetime-local" name="end_at" class="form-control" value="{{ old('end_at', $formatDate($record->end_at)) }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">Cor do evento</label>
            <input type="color" name="color" class="form-control form-control-color w-100" value="{{ old('color', $record->color ?: '#c49a3c') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Cor do texto</label>
            <input type="color" name="text_color" class="form-control form-control-color w-100" value="{{ old('text_color', $record->text_color ?: '#111318') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Exibição na agenda</label>
            <select name="display" class="form-select">
                @foreach ($displays as $display)
                    <option value="{{ $display }}" @selected(old('display', $record->display ?: 'auto') === $display)>{{ $displayLabels[$display] ?? $display }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Local</label>
            <input type="text" name="location" class="form-control" value="{{ old('location', $record->location) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Link relacionado</label>
            <input type="url" name="url" class="form-control" value="{{ old('url', $record->url) }}">
        </div>

        <div class="col-md-4 form-check ps-5">
            <input type="checkbox" class="form-check-input" id="calendar_all_day" name="all_day" value="1" @checked(old('all_day', $record->all_day))>
            <label class="form-check-label" for="calendar_all_day">Dia inteiro</label>
        </div>
        <div class="col-md-4 form-check ps-5">
            <input type="checkbox" class="form-check-input" id="calendar_editable" name="editable" value="1" @checked(old('editable', $record->editable ?? true))>
            <label class="form-check-label" for="calendar_editable">Permitir arrastar/redimensionar</label>
        </div>
        <div class="col-md-4 form-check ps-5">
            <input type="checkbox" class="form-check-input" id="calendar_overlap" name="overlap" value="1" @checked(old('overlap', $record->overlap ?? true))>
            <label class="form-check-label" for="calendar_overlap">Permitir sobreposição</label>
        </div>

        <div class="col-12">
            <label class="form-label">Descrição</label>
            <textarea name="description" class="form-control" data-editor="summernote">{{ old('description', $record->description) }}</textarea>
        </div>

        <div class="col-12">
            <label class="form-label">Propriedades extras em JSON</label>
            <textarea name="extended_props_text" class="form-control admin-code-editor" rows="5" placeholder='{"cliente":"Nome", "origem":"WhatsApp"}'>{{ $extendedProps }}</textarea>
        </div>
    </div>

    <div class="d-flex justify-content-between gap-2 mt-4">
        <div>
            @if($isEdit)
                <div class="d-flex gap-2">
                    @if($record->url)
                        <a href="{{ $record->url }}" class="btn btn-outline-primary" target="_blank" rel="noopener">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Abrir link
                        </a>
                    @endif
                    <button type="button" class="btn btn-outline-danger" data-delete-url="{{ route('admin.calendar.destroy', $record) }}" data-calendar-target="#admin-calendar" data-table-target="#admin-calendar-events-table" data-confirm-text="O evento será removido permanentemente da agenda.">
                        <i class="bi bi-trash me-1"></i>Excluir
                    </button>
                </div>
            @endif
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar evento</button>
        </div>
    </div>
</form>
