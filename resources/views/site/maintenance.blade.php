<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manutenção programada</title>
    <style>
        :root{color-scheme:dark}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:
            radial-gradient(circle at top, rgba(196,154,60,.16), transparent 28%),
            linear-gradient(160deg, #0b1020 0%, #12182b 100%);
            color:#f6f2e8;font-family:Arial,sans-serif}
        .box{width:min(760px,100%);padding:40px;border:1px solid rgba(196,154,60,.24);background:rgba(10,15,28,.88);backdrop-filter:blur(10px);text-align:center;border-radius:20px;box-shadow:0 30px 80px rgba(0,0,0,.34)}
        .eyebrow{display:inline-flex;padding:8px 14px;border-radius:999px;background:rgba(196,154,60,.14);color:#f4ca73;font-size:.85rem;letter-spacing:.04em;text-transform:uppercase;margin-bottom:18px}
        h1{margin:0 0 14px;font-size:2.2rem}
        p{margin:0;color:rgba(246,242,232,.78);line-height:1.7}
        .meta{margin-top:24px;font-size:.95rem}
        .countdown{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:28px 0}
        .count-card{padding:18px 10px;border-radius:16px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.03)}
        .count-card strong{display:block;font-size:1.8rem;color:#fff}
        .count-card span{display:block;margin-top:6px;color:rgba(246,242,232,.65);font-size:.82rem;text-transform:uppercase;letter-spacing:.08em}
        .actions{display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin-top:28px}
        .btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:0 18px;border-radius:12px;text-decoration:none;font-weight:700}
        .btn-primary{background:linear-gradient(135deg,#c49a3c,#f4ca73);color:#101522}
        .btn-secondary{border:1px solid rgba(255,255,255,.12);color:#f6f2e8}
        .contact{margin-top:20px;font-size:.92rem}
        .contact a{color:#f4ca73;text-decoration:none}
        @media (max-width:640px){.countdown{grid-template-columns:repeat(2,minmax(0,1fr))}h1{font-size:1.8rem}}
    </style>
</head>
<body>
    @php
        $release = $releaseAt ? \Carbon\Carbon::parse($releaseAt) : null;
        $phone = setting('site.company_phone', '(11) 3456-7890');
        $email = setting('site.company_email', 'contato@pujani.adv.br');
    @endphp
    <div class="box">
        <div class="eyebrow">Manutenção temporária</div>
        <h1>Estamos ajustando o ambiente</h1>
        <p>O acesso público foi limitado enquanto concluímos uma atualização programada. O painel administrativo e dispositivos liberados continuam com acesso normal.</p>

        @if($release)
            <div class="countdown" data-countdown="{{ $release->toIso8601String() }}">
                @foreach (['dias', 'horas', 'min', 'seg'] as $label)
                    <div class="count-card">
                        <strong data-unit>00</strong>
                        <span>{{ $label }}</span>
                    </div>
                @endforeach
            </div>
            <p class="meta">Previsão de liberação automática em {{ $release->format('d/m/Y \a\s H:i') }}.</p>
        @else
            <p class="meta">A liberação será feita assim que a revisão técnica for concluída.</p>
        @endif

        <div class="actions">
            <a class="btn btn-primary" href="{{ url('/') }}">Tentar novamente</a>
            <a class="btn btn-secondary" href="mailto:{{ $email }}">Falar por e-mail</a>
        </div>

        <div class="contact">
            Atendimento: <a href="tel:{{ preg_replace('/\D+/', '', $phone) }}">{{ $phone }}</a> ·
            <a href="mailto:{{ $email }}">{{ $email }}</a>
        </div>
    </div>

    @if($release)
        <script>
            (() => {
                const countdown = document.querySelector('[data-countdown]');

                if (!countdown) {
                    return;
                }

                const units = countdown.querySelectorAll('[data-unit]');
                const target = new Date(countdown.dataset.countdown).getTime();

                const render = () => {
                    const diff = Math.max(0, target - Date.now());
                    const days = Math.floor(diff / 86400000);
                    const hours = Math.floor((diff % 86400000) / 3600000);
                    const minutes = Math.floor((diff % 3600000) / 60000);
                    const seconds = Math.floor((diff % 60000) / 1000);
                    const values = [days, hours, minutes, seconds];

                    units.forEach((item, index) => {
                        item.textContent = String(values[index]).padStart(2, '0');
                    });

                    if (diff <= 0) {
                        window.location.reload();
                    }
                };

                render();
                window.setInterval(render, 1000);
            })();
        </script>
    @endif
</body>
</html>
