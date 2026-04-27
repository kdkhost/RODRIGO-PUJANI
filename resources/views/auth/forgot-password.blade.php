<x-guest-layout title="Recuperar senha | Painel administrativo">
    <div class="auth-form-heading">
        <span>Recuperação de acesso</span>
        <h2>Redefinir senha</h2>
        <p>Informe seu e-mail para receber o link de redefinição de senha.</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="auth-form" data-recaptcha-form data-recaptcha-action="password_reset_request">
        @csrf

        <input type="hidden" name="recaptcha_token" value="">

        <div class="auth-field">
            <label for="email">E-mail</label>
            <input
                id="email"
                type="email"
                name="email"
                class="auth-input @error('email') auth-input-error @enderror"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="E-mail"
            >
        </div>

        <button type="submit" class="auth-button">Enviar link de redefinição</button>

        <div class="auth-footer-link">
            <a href="{{ route('login') }}" class="auth-link">Voltar para o login</a>
        </div>
    </form>
</x-guest-layout>
