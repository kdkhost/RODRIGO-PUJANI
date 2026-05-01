@php
    $isEdit = $record->exists;
@endphp
<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form>
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3 admin-premium-form">
        <div class="col-md-6">
            <label class="form-label">Nome do template</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}" placeholder="Ex.: Confirmação de cadastro">
        </div>
        <div class="col-md-3">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control" value="{{ old('slug', $record->slug) }}" placeholder="confirmacao-cadastro">
        </div>
        <div class="col-md-3">
            <label class="form-label">Vincular ao sistema</label>
            <select name="system_key" class="form-select">
                <option value="">Template personalizado</option>
                @foreach($systemKeyOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('system_key', $record->system_key) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-9">
            <label class="form-label">Descrição</label>
            <input type="text" name="description" class="form-control" value="{{ old('description', $record->description) }}" placeholder="Uso interno para a equipe">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="d-flex flex-wrap gap-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="show_logo" id="mail_show_logo" value="1" @checked(old('show_logo', $record->show_logo ?? true))>
                    <label class="form-check-label" for="mail_show_logo">Exibir logo</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_active" id="mail_is_active" value="1" @checked(old('is_active', $record->is_active ?? true))>
                    <label class="form-check-label" for="mail_is_active">Ativo</label>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="admin-premium-surface p-3">
                <div class="admin-card-kicker mb-2">Variáveis clicáveis</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($tokenOptions as $token => $label)
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-mail-token="{{ $token }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label">Assunto</label>
            <input type="text" name="subject" class="form-control" value="{{ old('subject', $record->subject) }}" placeholder="Assunto do e-mail">
        </div>
        <div class="col-md-3">
            <label class="form-label">Modelo visual</label>
            <select name="layout" class="form-select">
                @foreach($layoutOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('layout', $record->layout ?? 'premium') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Fonte</label>
            <select name="font_family" class="form-select">
                @foreach($fontOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('font_family', $record->font_family ?? 'Segoe UI, Arial, sans-serif') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Cabeçalho</label>
            <textarea name="header_html" class="form-control" rows="4" data-editor="summernote" data-editor-height="220">{{ old('header_html', $record->header_html) }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Rodapé</label>
            <textarea name="footer_html" class="form-control" rows="4" data-editor="summernote" data-editor-height="220">{{ old('footer_html', $record->footer_html) }}</textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Corpo do e-mail</label>
            <textarea name="body_html" class="form-control" rows="8" data-editor="summernote" data-editor-height="300">{{ old('body_html', $record->body_html) }}</textarea>
        </div>

        @php
            $colorDefaults = [
                'background_color' => '#0F172A',
                'body_background_color' => '#F4F6FB',
                'card_background_color' => '#FFFFFF',
                'border_color' => '#E5E7EF',
                'heading_color' => '#0F172A',
                'text_color' => '#334155',
                'muted_color' => '#64748B',
                'button_background_color' => '#C49A3C',
                'button_text_color' => '#10131A',
            ];
        @endphp
        @foreach($colorDefaults as $field => $default)
            <div class="col-md-4">
                <label class="form-label">{{ match($field) {
                    'background_color' => 'Topo',
                    'body_background_color' => 'Fundo externo',
                    'card_background_color' => 'Card',
                    'border_color' => 'Borda',
                    'heading_color' => 'Títulos',
                    'text_color' => 'Texto',
                    'muted_color' => 'Texto auxiliar',
                    'button_background_color' => 'Botão',
                    'button_text_color' => 'Texto do botão',
                } }}</label>
                <div class="input-group">
                    <input
                        type="color"
                        class="form-control form-control-color"
                        value="{{ old($field, $record->{$field} ?? $default) }}"
                        oninput="this.nextElementSibling.value=this.value.toUpperCase()"
                    >
                    <input type="text" name="{{ $field }}" class="form-control text-uppercase" value="{{ old($field, $record->{$field} ?? $default) }}">
                </div>
            </div>
        @endforeach

        <div class="col-12">
            <label class="form-label">CSS adicional</label>
            <textarea name="custom_css" class="form-control" rows="5" placeholder=".system-mail-card { border-radius: 24px; }">{{ old('custom_css', $record->custom_css) }}</textarea>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
</form>
