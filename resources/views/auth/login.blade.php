<x-guest-layout title="Login | Pujani Admin">
    <div class="auth-form-heading">
        <span>Acesso administrativo</span>
        <h2>Entrar no painel</h2>
        <p>Use suas credenciais para continuar para a area administrativa.</p>
    </div>

    @if (session('status'))
        <div class="auth-status auth-status-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <div class="auth-field">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" class="auth-input @error('email') auth-input-error @enderror" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')<div class="auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="auth-field">
            <label for="password">Senha</label>
            <input id="password" type="password" name="password" class="auth-input @error('password') auth-input-error @enderror" required autocomplete="current-password">
            @error('password')<div class="auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="auth-row">
            <label class="auth-check" for="remember_me">
                <input id="remember_me" type="checkbox" name="remember">
                <span>Lembrar acesso</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="auth-link">Esqueci minha senha</a>
            @endif
        </div>

        <button type="submit" class="auth-button">Entrar</button>
    </form>
</x-guest-layout>
