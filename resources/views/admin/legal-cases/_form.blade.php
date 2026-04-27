@php
    $isEdit = $record->exists;
    $formatDateTime = fn ($value) => $value ? $value->format('Y-m-d\TH:i') : '';
@endphp

<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form>
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3 admin-premium-form">
        <div class="col-md-8">
            <label class="form-label">Título interno</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $record->title) }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Área de atuação</label>
            <input type="text" name="practice_area" class="form-control" value="{{ old('practice_area', $record->practice_area) }}" placeholder="Ex.: Empresarial">
        </div>

        <div class="col-md-5">
            <label class="form-label">Cliente</label>
            <select name="client_id" class="form-select" required>
                <option value="">Selecione</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((string) old('client_id', $record->client_id) === (string) $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Advogado principal</label>
            <select name="primary_lawyer_id" class="form-select" @disabled(! $canChooseLawyer)>
                <option value="">Definir depois</option>
                @foreach($lawyers as $lawyer)
                    <option value="{{ $lawyer->id }}" @selected((string) old('primary_lawyer_id', $record->primary_lawyer_id) === (string) $lawyer->id)>{{ $lawyer->name }}</option>
                @endforeach
            </select>
            @if(! $canChooseLawyer)
                <input type="hidden" name="primary_lawyer_id" value="{{ old('primary_lawyer_id', $record->primary_lawyer_id ?: auth()->id()) }}">
            @endif
        </div>
        <div class="col-md-3">
            <label class="form-label">Supervisor</label>
            <select name="supervising_lawyer_id" class="form-select" @disabled(! $canChooseLawyer)>
                <option value="">Opcional</option>
                @foreach($lawyers as $lawyer)
                    <option value="{{ $lawyer->id }}" @selected((string) old('supervising_lawyer_id', $record->supervising_lawyer_id) === (string) $lawyer->id)>{{ $lawyer->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Número do processo</label>
            <input type="text" name="process_number" data-mask="cnj" class="form-control" value="{{ old('process_number', $record->process_number) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Código interno</label>
            <input type="text" name="internal_code" class="form-control" value="{{ old('internal_code', $record->internal_code) }}">
        </div>
        <div class="col-md-5">
            <label class="form-label">Parte contrária</label>
            <input type="text" name="counterparty" class="form-control" value="{{ old('counterparty', $record->counterparty) }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $record->status ?: 'active') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Fase</label>
            <select name="phase" class="form-select">
                @foreach($phases as $key => $label)
                    <option value="{{ $key }}" @selected(old('phase', $record->phase ?: 'initial') === $key)>{{ $label }}</option>
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
            <label class="form-label">Distribuição</label>
            <input type="date" name="filing_date" class="form-control" value="{{ old('filing_date', $record->filing_date?->format('Y-m-d')) }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">Órgão / vara</label>
            <input type="text" name="court_name" class="form-control" value="{{ old('court_name', $record->court_name) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Seção / divisão</label>
            <input type="text" name="court_division" class="form-control" value="{{ old('court_division', $record->court_division) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Cidade</label>
            <input type="text" name="court_city" class="form-control" value="{{ old('court_city', $record->court_city) }}">
        </div>
        <div class="col-md-1">
            <label class="form-label">UF</label>
            <input type="text" name="court_state" class="form-control text-uppercase" maxlength="2" value="{{ old('court_state', $record->court_state) }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">Próxima audiência</label>
            <input type="datetime-local" name="next_hearing_at" class="form-control" value="{{ old('next_hearing_at', $formatDateTime($record->next_hearing_at)) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Próximo prazo</label>
            <input type="datetime-local" name="next_deadline_at" class="form-control" value="{{ old('next_deadline_at', $formatDateTime($record->next_deadline_at)) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Honorário de êxito (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="success_fee_percent" class="form-control" value="{{ old('success_fee_percent', $record->success_fee_percent) }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Valor da causa</label>
            <input type="text" name="claim_amount" data-mask="currency" class="form-control" value="{{ old('claim_amount', $record->claim_amount) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Valor contratado</label>
            <input type="text" name="contract_value" data-mask="currency" class="form-control" value="{{ old('contract_value', $record->contract_value) }}">
        </div>

        <div class="col-12">
            <label class="form-label">Resumo executivo</label>
            <textarea name="summary" class="form-control" data-editor="summernote" data-editor-height="220">{{ old('summary', $record->summary) }}</textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Estratégia e observações</label>
            <textarea name="strategy_notes" class="form-control" data-editor="summernote" data-editor-height="260">{{ old('strategy_notes', $record->strategy_notes) }}</textarea>
        </div>

        <div class="col-12">
            <div class="admin-premium-surface p-3">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="admin-card-kicker">Portal do cliente</div>
                        <h3 class="h6 mb-2">Resumo e visibilidade para o cliente</h3>
                        <textarea name="portal_summary" class="form-control" data-editor="summernote" data-editor-height="220">{{ old('portal_summary', $record->portal_summary) }}</textarea>
                    </div>
                    <div class="col-lg-4">
                        <div class="admin-card-kicker">DataJud / CNJ</div>
                        <h3 class="h6 mb-2">Monitoramento público</h3>
                        <label class="form-label">Alias do tribunal</label>
                        <input type="text" name="tribunal_alias" class="form-control" list="tribunal-aliases" value="{{ old('tribunal_alias', $record->tribunal_alias) }}" placeholder="Ex.: tjsp, trf3, trt2">
                        <datalist id="tribunal-aliases">
                            @foreach($tribunalSuggestions as $alias => $label)
                                <option value="{{ $alias }}">{{ $label }}</option>
                            @endforeach
                        </datalist>
                        <div class="form-text mb-3">Use o alias oficial do DataJud para este tribunal.</div>

                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="legal_case_portal_visible" name="portal_visible" value="1" @checked(old('portal_visible', $record->portal_visible ?? true))>
                            <label class="form-check-label" for="legal_case_portal_visible">Exibir no portal do cliente</label>
                        </div>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="legal_case_datajud_sync_enabled" name="datajud_sync_enabled" value="1" @checked(old('datajud_sync_enabled', $record->datajud_sync_enabled))>
                            <label class="form-check-label" for="legal_case_datajud_sync_enabled">Habilitar sincronização CNJ</label>
                        </div>
                        @if($record->exists)
                            <div class="small text-muted mt-3">
                                Última sincronização: {{ $record->datajud_last_synced_at?->format('d/m/Y H:i') ?: 'não realizada' }}<br>
                                Última movimentação do tribunal: {{ $record->latest_court_update_at?->format('d/m/Y H:i') ?: 'não informada' }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 form-check ps-5">
            <input type="checkbox" class="form-check-input" id="legal_case_confidential" name="is_confidential" value="1" @checked(old('is_confidential', $record->is_confidential ?? true))>
            <label class="form-check-label" for="legal_case_confidential">Caso confidencial</label>
        </div>
        <div class="col-md-4 form-check ps-5">
            <input type="checkbox" class="form-check-input" id="legal_case_active" name="is_active" value="1" @checked(old('is_active', $record->is_active ?? true))>
            <label class="form-check-label" for="legal_case_active">Caso ativo</label>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar processo</button>
    </div>
</form>
