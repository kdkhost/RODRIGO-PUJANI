<x-guest-layout title="Recuperar senha | Pujani Admin">
    <div class="auth-form-heading">
        <span>Recuperação de acesso</span>
        <h2>Redefinir senha</h2>
        <p>Informe seu e-mail para receber o link de redefinição de senha.</p>
    </div>

    <x-auth-session-status class="auth-status auth-status-success" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf

        <div class="auth-field">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" class="auth-input @error('email') auth-input-error @enderror" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')<div class="auth-error">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="auth-button">Enviar link de redefinição</button>

        <div class="auth-footer-link">
            <a href="{{ route('login') }}" class="auth-link">Voltar para o login</a>
        </div>
    </form>
</x-guest-layout>
