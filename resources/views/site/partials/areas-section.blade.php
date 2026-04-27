@php
    $section = $sectionBlocks->get('areas') ?? null;
@endphp

<section id="{{ $embedded ? 'areas' : 'areas-de-atuacao' }}" class="py-24 lg:py-36 relative" style="background:#0B0C10;">
    <div class="absolute inset-0 grid-pattern opacity-50"></div>
    <div class="max-w-7xl mx-auto px-6 lg:px-16 relative">
        <div class="text-center mb-16">
            <div class="section-label aos mb-4 inline-block">— Expertise Jurídica</div>
            <h2 class="font-display leading-tight aos delay-100" style="font-size:clamp(2.2rem,5vw,4rem);font-weight:300;">
                {!! $section?->title ?: 'Áreas de <span class="text-gold-gradient font-semibold">Atuação</span>' !!}
            </h2>
            <p class="text-cream/40 max-w-lg mx-auto mt-4 aos delay-200">{{ $section?->subtitle ?: 'Cobertura jurídica abrangente com especialistas dedicados em cada área do direito brasileiro.' }}</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($featuredAreas as $area)
                @php
                    $badges = collect(preg_split('/[,|]/', (string) ($area->highlight ?: '')))->map(fn ($item) => trim($item))->filter()->take(3);
                    $delay = (($loop->index % 3) + 1) * 100;
                    $areaKey = \Illuminate\Support\Str::of($area->slug ?: $area->title)->ascii()->lower();
                @endphp
                <article class="card-glass p-8 aos delay-{{ $delay }}">
                    <div class="area-icon mb-5">
                        @if($areaKey->contains('empresarial'))
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        @elseif($areaKey->contains('civil'))
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><path d="M12 2L3 7v5c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V7L12 2z"/></svg>
                        @elseif($areaKey->contains('tributario'))
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        @elseif($areaKey->contains('trabalhista'))
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        @elseif($areaKey->contains('digital'))
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        @elseif($areaKey->contains('imobiliario'))
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        @else
                            <span class="font-display text-lg text-gold/80">{{ $area->icon ?: 'ADV' }}</span>
                        @endif
                    </div>
                    <h3 class="font-display text-xl font-semibold text-cream/90 mb-3">{{ $area->title }}</h3>
                    <p class="text-cream/45 text-sm leading-relaxed mb-5">{{ $area->short_description }}</p>
                    @if($badges->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach($badges as $badge)
                                <span class="text-[0.65rem] tracking-widest uppercase text-gold/50 border border-gold/15 px-2 py-1">{{ $badge }}</span>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
