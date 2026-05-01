<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,sans-serif;color:#1d2330;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:24px 12px;">
    <tr>
        <td align="center">
            <table width="620" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid #e5e7ef;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="padding:24px 24px 8px;font-size:22px;font-weight:700;color:#0f172a;">
                        {{ config('app.name') }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 24px 12px;font-size:16px;line-height:1.55;color:#1f2937;">
                        {!! nl2br(e($header)) !!}
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 24px 8px;font-size:15px;line-height:1.65;color:#334155;">
                        {!! nl2br(e($body)) !!}
                    </td>
                </tr>
                @if(!empty($actionUrl))
                    <tr>
                        <td style="padding:16px 24px 12px;">
                            <a href="{{ $actionUrl }}" style="display:inline-block;background:#c49a3c;color:#0b0b0f;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:700;">
                                {{ $actionLabel ?? 'Abrir' }}
                            </a>
                        </td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:10px 24px 22px;font-size:13px;line-height:1.5;color:#64748b;border-top:1px solid #eef1f7;">
                        {!! nl2br(e($footer)) !!}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

