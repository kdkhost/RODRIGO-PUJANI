@php
    $coverUrl = site_asset_url($page->cover_path);
    $heroTitle = $page->hero_title ?: "Defendemos\nseus direitos\ncom precisão\ne estratégia.";
@endphp

<section id="hero" class="hero-bg grid-pattern relative min-h-screen flex flex-col justify-center overflow-hidden">
    @if($coverUrl)
        <img src="{{ $coverUrl }}" alt="{{ $page->title }}" class="absolute inset-0 w-full h-full object-cover opacity-55">
        <div class="absolute inset-0" style="background:linear-gradient(90deg,rgba(11,12,16,.96) 0%,rgba(11,12,16,.78) 42%,rgba(11,12,16,.35) 100%);"></div>
    @endif
    <div class="absolute left-0 top-0 bottom-0 w-px" style="background:linear-gradient(180deg,transparent,rgba(196,154,60,0.3),transparent);"></div>
    <div class="absolute right-0 top-0 bottom-0 w-px" style="background:linear-gradient(180deg,transparent,rgba(196,154,60,0.15),transparent);"></div>
    <div class="absolute right-0 top-1/2 -translate-y-1/2 font-display text-[12rem] lg:text-[18rem] font-bold leading-none select-none pointer-events-none" style="color:rgba(196,154,60,0.025);right:-4rem;line-height:1;">ADV</div>

    <div class="max-w-7xl mx-auto px-6 lg:px-16 pt-32 pb-20 w-full relative">
        <div class="max-w-4xl">
            <div class="section-label aos mb-8">Advocacia Estratégica de Excelência</div>
            <h1 class="font-display leading-none mb-6 aos delay-100" style="font-size:clamp(3rem,7vw,6.5rem);font-weight:300;">
                {!! nl2br(e($heroTitle)) !!}
            </h1>
            <p class="text-cream/55 max-w-2xl leading-relaxed mb-10 aos delay-200" style="font-size:1.08rem;">
                {{ $page->hero_subtitle ?: 'Há mais de duas décadas, a Pujani Advogados combina conhecimento jurídico profundo, ética inabalável e resultados concretos para pessoas e empresas.' }}
            </p>
            <div class="flex flex-wrap gap-4 aos delay-300">
                <a href="{{ $page->hero_cta_url ?: route('site.show', 'contato') }}" class="btn-primary px-8 py-4 inline-flex items-center gap-3">
                    <span>{{ $page->hero_cta_label ?: 'Agendar Consulta' }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="relative z-10"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <a href="{{ route('site.show', 'areas-de-atuacao') }}" class="btn-ghost px-8 py-4 inline-block">Nossas Áreas</a>
            </div>

            <div class="flex flex-wrap gap-8 mt-14 pt-10 aos delay-400" style="border-top:1px solid rgba(196,154,60,0.15);">
                @foreach($siteMetrics->take(3) as $metric)
                    <div>
                        <div class="font-display text-3xl font-semibold text-gold-gradient"><span class="counter" data-target="{{ $metric['counter'] }}">0</span>{{ $metric['suffix'] }}</div>
                        <div class="text-xs text-cream/40 tracking-widest uppercase mt-1">{{ $metric['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 aos delay-600">
        <div class="text-[0.6rem] tracking-widest uppercase text-cream/30">Scroll</div>
        <div class="w-px h-12" style="background:linear-gradient(180deg,rgba(196,154,60,0.5),transparent);animation:scrollPulse 2s infinite;"></div>
    </div>
</section>

<style>
    @keyframes scrollPulse {
        0%, 100% { opacity: 0.3; transform: scaleY(1); }
        50% { opacity: 1; transform: scaleY(1.2); transform-origin: top; }
    }
</style>
