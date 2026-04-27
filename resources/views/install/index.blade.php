<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalador do Sistema</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #0b1020;
            --panel: rgba(11, 16, 32, 0.88);
            --panel-border: rgba(196, 154, 60, 0.24);
            --text: #eef2ff;
            --muted: #9aa4bf;
            --accent: #c49a3c;
            --accent-strong: #f4c96d;
            --danger: #ff8a80;
            --ok: #79dba5;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top, rgba(196, 154, 60, 0.18), transparent 32%),
                linear-gradient(160deg, #0b1020 0%, #151c33 100%);
            color: var(--text);
        }
        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 32px auto;
            display: grid;
            gap: 24px;
            grid-template-columns: 320px 1fr;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(12px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.28);
        }
        h1, h2, h3, p { margin-top: 0; }
        h1 { font-size: 2rem; margin-bottom: 12px; }
        h2 { font-size: 1.1rem; margin-bottom: 16px; }
        .lead { color: var(--muted); line-height: 1.6; margin-bottom: 20px; }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(196, 154, 60, 0.16);
            color: var(--accent-strong);
            font-size: .85rem;
            margin-bottom: 16px;
        }
        .checklist {
            display: grid;
            gap: 12px;
        }
        .check {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            color: var(--muted);
        }
        .check strong { color: var(--text); font-size: .95rem; }
        .status-ok { color: var(--ok); }
        .status-error { color: var(--danger); }
        form {
            display: grid;
            gap: 24px;
        }
        .section {
            display: grid;
            gap: 16px;
        }
        .section-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .field {
            display: grid;
            gap: 8px;
        }
        .field.full { grid-column: 1 / -1; }
        label {
            font-size: .92rem;
            color: var(--muted);
        }
        input, select {
            width: 100%;
            height: 46px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            padding: 0 14px;
            outline: none;
        }
        input:focus, select:focus {
            border-color: rgba(196, 154, 60, 0.7);
            box-shadow: 0 0 0 3px rgba(196, 154, 60, 0.15);
        }
        .help {
            color: var(--muted);
            font-size: .82rem;
            line-height: 1.5;
        }
        .switch {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            color: var(--muted);
            font-size: .92rem;
        }
        .switch input {
            width: 18px;
            height: 18px;
            margin-top: 2px;
        }
        .errors {
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid rgba(255, 138, 128, 0.35);
            background: rgba(255, 138, 128, 0.08);
            color: #ffd3cf;
        }
        .errors ul {
            margin: 0;
            padding-left: 18px;
        }
        .actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
        }
        button {
            border: 0;
            border-radius: 12px;
            height: 48px;
            padding: 0 22px;
            font-size: .95rem;
            font-weight: 700;
            color: #111827;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-strong) 100%);
            cursor: pointer;
        }
        .note {
            color: var(--muted);
            font-size: .85rem;
        }
        @media (max-width: 980px) {
            .page { grid-template-columns: 1fr; }
            .section-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="page">
        <aside class="panel">
            <div class="badge">Instalador automático para hospedagem compartilhada</div>
            <h1>Configurar sistema</h1>
            <p class="lead">
                Este assistente grava o arquivo de ambiente, valida a conexao com MariaDB/MySQL,
                executa migracoes, popula os dados iniciais e cria o primeiro administrador.
            </p>

            <h2>Checklist do ambiente</h2>
            <div class="checklist">
                <div class="check">
                    <strong>.env disponível para escrita</strong>
                    <span class="{{ $status['env_writable'] ? 'status-ok' : 'status-error' }}">
                        {{ $status['env_writable'] ? 'OK' : 'Revisar' }}
                    </span>
                </div>
                <div class="check">
                    <strong>storage/ com permissão de escrita</strong>
                    <span class="{{ $status['storage_writable'] ? 'status-ok' : 'status-error' }}">
                        {{ $status['storage_writable'] ? 'OK' : 'Revisar' }}
                    </span>
                </div>
                <div class="check">
                    <strong>bootstrap/cache com permissão</strong>
                    <span class="{{ $status['bootstrap_writable'] ? 'status-ok' : 'status-error' }}">
                        {{ $status['bootstrap_writable'] ? 'OK' : 'Revisar' }}
                    </span>
                </div>
                <div class="check">
                    <strong>public/ com permissão para link simbólico</strong>
                    <span class="{{ $status['public_writable'] ? 'status-ok' : 'status-error' }}">
                        {{ $status['public_writable'] ? 'OK' : 'Revisar' }}
                    </span>
                </div>
            </div>
        </aside>

        <main class="panel">
            <h2>Dados da instalação</h2>

            @if ($errors->any())
                <div class="errors">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('install.store') }}">
                @csrf

                <section class="section">
                    <div>
                        <h3>Aplicação</h3>
                        <p class="help">Use a URL final do domínio principal já apontando para a raiz pública configurada via .htaccess.</p>
                    </div>
                    <div class="section-grid">
                        <div class="field">
                            <label for="app_name">Nome do sistema</label>
                            <input id="app_name" name="app_name" type="text" value="{{ old('app_name', $defaults['app_name']) }}" required>
                        </div>
                        <div class="field">
                            <label for="app_url">URL principal</label>
                            <input id="app_url" name="app_url" type="url" value="{{ old('app_url', $defaults['app_url']) }}" required>
                        </div>
                    </div>
                </section>

                <section class="section">
                    <div>
                        <h3>Banco de dados</h3>
                        <p class="help">Compatível com MariaDB e MySQL. Em cPanel, normalmente o host e o banco são informados pelo provedor.</p>
                    </div>
                    <div class="section-grid">
                        <div class="field">
                            <label for="db_connection">Driver</label>
                            <select id="db_connection" name="db_connection" required>
                                @foreach (['mariadb' => 'MariaDB', 'mysql' => 'MySQL'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('db_connection', $defaults['db_connection']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="db_host">Host</label>
                            <input id="db_host" name="db_host" type="text" value="{{ old('db_host', $defaults['db_host']) }}" required>
                        </div>
                        <div class="field">
                            <label for="db_port">Porta</label>
                            <input id="db_port" name="db_port" type="number" value="{{ old('db_port', $defaults['db_port']) }}" required>
                        </div>
                        <div class="field">
                            <label for="db_database">Banco</label>
                            <input id="db_database" name="db_database" type="text" value="{{ old('db_database', $defaults['db_database']) }}" required>
                        </div>
                        <div class="field">
                            <label for="db_username">Usuário</label>
                            <input id="db_username" name="db_username" type="text" value="{{ old('db_username', $defaults['db_username']) }}" required>
                        </div>
                        <div class="field">
                            <label for="db_password">Senha</label>
                            <input id="db_password" name="db_password" type="password" autocomplete="new-password">
                        </div>
                    </div>
                </section>

                <section class="section">
                    <div>
                        <h3>Primeiro administrador</h3>
                        <p class="help">Esse usuário já sai com perfil Super Admin e acesso completo ao painel.</p>
                    </div>
                    <div class="section-grid">
                        <div class="field">
                            <label for="admin_name">Nome</label>
                            <input id="admin_name" name="admin_name" type="text" value="{{ old('admin_name', $defaults['admin_name']) }}" required>
                        </div>
                        <div class="field">
                            <label for="admin_email">E-mail</label>
                            <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email', $defaults['admin_email']) }}" required>
                        </div>
                        <div class="field">
                            <label for="admin_password">Senha</label>
                            <input id="admin_password" name="admin_password" type="password" autocomplete="new-password" required>
                        </div>
                        <div class="field">
                            <label for="admin_password_confirmation">Confirmação da senha</label>
                            <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" autocomplete="new-password" required>
                        </div>
                        <label class="switch full">
                            <input type="checkbox" name="fresh_install" value="1" @checked(old('fresh_install'))>
                            <span>Recriar o banco do zero antes de instalar. Use apenas quando quiser apagar todas as tabelas existentes.</span>
                        </label>
                    </div>
                </section>

                <div class="actions">
                    <div class="note">Ao concluir, o sistema executa migrate, seed, storage:link e limpeza de cache.</div>
                    <button type="submit">Instalar sistema</button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>
