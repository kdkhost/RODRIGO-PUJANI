@extends('site.portal.layout')

@php
    $pageTitle = 'Meu perfil';
    $avatarUrl = $client->avatar_path ? site_asset_url($client->avatar_path) : null;
    $initials = collect(explode(' ', (string) $client->name))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');
@endphp

@section('portal_full_width', true)

@section('content')
    <div class="portal-dashboard-header">
        <div>
            <span>Cadastro do cliente</span>
            <h2>Meu perfil</h2>
            <p>Atualize seus dados de contato, endereço e foto para manter a comunicação com o escritório sempre correta.</p>
        </div>
        <a href="{{ route('portal.dashboard') }}" class="portal-secondary-button">Voltar ao painel</a>
    </div>

    <form action="{{ route('portal.profile.update') }}" method="POST" enctype="multipart/form-data" class="portal-profile-form">
        @csrf
        @method('PUT')

        <section class="portal-section">
            <div class="portal-section-heading">
                <h3>Identificação</h3>
            </div>

            <div class="portal-profile-grid">
                <div class="portal-field">
                    <label for="person_type">Tipo de cadastro</label>
                    <select id="person_type" name="person_type" class="portal-input" required>
                        <option value="individual" @selected(old('person_type', $client->person_type ?: 'individual') === 'individual')>Pessoa física</option>
                        <option value="company" @selected(old('person_type', $client->person_type) === 'company')>Pessoa jurídica</option>
                    </select>
                </div>
                <div class="portal-field portal-field-wide">
                    <label for="name">Nome / razão social</label>
                    <input id="name" type="text" name="name" class="portal-input @error('name') portal-input-error @enderror" value="{{ old('name', $client->name) }}" required placeholder="Nome completo ou razão social">
                </div>
                <div class="portal-field">
                    <label for="trade_name">Nome fantasia / complemento</label>
                    <input id="trade_name" type="text" name="trade_name" class="portal-input" value="{{ old('trade_name', $client->trade_name) }}" placeholder="Nome social, fantasia ou complemento">
                </div>
                <div class="portal-field">
                    <label for="document_number">CPF ou CNPJ</label>
                    <input id="document_number" type="text" name="document_number" data-mask="cpf-cnpj" class="portal-input" value="{{ old('document_number', $client->document_number) }}" placeholder="CPF ou CNPJ">
                </div>
                <div class="portal-field">
                    <label for="birth_date">Data de nascimento</label>
                    <input id="birth_date" type="date" name="birth_date" class="portal-input" value="{{ old('birth_date', $client->birth_date?->format('Y-m-d')) }}">
                </div>
                <div class="portal-field">
                    <label for="profession">Profissão / segmento</label>
                    <input id="profession" type="text" name="profession" class="portal-input" value="{{ old('profession', $client->profession) }}" placeholder="Profissão ou segmento">
                </div>
            </div>
        </section>

        <section class="portal-section portal-section-spaced">
            <div class="portal-section-heading">
                <h3>Contato</h3>
            </div>

            <div class="portal-profile-grid">
                <div class="portal-field">
                    <label for="email">E-mail</label>
                    <input id="email" type="email" name="email" class="portal-input @error('email') portal-input-error @enderror" value="{{ old('email', $client->email) }}" placeholder="seu@email.com.br">
                </div>
                <div class="portal-field">
                    <label for="phone">Telefone</label>
                    <input id="phone" type="text" name="phone" data-mask="phone" class="portal-input" value="{{ old('phone', $client->phone) }}" placeholder="(00) 0000-0000">
                </div>
                <div class="portal-field">
                    <label for="whatsapp">WhatsApp</label>
                    <input id="whatsapp" type="text" name="whatsapp" data-mask="phone" class="portal-input" value="{{ old('whatsapp', $client->whatsapp) }}" placeholder="(00) 00000-0000">
                </div>
                <div class="portal-field">
                    <label for="alternate_phone">Telefone alternativo</label>
                    <input id="alternate_phone" type="text" name="alternate_phone" data-mask="phone" class="portal-input" value="{{ old('alternate_phone', $client->alternate_phone) }}" placeholder="(00) 00000-0000">
                </div>
            </div>
        </section>

        <section class="portal-section portal-section-spaced">
            <div class="portal-section-heading">
                <h3>Foto do perfil</h3>
            </div>

            <div class="portal-avatar-editor">
                <label class="portal-avatar-upload" for="avatar">
                    <input id="avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp" data-portal-avatar-input>
                    <span>Arraste uma imagem ou clique para selecionar</span>
                    <small>PNG, JPG ou WEBP até 4 MB</small>
                </label>
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
                    <input id="address_zip" type="text" name="address_zip" data-mask="cep" data-cep-autofill class="portal-input" value="{{ old('address_zip', $client->address_zip) }}" placeholder="00000-000">
                </div>
                <div class="portal-field portal-field-wide">
                    <label for="address_street">Logradouro</label>
                    <input id="address_street" type="text" name="address_street" class="portal-input" value="{{ old('address_street', $client->address_street) }}" placeholder="Rua, avenida, travessa">
                </div>
                <div class="portal-field">
                    <label for="address_number">Número</label>
                    <input id="address_number" type="text" name="address_number" class="portal-input" value="{{ old('address_number', $client->address_number) }}" placeholder="Número">
                </div>
                <div class="portal-field">
                    <label for="address_complement">Complemento</label>
                    <input id="address_complement" type="text" name="address_complement" class="portal-input" value="{{ old('address_complement', $client->address_complement) }}" placeholder="Apartamento, bloco, sala">
                </div>
                <div class="portal-field">
                    <label for="address_district">Bairro</label>
                    <input id="address_district" type="text" name="address_district" class="portal-input" value="{{ old('address_district', $client->address_district) }}" placeholder="Bairro">
                </div>
                <div class="portal-field">
                    <label for="address_city">Cidade</label>
                    <input id="address_city" type="text" name="address_city" class="portal-input" value="{{ old('address_city', $client->address_city) }}" placeholder="Cidade">
                </div>
                <div class="portal-field">
                    <label for="address_state">UF</label>
                    <input id="address_state" type="text" name="address_state" class="portal-input" maxlength="2" value="{{ old('address_state', $client->address_state) }}" placeholder="UF">
                </div>
            </div>
        </section>

        @if($errors->any())
            <div class="portal-status portal-status-warning portal-section-spaced">
                Revise os campos destacados antes de salvar.
            </div>
        @endif

        <div class="portal-profile-actions">
            <button type="submit" class="portal-button">Salvar alterações</button>
        </div>
    </form>
@endsection
