@php
    $theme = array_merge([
        'show_logo' => true,
        'font_family' => 'Segoe UI, Arial, sans-serif',
        'layout' => 'premium',
        'background_color' => '#0f172a',
        'body_background_color' => '#f4f6fb',
        'card_background_color' => '#ffffff',
        'heading_color' => '#0f172a',
        'text_color' => '#334155',
        'muted_color' => '#64748b',
        'border_color' => '#e5e7ef',
        'button_background_color' => '#c49a3c',
        'button_text_color' => '#10131a',
        'custom_css' => '',
    ], $theme ?? []);

    $brandName = $brandName ?? config('app.name');
    $logoUrl = $logoUrl ?? null;
    $layout = $theme['layout'];
    $heroBackground = $layout === 'minimal' ? $theme['card_background_color'] : $theme['background_color'];
    $heroTextColor = $layout === 'minimal' ? $theme['heading_color'] : '#ffffff';
    $heroSubtleColor = $layout === 'minimal' ? $theme['muted_color'] : 'rgba(255,255,255,.78)';
    $cardShadow = $layout === 'classic'
        ? '0 8px 24px rgba(15, 23, 42, .08)'
        : ($layout === 'minimal' ? '0 2px 12px rgba(15, 23, 42, .06)' : '0 18px 52px rgba(15, 23, 42, .18)');
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: {{ $theme['body_background_color'] }};
            font-family: {!! json_encode($theme['font_family']) !!};
            color: {{ $theme['text_color'] }};
        }

        a {
            color: {{ $theme['button_background_color'] }};
        }

        .system-mail-shell {
            width: 100%;
            padding: 24px 12px;
            background: {{ $theme['body_background_color'] }};
        }

        .system-mail-card {
            width: 100%;
            max-width: 680px;
            margin: 0 auto;
            background: {{ $theme['card_background_color'] }};
            border: 1px solid {{ $theme['border_color'] }};
            border-radius: 18px;
            overflow: hidden;
            box-shadow: {{ $cardShadow }};
        }

        .system-mail-hero {
            padding: 28px 28px 22px;
            background: {{ $heroBackground }};
            color: {{ $heroTextColor }};
        }

        .system-mail-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }

        .system-mail-brand-badge {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.22);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.08);
            font-size: 18px;
            font-weight: 800;
            overflow: hidden;
        }

        .system-mail-brand-badge img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .system-mail-brand-copy strong {
            display: block;
            font-size: 20px;
            line-height: 1.1;
        }

        .system-mail-brand-copy span {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: {{ $heroSubtleColor }};
        }

        .system-mail-subject {
            font-size: 28px;
            line-height: 1.2;
            font-weight: 800;
            color: {{ $heroTextColor }};
            margin: 0;
        }

        .system-mail-body {
            padding: 28px;
            color: {{ $theme['text_color'] }};
            font-size: 15px;
            line-height: 1.75;
        }

        .system-mail-body h1,
        .system-mail-body h2,
        .system-mail-body h3,
        .system-mail-body h4,
        .system-mail-body strong {
            color: {{ $theme['heading_color'] }};
        }

        .system-mail-body p {
            margin: 0 0 16px;
        }

        .system-mail-divider {
            height: 1px;
            margin: 22px 0;
            background: {{ $theme['border_color'] }};
        }

        .system-mail-action {
            margin: 24px 0 10px;
        }

        .system-mail-action a {
            display: inline-block;
            text-decoration: none;
            background: {{ $theme['button_background_color'] }};
            color: {{ $theme['button_text_color'] }};
            padding: 13px 20px;
            border-radius: 12px;
            font-weight: 800;
        }

        .system-mail-footer {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid {{ $theme['border_color'] }};
            color: {{ $theme['muted_color'] }};
            font-size: 13px;
            line-height: 1.7;
        }

        .system-mail-footer p:last-child,
        .system-mail-body p:last-child {
            margin-bottom: 0;
        }

        {!! $theme['custom_css'] !!}

        @media only screen and (max-width: 640px) {
            .system-mail-shell {
                padding: 14px 8px;
            }

            .system-mail-hero,
            .system-mail-body {
                padding: 20px;
            }

            .system-mail-subject {
                font-size: 23px;
            }

            .system-mail-brand {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="system-mail-shell">
        <div class="system-mail-card">
            <div class="system-mail-hero">
                <div class="system-mail-brand">
                    @if($theme['show_logo'] && !empty($logoUrl))
                        <div class="system-mail-brand-badge">
                            <img src="{{ $logoUrl }}" alt="{{ $brandName }}">
                        </div>
                    @else
                        <div class="system-mail-brand-badge">{{ mb_substr((string) $brandName, 0, 1) }}</div>
                    @endif
                    <div class="system-mail-brand-copy">
                        <strong>{{ $brandName }}</strong>
                        <span>Comunicacao oficial do sistema</span>
                    </div>
                </div>
                <h1 class="system-mail-subject">{{ $subject }}</h1>
            </div>
            <div class="system-mail-body">
                @if(!empty($header))
                    <div class="system-mail-header">{!! $header !!}</div>
                @endif

                @if(!empty($header) && !empty($body))
                    <div class="system-mail-divider"></div>
                @endif

                <div class="system-mail-content">{!! $body !!}</div>

                @if(!empty($actionUrl))
                    <div class="system-mail-action">
                        <a href="{{ $actionUrl }}">{{ $actionLabel ?? 'Abrir' }}</a>
                    </div>
                @endif

                @if(!empty($footer))
                    <div class="system-mail-footer">{!! $footer !!}</div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
