<x-guest-layout title="Cadastro | Pujani Admin">
    <div class="auth-form-heading">
        <span>Novo acesso</span>
        <h2>Criar conta</h2>
        <p>Cadastre um novo usuário administrativo com os dados iniciais de acesso.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="auth-form">
        @csrf

        <div class="auth-field">
            <label for="name">Nome</label>
            <input id="name" class="auth-input @error('name') auth-input-error @enderror" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
            @error('name')<div class="auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="auth-field">
            <label for="email">E-mail</label>
            <input id="email" class="auth-input @error('email') auth-input-error @enderror" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
            @error('email')<div class="auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="auth-field">
            <label for="password">Senha</label>
            <input id="password" class="auth-input @error('password') auth-input-error @enderror" type="password" name="password" required autocomplete="new-password" />
            @error('password')<div class="auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="auth-field">
            <label for="password_confirmation">Confirmar senha</label>
            <input id="password_confirmation" class="auth-input @error('password_confirmation') auth-input-error @enderror" type="password" name="password_confirmation" required autocomplete="new-password" />
            @error('password_confirmation')<div class="auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="auth-actions-split">
            <a href="{{ route('login') }}" class="auth-link">Já possui acesso?</a>
            <button type="submit" class="auth-button">Cadastrar</button>
        </div>
    </form>
</x-guest-layout>
