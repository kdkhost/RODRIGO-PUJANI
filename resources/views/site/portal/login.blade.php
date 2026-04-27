@extends('site.portal.layout')

@php($pageTitle = 'Portal do cliente')

@section('content')
    <div class="portal-form-heading">
        <span>Acesso reservado</span>
        <h2>Entre para acompanhar seus processos</h2>
        <p>Use o documento do cadastro e o código de acesso informado pelo escritório.</p>
    </div>

    @if(session('portal_status'))
        <div class="portal-status portal-status-success">{{ session('portal_status') }}</div>
    @endif

    @if(session('portal_error'))
        <div class="portal-status portal-status-warning">{{ session('portal_error') }}</div>
    @endif

    <form action="{{ route('portal.authenticate') }}" method="POST" class="portal-form">
        @csrf

        <div class="portal-field">
            <label for="portal_document_number">CPF ou CNPJ</label>
            <input id="portal_document_number" type="text" name="document_number" class="portal-input @error('document_number') portal-input-error @enderror" value="{{ old('document_number') }}" data-mask="cpf-cnpj" required autofocus>
            @error('document_number')
                <div class="portal-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="portal-field">
            <label for="portal_access_code">Código de acesso</label>
            <input id="portal_access_code" type="password" name="access_code" class="portal-input @error('access_code') portal-input-error @enderror" required>
            @error('access_code')
                <div class="portal-error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="portal-button">Entrar no portal</button>
    </form>

    <div class="portal-support-copy">
        {{ $portalPanel['support_text'] }}
    </div>
@endsection
