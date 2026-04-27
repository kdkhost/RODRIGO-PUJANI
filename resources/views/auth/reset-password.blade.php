<x-guest-layout title="Nova senha | Painel administrativo">
    <div class="auth-form-heading">
        <span>Segurança da conta</span>
        <h2>Criar nova senha</h2>
        <p>Defina uma senha forte para restaurar o acesso ao painel.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="auth-form" data-recaptcha-form data-recaptcha-action="password_reset_confirm">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <input type="hidden" name="recaptcha_token" value="">

        <div class="auth-field">
            <label for="email">E-mail</label>
            <input
                id="email"
                type="email"
                name="email"
                class="auth-input @error('email') auth-input-error @enderror"
                value="{{ old('email', $request->email) }}"
                required
                autofocus
                autocomplete="username"
                placeholder="E-mail"
            >
        </div>

        <div class="auth-field">
            <label for="password">Nova senha</label>
            <input
                id="password"
                type="password"
                name="password"
                class="auth-input @error('password') auth-input-error @enderror"
                required
                autocomplete="new-password"
                placeholder="Nova senha"
            >
        </div>

        <div class="auth-field">
            <label for="password_confirmation">Confirmar nova senha</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                class="auth-input @error('password_confirmation') auth-input-error @enderror"
                required
                autocomplete="new-password"
                placeholder="Confirmar nova senha"
            >
        </div>

        <button type="submit" class="auth-button">Salvar nova senha</button>
    </form>
</x-guest-layout>
