<x-guest-layout title="Nova senha | Pujani Admin">
    <div class="auth-form-heading">
        <span>Seguranca da conta</span>
        <h2>Criar nova senha</h2>
        <p>Defina uma senha forte para restaurar o acesso ao painel.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="auth-form">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="auth-field">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" class="auth-input @error('email') auth-input-error @enderror" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            @error('email')<div class="auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="auth-field">
            <label for="password">Nova senha</label>
            <input id="password" type="password" name="password" class="auth-input @error('password') auth-input-error @enderror" required autocomplete="new-password">
            @error('password')<div class="auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="auth-field">
            <label for="password_confirmation">Confirmar nova senha</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="auth-input @error('password_confirmation') auth-input-error @enderror" required autocomplete="new-password">
            @error('password_confirmation')<div class="auth-error">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="auth-button">Salvar nova senha</button>
    </form>
</x-guest-layout>
