<x-guest-layout title="Entrar | Painel administrativo">
    <div class="auth-form-heading">
        <span>Acesso administrativo</span>
        <h2>Entrar no painel</h2>
        <p>Use suas credenciais para continuar para a área administrativa.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="auth-form" data-recaptcha-form data-recaptcha-action="login" autocomplete="off">
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
                autocomplete="off"
                placeholder="E-mail"
            >
        </div>

        <div class="auth-field">
            <label for="password">Senha</label>
            <input
                id="password"
                type="password"
                name="password"
                class="auth-input @error('password') auth-input-error @enderror"
                required
                autocomplete="off"
                placeholder="Senha"
            >
        </div>

        <div class="auth-row">
            <label class="auth-check" for="remember_me">
                <input id="remember_me" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>Lembrar acesso</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="auth-link">Esqueci minha senha</a>
            @endif
        </div>

        <button type="submit" class="auth-button">Entrar</button>
    </form>
</x-guest-layout>
