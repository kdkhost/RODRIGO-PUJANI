<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · {{ $title }}</title>
    <style>
        :root{color-scheme:dark}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:
            radial-gradient(circle at top, rgba(196,154,60,.18), transparent 28%),
            linear-gradient(160deg, #0b1020 0%, #141b30 100%);
            color:#f6f2e8;font-family:"Segoe UI",Arial,sans-serif}
        .panel{width:min(720px,100%);padding:40px;border-radius:20px;border:1px solid rgba(196,154,60,.24);background:rgba(10,15,28,.88);backdrop-filter:blur(10px);box-shadow:0 30px 80px rgba(0,0,0,.34)}
        .code{display:inline-flex;align-items:center;justify-content:center;min-width:74px;height:36px;padding:0 12px;border-radius:999px;background:rgba(196,154,60,.14);color:#f4ca73;font-weight:700;margin-bottom:18px}
        h1{margin:0 0 14px;font-size:2rem}
        p{margin:0;color:rgba(246,242,232,.76);line-height:1.7}
        .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}
        .btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:0 18px;border-radius:12px;text-decoration:none;font-weight:700}
        .btn-primary{background:linear-gradient(135deg,#c49a3c,#f4ca73);color:#101522}
        .btn-secondary{border:1px solid rgba(255,255,255,.12);color:#f6f2e8}
    </style>
</head>
<body>
    <main class="panel">
        <div class="code">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <div class="actions">
            <a class="btn btn-primary" href="{{ $actionUrl ?? url('/') }}">{{ $actionLabel ?? 'Voltar ao início' }}</a>
            <a class="btn btn-secondary" href="javascript:history.back()">Retornar</a>
        </div>
    </main>
</body>
</html>
