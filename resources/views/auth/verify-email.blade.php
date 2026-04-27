<x-guest-layout title="Verificar e-mail | Pujani Admin">
    <div class="auth-form-heading">
        <span>Validacao da conta</span>
        <h2>Verifique seu e-mail</h2>
        <p>Use o link enviado para seu e-mail para liberar todos os recursos do acesso administrativo.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="auth-status auth-status-success">
            Um novo link de verificacao foi enviado para o e-mail cadastrado.
        </div>
    @endif

    <div class="auth-actions-split">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="auth-button">Reenviar verificacao</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="auth-secondary-button">Sair</button>
        </form>
    </div>
</x-guest-layout>
