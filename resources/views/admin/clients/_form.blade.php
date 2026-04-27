@php
    $isEdit = $record->exists;
    $selectedLawyer = old('assigned_lawyer_id', $record->assigned_lawyer_id);
@endphp

<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form>
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3 admin-premium-form">
        <div class="col-md-4">
            <label class="form-label">Tipo de cadastro</label>
            <select name="person_type" class="form-select" required>
                <option value="individual" @selected(old('person_type', $record->person_type ?: 'individual') === 'individual')>Pessoa física</option>
                <option value="company" @selected(old('person_type', $record->person_type) === 'company')>Pessoa jurídica</option>
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label">Nome / razão social</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Nome fantasia / complemento</label>
            <input type="text" name="trade_name" class="form-control" value="{{ old('trade_name', $record->trade_name) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">CPF ou CNPJ</label>
            <input type="text" name="document_number" data-mask="cpf-cnpj" class="form-control" value="{{ old('document_number', $record->document_number) }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $record->email) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Telefone</label>
            <input type="text" name="phone" data-mask="phone" class="form-control" value="{{ old('phone', $record->phone) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">WhatsApp</label>
            <input type="text" name="whatsapp" data-mask="phone" class="form-control" value="{{ old('whatsapp', $record->whatsapp) }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">Telefone alternativo</label>
            <input type="text" name="alternate_phone" data-mask="phone" class="form-control" value="{{ old('alternate_phone', $record->alternate_phone) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Data de referência</label>
            <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $record->birth_date?->format('Y-m-d')) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Profissão / segmento</label>
            <input type="text" name="profession" class="form-control" value="{{ old('profession', $record->profession) }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">CEP</label>
            <input type="text" name="address_zip" data-mask="cep" data-cep-autofill class="form-control" value="{{ old('address_zip', $record->address_zip) }}">
        </div>
        <div class="col-md-7">
            <label class="form-label">Logradouro</label>
            <input type="text" name="address_street" class="form-control" value="{{ old('address_street', $record->address_street) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Número</label>
            <input type="text" name="address_number" class="form-control" value="{{ old('address_number', $record->address_number) }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">Complemento</label>
            <input type="text" name="address_complement" class="form-control" value="{{ old('address_complement', $record->address_complement) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Bairro</label>
            <input type="text" name="address_district" class="form-control" value="{{ old('address_district', $record->address_district) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Cidade</label>
            <input type="text" name="address_city" class="form-control" value="{{ old('address_city', $record->address_city) }}">
        </div>
        <div class="col-md-1">
            <label class="form-label">UF</label>
            <input type="text" name="address_state" class="form-control text-uppercase" maxlength="2" value="{{ old('address_state', $record->address_state) }}">
        </div>

        <div class="col-md-8">
            <label class="form-label">Advogado responsável</label>
            <select name="assigned_lawyer_id" class="form-select" @disabled(! $canChooseLawyer)>
                <option value="">Definir depois</option>
                @foreach($lawyers as $lawyer)
                    <option value="{{ $lawyer->id }}" @selected((string) $selectedLawyer === (string) $lawyer->id)>{{ $lawyer->name }}</option>
                @endforeach
            </select>
            @if(! $canChooseLawyer)
                <input type="hidden" name="assigned_lawyer_id" value="{{ $selectedLawyer ?: auth()->id() }}">
            @endif
        </div>
        <div class="col-md-4 form-check ps-5">
            <input type="checkbox" class="form-check-input" id="client_active" name="is_active" value="1" @checked(old('is_active', $record->is_active ?? true))>
            <label class="form-check-label" for="client_active">Cliente ativo</label>
        </div>

        <div class="col-12">
            <div class="admin-premium-surface p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <div class="admin-card-kicker">Portal do cliente</div>
                        <h3 class="h6 mb-0">Acesso individual ao acompanhamento</h3>
                    </div>
                    <span class="badge {{ old('portal_enabled', $record->portal_enabled) ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ old('portal_enabled', $record->portal_enabled) ? 'Liberado' : 'Desativado' }}
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-md-4 form-check ps-5">
                        <input type="checkbox" class="form-check-input" id="client_portal_enabled" name="portal_enabled" value="1" @checked(old('portal_enabled', $record->portal_enabled))>
                        <label class="form-check-label" for="client_portal_enabled">Permitir acesso ao portal</label>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Código de acesso</label>
                        <div class="input-group">
                            <input type="text" name="portal_access_code" class="form-control" value="{{ old('portal_access_code') }}" placeholder="{{ $record->portal_access_code ? 'Deixe em branco para manter o código atual' : 'Informe ou gere um código de acesso' }}">
                            <button class="btn btn-outline-secondary" type="button" data-generate-client-code>Gerar</button>
                        </div>
                        <div class="form-text">
                            @if($record->portal_access_code)
                                Última alteração: {{ $record->portal_access_code_updated_at?->format('d/m/Y H:i') ?: 'não registrada' }}.
                            @else
                                Defina um código com no mínimo 6 caracteres para o primeiro acesso.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">Notas internas</label>
            <textarea name="notes" class="form-control" data-editor="summernote" data-editor-height="260">{{ old('notes', $record->notes) }}</textarea>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar cliente</button>
    </div>
</form>
