@php
    $coverUrl = site_asset_url($page->cover_path ?: 'assets/site/premium/hero-home.jpg');
    $menuPages = collect($publicPages ?? []);
    $pageUrl = function (?string $slug, ?string $fallback = null) use ($menuPages): string {
        if (! filled($slug)) {
            return $fallback ?: route('site.home');
        }

        $menuPage = $menuPages->first(fn ($item) => ($item->slug ?? null) === $slug);

        if ($menuPage?->is_home || $slug === 'home') {
            return route('site.home');
        }

        return $menuPage ? route('site.show', $menuPage->slug) : ($fallback ?: route('site.show', $slug));
    };
    $contactPageUrl = $pageUrl('contato', route('site.home'));
    $areasPageUrl = $pageUrl('areas-de-atuacao', route('site.home'));
@endphp

<section id="hero" class="hero-bg grid-pattern relative min-h-screen flex flex-col justify-center overflow-hidden">
    <div class="absolute left-0 top-0 bottom-0 w-px" style="background: linear-gradient(180deg, transparent, rgba(196,154,60,0.3), transparent);"></div>
    <div class="absolute right-0 top-0 bottom-0 w-px" style="background: linear-gradient(180deg, transparent, rgba(196,154,60,0.15), transparent);"></div>

    <div class="hidden sm:block absolute top-1/4 right-8 lg:right-24 w-40 h-40 lg:w-64 lg:h-64 border border-gold/8 rotate-12 parallax" data-speed="0.3"></div>
    <div class="hidden sm:block absolute top-1/4 right-8 lg:right-24 w-40 h-40 lg:w-64 lg:h-64 border border-gold/5 -rotate-6 parallax" data-speed="0.5"></div>

    <div class="absolute right-0 top-1/2 -translate-y-1/2 font-display text-[20rem] lg:text-[28rem] font-bold leading-none select-none pointer-events-none" style="color: rgba(196,154,60,0.025); right: -4rem; line-height: 1;">§</div>

    <div class="max-w-7xl mx-auto px-6 lg:px-16 pt-32 pb-20 w-full">
        <div class="grid lg:grid-cols-12 gap-12 items-center min-w-0">
            <div class="lg:col-span-7 min-w-0" style="max-width:calc(100vw - 3rem);">
                <div class="section-label aos mb-8">— Advocacia Estratégica de Excelência</div>

                <h1 class="font-display leading-none mb-6 aos delay-100" style="font-size: clamp(3rem, 7vw, 6.5rem); font-weight: 300; letter-spacing: -0.01em;">
                    Defendemos<br>
                    seus <em class="hero-em not-italic text-gold-gradient font-semibold">direitos</em><br>
                    com precisão<br>
                    <span style="color: rgba(240,233,220,0.45);">e estratégia.</span>
                </h1>

                <p class="text-cream/50 max-w-[calc(100vw-4rem)] sm:max-w-lg leading-relaxed mb-10 aos delay-200" style="font-size: 1.05rem;">
                    Há mais de duas décadas, o escritório Pujani Advogados combina conhecimento jurídico profundo, ética inabalável e resultados concretos para pessoas e empresas.
                </p>

                <div class="flex flex-col sm:flex-row sm:flex-wrap gap-4 aos delay-300">
                    <a href="{{ $contactPageUrl }}" class="btn-primary px-8 py-4 inline-flex items-center justify-center gap-3 w-[calc(100vw-4rem)] max-w-[calc(100vw-4rem)] sm:w-auto sm:max-w-none">
                        <span>Agendar Consulta</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="relative z-10">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <a href="{{ $areasPageUrl }}" class="btn-ghost px-8 py-4 inline-block text-center w-[calc(100vw-4rem)] max-w-[calc(100vw-4rem)] sm:w-auto sm:max-w-none">Nossas Áreas</a>
                </div>

                <div class="flex flex-wrap gap-8 mt-14 pt-10 aos delay-400" style="border-top: 1px solid rgba(196,154,60,0.15);">
                    <div>
                        <div class="font-display text-3xl font-semibold text-gold-gradient"><span class="counter" data-target="20">0</span>+</div>
                        <div class="text-xs text-cream/40 tracking-widest uppercase mt-1">Anos de Atuação</div>
                    </div>
                    <div>
                        <div class="font-display text-3xl font-semibold text-gold-gradient"><span class="counter" data-target="2400">0</span>+</div>
                        <div class="text-xs text-cream/40 tracking-widest uppercase mt-1">Casos Resolvidos</div>
                    </div>
                    <div>
                        <div class="font-display text-3xl font-semibold text-gold-gradient"><span class="counter" data-target="98">0</span>%</div>
                        <div class="text-xs text-cream/40 tracking-widest uppercase mt-1">Satisfação</div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 relative min-w-0">
                <div class="relative aos-right delay-200">
                    <div class="relative" style="aspect-ratio: 3/4;">
                        <div class="absolute inset-0" style="background: linear-gradient(135deg, rgba(196,154,60,0.08), rgba(196,154,60,0.02)); border: 1px solid rgba(196,154,60,0.2);"></div>
                        @if($coverUrl)
                            <img src="{{ $coverUrl }}" alt="Pujani Advogados" class="absolute inset-0 w-full h-full object-cover opacity-55">
                            <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(11,12,16,0.12), rgba(11,12,16,0.78));"></div>
                        @endif
                        <div class="absolute inset-0 flex flex-col items-center justify-center p-12">
                            <svg viewBox="0 0 200 260" class="w-full max-w-xs opacity-60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="98" y="10" width="4" height="200" fill="#C49A3C" opacity="0.6"/>
                                <circle cx="100" cy="10" r="6" fill="#C49A3C" opacity="0.8"/>
                                <line x1="100" y1="70" x2="36" y2="110" stroke="#C49A3C" stroke-width="2" opacity="0.7"/>
                                <line x1="100" y1="70" x2="164" y2="110" stroke="#C49A3C" stroke-width="2" opacity="0.7"/>
                                <ellipse cx="36" cy="126" rx="28" ry="8" fill="none" stroke="#C49A3C" stroke-width="1.5" opacity="0.6"/>
                                <line x1="8" y1="118" x2="64" y2="118" stroke="#C49A3C" stroke-width="1.5" opacity="0.5"/>
                                <ellipse cx="164" cy="118" rx="28" ry="8" fill="none" stroke="#C49A3C" stroke-width="1.5" opacity="0.6"/>
                                <line x1="136" y1="110" x2="192" y2="110" stroke="#C49A3C" stroke-width="1.5" opacity="0.5"/>
                                <path d="M70 210 L100 210 L130 210" stroke="#C49A3C" stroke-width="2" opacity="0.5"/>
                                <rect x="86" y="210" width="28" height="6" rx="1" fill="#C49A3C" opacity="0.3"/>
                                <rect x="30" y="235" width="140" height="2" rx="1" fill="#C49A3C" opacity="0.2"/>
                                <rect x="50" y="243" width="100" height="2" rx="1" fill="#C49A3C" opacity="0.15"/>
                                <rect x="60" y="251" width="80" height="2" rx="1" fill="#C49A3C" opacity="0.1"/>
                            </svg>
                            <div class="mt-6 text-center">
                                <div class="font-title text-xl tracking-widest text-gold/70">PUJANI</div>
                                <div class="text-xs tracking-[0.4em] text-gold/40 uppercase mt-1">Advogados</div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -top-3 -left-3 w-8 h-8 border-t-2 border-l-2 border-gold/50"></div>
                    <div class="absolute -bottom-3 -right-3 w-8 h-8 border-b-2 border-r-2 border-gold/50"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 aos delay-600">
        <div class="text-[0.6rem] tracking-widest uppercase text-cream/30">Scroll</div>
        <div class="w-px h-12" style="background: linear-gradient(180deg, rgba(196,154,60,0.5), transparent); animation: scrollPulse 2s infinite;"></div>
    </div>
</section>

<style>
    @keyframes scrollPulse {
        0%, 100% { opacity: 0.3; transform: scaleY(1); }
        50% { opacity: 1; transform: scaleY(1.2); transform-origin: top; }
    }
</style>
