@extends('site.portal.layout')

@php($pageTitle = 'Portal do cliente')

@section('content')
    <div class="portal-form-heading">
        <span>Acesso reservado</span>
        <h2>Entre para acompanhar seus processos</h2>
        <p>Use o documento do cadastro e o código de acesso informado pelo escritório.</p>
    </div>

    <form action="{{ route('portal.authenticate') }}" method="POST" class="portal-form" data-recaptcha-form data-recaptcha-action="portal_login">
        @csrf

        <input type="hidden" name="recaptcha_token" value="">

        <div class="portal-field">
            <label for="portal_document_number">CPF ou CNPJ</label>
            <input
                id="portal_document_number"
                type="text"
                name="document_number"
                class="portal-input @error('document_number') portal-input-error @enderror"
                value="{{ old('document_number') }}"
                data-mask="cpf-cnpj"
                required
                autofocus
                placeholder="CPF ou CNPJ"
            >
        </div>

        <div class="portal-field">
            <label for="portal_access_code">Código de acesso</label>
            <input
                id="portal_access_code"
                type="password"
                name="access_code"
                class="portal-input @error('access_code') portal-input-error @enderror"
                required
                placeholder="Código de acesso"
            >
        </div>

        <button type="submit" class="portal-button">Entrar no portal</button>
    </form>

    <div class="portal-support-copy">
        {{ $portalPanel['support_text'] }}
    </div>
@endsection
