@php
    $isEdit = $record->exists;
    $selectedRole = old('role_name', $record->roles->pluck('name')->first());
    $roleLocked = $record->exists && $record->isSuperAdmin();
    $avatarPath = (string) ($record->avatar_path ?? '');
    $avatarUrl = $avatarPath !== '' ? site_asset_url($avatarPath) : null;
@endphp
<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-4 admin-premium-form">
        <div class="col-12">
            <div class="admin-premium-surface p-3 p-lg-4">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="admin-card-kicker">Identificação</div>
                    </div>
                    <div class="col-md-6"><label class="form-label">Nome</label><input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}" placeholder="Nome completo"></div>
                    <div class="col-md-6"><label class="form-label">E-mail</label><input type="email" name="email" class="form-control" value="{{ old('email', $record->email) }}" placeholder="email@dominio.com.br"></div>
                    <div class="col-md-4"><label class="form-label">Telefone</label><input type="text" name="phone" data-mask="phone" class="form-control" value="{{ old('phone', $record->phone) }}" placeholder="(11) 3000-0000"></div>
                    <div class="col-md-4"><label class="form-label">WhatsApp</label><input type="text" name="whatsapp" data-mask="phone" class="form-control" value="{{ old('whatsapp', $record->whatsapp) }}" placeholder="(11) 90000-0000"></div>
                    <div class="col-md-4"><label class="form-label">Telefone alternativo</label><input type="text" name="alternate_phone" data-mask="phone" class="form-control" value="{{ old('alternate_phone', $record->alternate_phone) }}" placeholder="(11) 3000-0000"></div>
                    <div class="col-md-4"><label class="form-label">CPF ou CNPJ</label><input type="text" name="document_number" data-mask="cpf-cnpj" class="form-control" value="{{ old('document_number', $record->document_number) }}" placeholder="000.000.000-00"></div>
                    <div class="col-md-4"><label class="form-label">Data de nascimento</label><input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $record->birth_date?->format('Y-m-d')) }}"></div>
                    <div class="col-md-4">
                        <label class="form-label">Fuso horário</label>
                        <select name="timezone" class="form-select">
                            @php($selectedTimezone = old('timezone', $record->timezone ?? 'America/Sao_Paulo'))
                            @foreach($timezones as $timezone)
                                <option value="{{ $timezone }}" @selected($selectedTimezone === $timezone)>{{ $timezone }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 admin-upload-compact">
                        <div class="row g-3 align-items-start">
                            <div class="col-lg-6">
                                <label class="form-label">Avatar</label>
                                <input
                                    type="file"
                                    name="avatar"
                                    class="form-control"
                                    data-filepond
                                    data-accepted="image/png,image/jpeg,image/webp"
                                    data-current-url="{{ $avatarUrl ?: '' }}"
                                    data-current-name="{{ $avatarPath !== '' ? basename($avatarPath) : '' }}"
                                >
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">Preview atual</label>
                                <div class="admin-upload-preview-panel h-100">
                                    @if($avatarUrl)
                                        <div class="admin-upload-preview-item">
                                            <div class="admin-upload-preview-media">
                                                <img src="{{ $avatarUrl }}" alt="{{ $record->name }}">
                                            </div>
                                            <div class="admin-upload-preview-info">
                                                <div class="admin-upload-preview-title">
                                                    <strong>{{ $record->name ?: 'Usuário' }}</strong>
                                                    <span>Avatar</span>
                                                </div>
                                                <div class="admin-upload-preview-meta">
                                                    <span class="admin-upload-extension">{{ strtoupper(pathinfo($avatarPath, PATHINFO_EXTENSION)) ?: 'IMG' }}</span>
                                                    <span>Imagem atual do cadastro</span>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="admin-upload-preview-empty">
                                            <i class="bi bi-person-bounding-box"></i>
                                            <div>
                                                <strong>Nenhuma foto cadastrada</strong>
                                                <span>Envie uma imagem para exibir no perfil e nas listagens.</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="admin-premium-surface p-3 p-lg-4">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="admin-card-kicker">Endereço completo</div>
                    </div>
                    <div class="col-md-3"><label class="form-label">CEP</label><input type="text" name="address_zip" data-mask="cep" data-cep-autofill class="form-control" value="{{ old('address_zip', $record->address_zip) }}" placeholder="00000-000"></div>
                    <div class="col-md-7"><label class="form-label">Logradouro</label><input type="text" name="address_street" class="form-control" value="{{ old('address_street', $record->address_street) }}" placeholder="Rua, avenida ou alameda"></div>
                    <div class="col-md-2"><label class="form-label">Número</label><input type="text" name="address_number" class="form-control" value="{{ old('address_number', $record->address_number) }}" placeholder="100"></div>
                    <div class="col-md-4"><label class="form-label">Complemento</label><input type="text" name="address_complement" class="form-control" value="{{ old('address_complement', $record->address_complement) }}" placeholder="Sala, bloco ou referência"></div>
                    <div class="col-md-4"><label class="form-label">Bairro</label><input type="text" name="address_district" class="form-control" value="{{ old('address_district', $record->address_district) }}" placeholder="Bairro"></div>
                    <div class="col-md-3"><label class="form-label">Cidade</label><input type="text" name="address_city" class="form-control" value="{{ old('address_city', $record->address_city) }}" placeholder="Cidade"></div>
                    <div class="col-md-1"><label class="form-label">UF</label><input type="text" name="address_state" class="form-control text-uppercase" value="{{ old('address_state', $record->address_state) }}" maxlength="2" placeholder="SP"></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="admin-premium-surface p-3 p-lg-4">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="admin-card-kicker">Acesso e permissões</div>
                    </div>
                    <div class="col-md-6"><label class="form-label">Senha</label><input type="password" name="password" class="form-control" placeholder="{{ $isEdit ? 'Preencha somente se for alterar' : 'Senha inicial' }}"></div>
                    <div class="col-md-6"><label class="form-label">Confirmar senha</label><input type="password" name="password_confirmation" class="form-control" placeholder="Repita a senha"></div>
                    <div class="col-12">
                        <label class="form-label">Função</label>
                        <select name="role_name" class="form-select" @disabled($roleLocked)>
                            <option value="">Selecione uma função</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @if($roleLocked)
                            <input type="hidden" name="role_name" value="Super Admin">
                            <div class="form-text">Conta protegida. A função Super Admin não pode ser removida.</div>
                        @endif
                    </div>
                    <div class="col-12 form-check">
                        <input type="checkbox" class="form-check-input" id="user_active" name="is_active" value="1" @checked(old('is_active', $record->is_active ?? true))>
                        <label class="form-check-label" for="user_active">Usuário ativo</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
</form>
