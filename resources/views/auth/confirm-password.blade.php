<x-guest-layout title="Confirmar senha | Painel administrativo">
    <div class="auth-form-heading">
        <span>Área segura</span>
        <h2>Confirme sua senha</h2>
        <p>Esta etapa protege operações sensíveis dentro do painel.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
        @csrf

        <div class="auth-field">
            <label for="password">Senha</label>
            <input
                id="password"
                type="password"
                name="password"
                class="auth-input @error('password') auth-input-error @enderror"
                required
                autocomplete="current-password"
                placeholder="Senha"
            >
        </div>

        <button type="submit" class="auth-button">Confirmar acesso</button>
    </form>
</x-guest-layout>
