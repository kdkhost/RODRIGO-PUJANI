<x-guest-layout title="Verificar e-mail | Painel administrativo">
    <div class="auth-form-heading">
        <span>Validação da conta</span>
        <h2>Verifique seu e-mail</h2>
        <p>Use o link enviado para seu e-mail para liberar todos os recursos do acesso administrativo.</p>
    </div>

    <div class="auth-actions-split">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="auth-button">Reenviar verificação</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="auth-secondary-button">Sair</button>
        </form>
    </div>
</x-guest-layout>
