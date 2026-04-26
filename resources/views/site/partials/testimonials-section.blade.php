@php
    $section = $sectionBlocks->get('testimonials') ?? null;
@endphp

<section id="{{ $embedded ? 'depoimentos' : 'clientes' }}" class="py-24 lg:py-36 relative overflow-hidden" style="background:#0F1017;">
    <div class="absolute inset-0" style="background:radial-gradient(ellipse 60% 60% at 80% 50%, rgba(196,154,60,0.04), transparent);"></div>
    <div class="max-w-7xl mx-auto px-6 lg:px-16 relative">
        <div class="text-center mb-16">
            <div class="section-label aos mb-4 inline-block">Vozes de Quem Confia</div>
            <h2 class="font-display leading-tight aos delay-100" style="font-size:clamp(2.2rem,5vw,4rem);font-weight:300;">
                {!! $section?->title ?: 'O que dizem nossos <span class="text-gold-gradient font-semibold">clientes</span>' !!}
            </h2>
            @if($section?->subtitle)
                <p class="text-cream/40 max-w-lg mx-auto mt-4 aos delay-200">{{ $section->subtitle }}</p>
            @endif
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($testimonials as $testimonial)
                @php
                    $initials = collect(explode(' ', $testimonial->author_name))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                        ->implode('');
                    $imageUrl = site_asset_url($testimonial->image_path);
                @endphp
                <article class="testimonial-card card-glass p-8 aos delay-{{ (($loop->index % 3) + 1) * 100 }}">
                    <div class="flex gap-1 mb-5"><span class="text-gold" style="font-size:1rem;">&#9733;&#9733;&#9733;&#9733;&#9733;</span></div>
                    <p class="text-cream/65 leading-relaxed mb-6 text-sm">"{{ $testimonial->content }}"</p>
                    <div class="flex items-center gap-3" style="border-top:1px solid rgba(196,154,60,0.15);padding-top:1.25rem;">
                        <div class="w-10 h-10 rounded-full border border-gold/25 overflow-hidden flex items-center justify-center" style="background:rgba(196,154,60,0.08);">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $testimonial->author_name }}" class="w-full h-full object-cover">
                            @else
                                <span class="font-title text-sm text-gold/60">{{ $initials }}</span>
                            @endif
                        </div>
                        <div>
                            <div class="text-cream/80 text-sm font-medium">{{ $testimonial->author_name }}</div>
                            <div class="text-xs text-cream/35">{{ $testimonial->author_role }}{{ $testimonial->company ? ' - '.$testimonial->company : '' }}</div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-20 pt-12" style="border-top:1px solid rgba(196,154,60,0.12);">
            <div class="text-center text-xs text-cream/25 tracking-widest uppercase mb-10">Reconhecimentos e certificações</div>
            <div class="flex flex-wrap items-center justify-center gap-12 lg:gap-16">
                @foreach($recognitions as $recognition)
                    <div class="text-center">
                        <div class="font-title text-sm text-cream/25 tracking-widest">{{ $recognition['title'] }}</div>
                        <div class="text-[0.6rem] text-cream/15 tracking-widest uppercase mt-1">{{ $recognition['subtitle'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
