@extends('site.portal.layout')

@php
    $pageTitle = 'Meu perfil';
    $avatarUrl = $client->avatar_path ? site_asset_url($client->avatar_path) : null;
    $initials = collect(explode(' ', (string) $client->name))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');
    $canEditRegistration = (bool) $client->portal_profile_update_allowed;
    $editableFields = collect($editableFields ?? []);
    $isEditable = fn (string $field): bool => $canEditRegistration && $editableFields->contains($field);
    $personType = old('person_type', $resolvedPersonType ?? $client->person_type ?: 'individual');
    $isCompany = $personType === 'company';
    $metadata = is_array($client->metadata) ? $client->metadata : [];
@endphp

@section('portal_full_width', true)

@section('content')
    <div class="portal-dashboard-header">
        <div>
            <span>Cadastro do cliente</span>
            <h2>Meu perfil</h2>
            <p>Atualize sua foto e, quando liberado pelo escritório, mantenha seus dados cadastrais e de contato em dia.</p>
        </div>
        <a href="{{ route('portal.dashboard') }}" class="portal-secondary-button">Voltar ao painel</a>
    </div>

    <form action="{{ route('portal.profile.update') }}" method="POST" enctype="multipart/form-data" class="portal-profile-form">
        @csrf
        @method('PUT')

        @unless($canEditRegistration)
            <div class="portal-status portal-status-info">
                A atualização cadastral está bloqueada pelo escritório. Você pode trocar apenas a foto de perfil.
            </div>
        @endunless

        <section class="portal-section">
            <div class="portal-section-heading">
                <h3>Identificação</h3>
            </div>

            <div class="portal-profile-grid">
                <div class="portal-field">
                    <label for="person_type">Tipo de cadastro</label>
                    <select id="person_type" name="person_type" class="portal-input" data-portal-person-type required @disabled(true)>
                        <option value="individual" @selected($personType === 'individual')>Pessoa física</option>
                        <option value="company" @selected($personType === 'company')>Pessoa jurídica</option>
                    </select>
                </div>
                <div class="portal-field portal-field-wide">
                    <label for="name" data-portal-name-label>{{ $isCompany ? 'Razão social' : 'Nome completo' }}</label>
                    <input id="name" type="text" name="name" class="portal-input @error('name') portal-input-error @enderror" value="{{ old('name', $client->name) }}" required placeholder="{{ $isCompany ? 'Razão social' : 'Nome completo' }}" @disabled(! $isEditable('name'))>
                </div>
                <div class="portal-field {{ $isCompany ? '' : 'portal-hidden' }}" data-portal-company-field>
                    <label for="trade_name">Nome fantasia</label>
                    <input id="trade_name" type="text" name="trade_name" class="portal-input" value="{{ old('trade_name', $client->trade_name) }}" placeholder="Nome fantasia da empresa" data-locked-by-portal="{{ $isEditable('trade_name') ? 'false' : 'true' }}" @disabled(! $isEditable('trade_name') || ! $isCompany)>
                </div>
                <div class="portal-field">
                    <label for="document_number" data-portal-document-label>{{ $isCompany ? 'CNPJ' : 'CPF' }}</label>
                    <input id="document_number" type="text" name="document_number" data-mask="{{ $isCompany ? 'cnpj' : 'cpf' }}" data-portal-document-field class="portal-input" value="{{ old('document_number', $client->document_number) }}" placeholder="{{ $isCompany ? '00.000.000/0000-00' : '000.000.000-00' }}" @disabled(! $isEditable('document_number'))>
                </div>
                <div class="portal-field">
                    <label for="birth_date">Data de nascimento</label>
                    <input id="birth_date" type="date" name="birth_date" class="portal-input" value="{{ old('birth_date', $client->birth_date?->format('Y-m-d')) }}" @disabled(! $isEditable('birth_date'))>
                </div>
                <div class="portal-field">
                    <label for="profession">Profissão / segmento</label>
                    <input id="profession" type="text" name="profession" class="portal-input" value="{{ old('profession', $client->profession) }}" placeholder="Profissão ou segmento" @disabled(! $isEditable('profession'))>
                </div>
            </div>
        </section>

        @if($isCompany)
            <section class="portal-section portal-section-spaced">
                <div class="portal-section-heading">
                    <h3>Responsável legal</h3>
                </div>

                <div class="portal-profile-grid">
                    <div class="portal-field">
                        <label for="legal_representative_name">Nome</label>
                        <input id="legal_representative_name" type="text" name="legal_representative_name" class="portal-input" value="{{ old('legal_representative_name', $metadata['legal_representative_name'] ?? '') }}" placeholder="Nome do responsável legal" @disabled(! $isEditable('legal_representative_name'))>
                    </div>
                    <div class="portal-field">
                        <label for="legal_representative_document">CPF</label>
                        <input id="legal_representative_document" type="text" name="legal_representative_document" data-mask="cpf" class="portal-input" value="{{ old('legal_representative_document', $metadata['legal_representative_document'] ?? '') }}" placeholder="000.000.000-00" @disabled(! $isEditable('legal_representative_document'))>
                    </div>
                    <div class="portal-field">
                        <label for="legal_representative_email">E-mail</label>
                        <input id="legal_representative_email" type="email" name="legal_representative_email" class="portal-input" value="{{ old('legal_representative_email', $metadata['legal_representative_email'] ?? '') }}" placeholder="email@dominio.com.br" @disabled(! $isEditable('legal_representative_email'))>
                    </div>
                    <div class="portal-field">
                        <label for="legal_representative_phone">Telefone</label>
                        <input id="legal_representative_phone" type="text" name="legal_representative_phone" data-mask="phone" class="portal-input" value="{{ old('legal_representative_phone', $metadata['legal_representative_phone'] ?? '') }}" placeholder="(00) 00000-0000" @disabled(! $isEditable('legal_representative_phone'))>
                    </div>
                </div>
            </section>
        @endif

        <section class="portal-section portal-section-spaced">
            <div class="portal-section-heading">
                <h3>Contato</h3>
            </div>

            <div class="portal-profile-grid">
                <div class="portal-field">
                    <label for="email">E-mail</label>
                    <input id="email" type="email" name="email" class="portal-input @error('email') portal-input-error @enderror" value="{{ old('email', $client->email) }}" placeholder="seu@email.com.br" @disabled(! $isEditable('email'))>
                </div>
                <div class="portal-field">
                    <label for="phone">Telefone</label>
                    <input id="phone" type="text" name="phone" data-mask="phone" class="portal-input" value="{{ old('phone', $client->phone) }}" placeholder="(00) 0000-0000" @disabled(! $isEditable('phone'))>
                </div>
                <div class="portal-field">
                    <label for="whatsapp">WhatsApp</label>
                    <input id="whatsapp" type="text" name="whatsapp" data-mask="phone" class="portal-input" value="{{ old('whatsapp', $client->whatsapp) }}" placeholder="(00) 00000-0000" @disabled(! $isEditable('whatsapp'))>
                </div>
                <div class="portal-field">
                    <label for="alternate_phone">Telefone alternativo</label>
                    <input id="alternate_phone" type="text" name="alternate_phone" data-mask="phone" class="portal-input" value="{{ old('alternate_phone', $client->alternate_phone) }}" placeholder="(00) 00000-0000" @disabled(! $isEditable('alternate_phone'))>
                </div>
            </div>
        </section>

        <section class="portal-section portal-section-spaced">
            <div class="portal-section-heading">
                <h3>Foto do perfil</h3>
            </div>

            <div class="portal-avatar-editor">
                <div class="portal-avatar-upload">
                    <input
                        id="avatar"
                        type="file"
                        name="avatar"
                        accept="image/png,image/jpeg,image/webp"
                        data-portal-filepond
                        data-portal-avatar-input
                        data-accepted="image/png,image/jpeg,image/webp"
                        data-max-file-size="4MB"
                        data-preview-target="[data-portal-avatar-preview]"
                    >
                </div>
                <div class="portal-avatar-preview" data-portal-avatar-preview>
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $client->name }}">
                    @else
                        <span>{{ $initials ?: 'CL' }}</span>
                    @endif
                </div>
            </div>
            @error('avatar')<div class="portal-error mt-2">{{ $message }}</div>@enderror
        </section>

        <section class="portal-section portal-section-spaced">
            <div class="portal-section-heading">
                <h3>Endereço completo</h3>
            </div>

            <div class="portal-profile-grid">
                <div class="portal-field">
                    <label for="address_zip">CEP</label>
                    <input id="address_zip" type="text" name="address_zip" data-mask="cep" data-cep-autofill class="portal-input" value="{{ old('address_zip', $client->address_zip) }}" placeholder="00000-000" @disabled(! $isEditable('address_zip'))>
                </div>
                <div class="portal-field portal-field-wide">
                    <label for="address_street">Logradouro</label>
                    <input id="address_street" type="text" name="address_street" class="portal-input" value="{{ old('address_street', $client->address_street) }}" placeholder="Rua, avenida, travessa" @disabled(! $isEditable('address_street'))>
                </div>
                <div class="portal-field">
                    <label for="address_number">Número</label>
                    <input id="address_number" type="text" name="address_number" class="portal-input" value="{{ old('address_number', $client->address_number) }}" placeholder="Número" @disabled(! $isEditable('address_number'))>
                </div>
                <div class="portal-field">
                    <label for="address_complement">Complemento</label>
                    <input id="address_complement" type="text" name="address_complement" class="portal-input" value="{{ old('address_complement', $client->address_complement) }}" placeholder="Apartamento, bloco, sala" @disabled(! $isEditable('address_complement'))>
                </div>
                <div class="portal-field">
                    <label for="address_district">Bairro</label>
                    <input id="address_district" type="text" name="address_district" class="portal-input" value="{{ old('address_district', $client->address_district) }}" placeholder="Bairro" @disabled(! $isEditable('address_district'))>
                </div>
                <div class="portal-field">
                    <label for="address_city">Cidade</label>
                    <input id="address_city" type="text" name="address_city" class="portal-input" value="{{ old('address_city', $client->address_city) }}" placeholder="Cidade" @disabled(! $isEditable('address_city'))>
                </div>
                <div class="portal-field">
                    <label for="address_state">UF</label>
                    <input id="address_state" type="text" name="address_state" class="portal-input" maxlength="2" value="{{ old('address_state', $client->address_state) }}" placeholder="UF" @disabled(! $isEditable('address_state'))>
                </div>
            </div>
        </section>

        @if($errors->any())
            <div class="portal-status portal-status-warning portal-section-spaced">
                Revise os campos destacados antes de salvar.
            </div>
        @endif

        <div class="portal-profile-actions">
            <button type="submit" class="portal-button">{{ $canEditRegistration ? 'Salvar alterações' : 'Salvar foto' }}</button>
        </div>
    </form>
@endsection