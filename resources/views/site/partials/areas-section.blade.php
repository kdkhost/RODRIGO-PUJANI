@php
    $section = $sectionBlocks->get('areas') ?? null;
@endphp

<section id="{{ $embedded ? 'areas' : 'areas-de-atuacao' }}" class="py-24 lg:py-36 relative" style="background:#0B0C10;">
    <div class="absolute inset-0 grid-pattern opacity-50"></div>
    <div class="max-w-7xl mx-auto px-6 lg:px-16 relative">
        <div class="text-center mb-16">
            <div class="section-label aos mb-4 inline-block">Expertise Jurídica</div>
            <h2 class="font-display leading-tight aos delay-100" style="font-size:clamp(2.2rem,5vw,4rem);font-weight:300;">
                {!! $section?->title ?: 'Áreas de <span class="text-gold-gradient font-semibold">Atuação</span>' !!}
            </h2>
            <p class="text-cream/40 max-w-lg mx-auto mt-4 aos delay-200">{{ $section?->subtitle ?: 'Cobertura jurídica abrangente com especialistas dedicados em cada área do direito brasileiro.' }}</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($featuredAreas as $area)
                @php
                    $badges = collect(preg_split('/[,|]/', (string) ($area->highlight ?: '')))->map(fn ($item) => trim($item))->filter()->take(3);
                    $imageUrl = site_asset_url($area->image_path);
                    $delay = (($loop->index % 3) + 1) * 100;
                @endphp
                <article class="card-glass overflow-hidden aos delay-{{ $delay }}">
                    @if($imageUrl)
                        <div class="relative overflow-hidden" style="aspect-ratio:16/10;">
                            <img src="{{ $imageUrl }}" alt="{{ $area->title }}" class="w-full h-full object-cover transition-transform duration-500 hover:scale-105">
                            <div class="absolute inset-0" style="background:linear-gradient(180deg,transparent,rgba(11,12,16,.76));"></div>
                        </div>
                    @endif
                    <div class="p-8">
                        <div class="mb-5 w-12 h-12 border border-gold/20 flex items-center justify-center text-gold/80 font-display text-2xl">
                            {{ $area->icon ?: 'ADV' }}
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
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
