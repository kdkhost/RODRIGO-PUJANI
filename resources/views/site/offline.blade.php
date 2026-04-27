<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $appName }} · Offline</title>
    <style>
        :root { --gold:#C49A3C; --ink:#0B0C10; --ink-2:#111318; --cream:#F0E9DC; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: radial-gradient(circle at top, rgba(196,154,60,0.12), transparent 45%), linear-gradient(160deg, var(--ink), var(--ink-2)); color: var(--cream); font-family: Arial, Helvetica, sans-serif; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .panel { width: min(520px, 100%); border: 1px solid rgba(196,154,60,0.2); background: rgba(255,255,255,0.03); padding: 32px; }
        h1 { margin: 0 0 12px; font-size: 32px; font-weight: 400; }
        p { margin: 0 0 16px; line-height: 1.6; color: rgba(240,233,220,0.78); }
        .meta { font-size: 14px; color: rgba(240,233,220,0.62); }
        a { color: var(--gold); text-decoration: none; }
    </style>
</head>
<body>
    <main class="panel">
        <div class="meta">Conexão indisponível</div>
        <h1>{{ $offlineTitle }}</h1>
        <p>{{ $offlineMessage }}</p>
        <p class="meta">Contato imediato: {{ $phone }} · <a href="mailto:{{ $email }}">{{ $email }}</a></p>
        <p><a href="javascript:window.location.reload()">{{ $offlineButtonLabel }}</a></p>
    </main>
</body>
</html>
