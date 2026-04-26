@php
    $preview = $preview ?? false;
    $preloaderId = $preview ? 'system-preloader-preview' : 'system-preloader';
    $style = in_array($preloader['style'] ?? 'spinner', ['spinner', 'bar', 'orbit', 'pulse'], true) ? $preloader['style'] : 'spinner';
@endphp

<div
    id="{{ $preloaderId }}"
    class="system-preloader system-preloader-{{ $style }} {{ $preview ? 'system-preloader-preview' : '' }}"
    data-min-duration="{{ (int) ($preloader['min_duration'] ?? 0) }}"
    data-started-at="{{ (int) (microtime(true) * 1000) }}"
    style="--preloader-bg: {{ $preloader['background_color'] ?? '#0f1318' }}; --preloader-accent: {{ $preloader['accent_color'] ?? '#c49a3c' }}; --preloader-text: {{ $preloader['text_color'] ?? '#f4ead7' }};"
>
    <div class="system-preloader-inner">
        @if(! empty($preloader['logo_url']))
            <img src="{{ $preloader['logo_url'] }}" alt="{{ $preloader['brand'] ?? config('app.name') }}" class="system-preloader-logo">
        @else
            <div class="system-preloader-mark">{{ mb_substr((string) ($preloader['brand'] ?? 'P'), 0, 1) }}</div>
        @endif

        <div class="system-preloader-brand">{{ $preloader['brand'] ?? config('app.name') }}</div>
        <div class="system-preloader-loader" aria-hidden="true">
            <span></span><span></span><span></span>
        </div>
        <div class="system-preloader-message">{{ $preloader['message'] ?? 'Carregando...' }}</div>
    </div>
</div>

<style>
    .system-preloader{position:fixed;inset:0;z-index:2147483000;display:flex;align-items:center;justify-content:center;background:var(--preloader-bg);color:var(--preloader-text);transition:opacity .38s ease,visibility .38s ease}
    .system-preloader.is-hidden{opacity:0;visibility:hidden}
    .system-preloader-inner{display:flex;flex-direction:column;align-items:center;gap:.9rem;padding:2rem;text-align:center}
    .system-preloader-logo{max-width:112px;max-height:72px;object-fit:contain}
    .system-preloader-mark{width:64px;height:64px;border:1px solid color-mix(in srgb,var(--preloader-accent) 62%,transparent);display:flex;align-items:center;justify-content:center;border-radius:16px;background:color-mix(in srgb,var(--preloader-accent) 12%,transparent);color:var(--preloader-accent);font-weight:800;font-size:1.45rem}
    .system-preloader-brand{font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .system-preloader-message{max-width:320px;opacity:.72;font-size:.9rem}
    .system-preloader-loader{position:relative;width:72px;height:72px}
    .system-preloader-spinner .system-preloader-loader span:first-child{position:absolute;inset:0;border:3px solid color-mix(in srgb,var(--preloader-accent) 20%,transparent);border-top-color:var(--preloader-accent);border-radius:50%;animation:preloader-spin .9s linear infinite}
    .system-preloader-bar .system-preloader-loader{width:180px;height:6px;overflow:hidden;border-radius:999px;background:color-mix(in srgb,var(--preloader-text) 12%,transparent)}
    .system-preloader-bar .system-preloader-loader span:first-child{display:block;width:44%;height:100%;border-radius:999px;background:linear-gradient(90deg,transparent,var(--preloader-accent),transparent);animation:preloader-bar 1.15s ease-in-out infinite}
    .system-preloader-orbit .system-preloader-loader span{position:absolute;inset:10px;border:1px solid color-mix(in srgb,var(--preloader-accent) 55%,transparent);border-radius:50%;animation:preloader-spin 1.35s linear infinite}
    .system-preloader-orbit .system-preloader-loader span:nth-child(2){inset:20px;animation-duration:.95s;animation-direction:reverse}
    .system-preloader-orbit .system-preloader-loader span:nth-child(3){inset:31px;background:var(--preloader-accent)}
    .system-preloader-pulse .system-preloader-loader span{position:absolute;inset:18px;border-radius:50%;background:var(--preloader-accent);animation:preloader-pulse 1.2s ease-out infinite}
    .system-preloader-pulse .system-preloader-loader span:nth-child(2){animation-delay:.18s}
    .system-preloader-pulse .system-preloader-loader span:nth-child(3){animation-delay:.36s}
    .system-preloader-preview{position:relative;inset:auto;z-index:1;min-height:420px;border-radius:.85rem;overflow:hidden}
    .system-preloader-preview.system-preloader{position:relative}
    @keyframes preloader-spin{to{transform:rotate(360deg)}}
    @keyframes preloader-bar{0%{transform:translateX(-120%)}100%{transform:translateX(260%)}}
    @keyframes preloader-pulse{0%{transform:scale(.35);opacity:.85}100%{transform:scale(1.25);opacity:0}}
</style>

@if(! $preview)
    <script>
        (() => {
            const hide = () => {
                const el = document.getElementById('system-preloader');
                if (!el) return;

                const startedAt = Number(el.dataset.startedAt || Date.now());
                const minDuration = Number(el.dataset.minDuration || 0);
                const wait = Math.max(0, minDuration - (Date.now() - startedAt));

                window.setTimeout(() => {
                    el.classList.add('is-hidden');
                    window.setTimeout(() => el.remove(), 450);
                }, wait);
            };

            window.addEventListener('load', hide, { once: true });
            window.setTimeout(hide, 5000);
        })();
    </script>
@endif

@if(! empty($preloader['custom_css']))
    <style>{!! $preloader['custom_css'] !!}</style>
@endif
