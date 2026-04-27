@php
    $section = $sectionBlocks->get('about') ?? null;
@endphp

<section id="{{ $embedded ? 'sobre' : 'sobre-escritorio' }}" class="py-24 lg:py-36 relative overflow-hidden" style="background:#0F1017;">
    <div class="absolute right-0 top-0 w-1/2 h-full" style="background:radial-gradient(ellipse 60% 80% at 90% 30%, rgba(196,154,60,0.04), transparent);"></div>
    <div class="max-w-7xl mx-auto px-6 lg:px-16">
        <div class="grid lg:grid-cols-2 gap-16 lg:gap-24 items-center">
            <div>
                <div class="section-label aos mb-6">— Quem Somos</div>
                <blockquote class="font-display leading-tight mb-10 aos delay-100" style="font-size:clamp(2rem,4vw,3.2rem);font-weight:300;font-style:italic;border-left:2px solid var(--gold);padding-left:1.5rem;">
                    {{ $section?->title ?: '"O direito não é apenas uma profissão — é um compromisso com a justiça e com as pessoas."' }}
                </blockquote>
                <div class="text-cream/50 leading-relaxed mb-10 aos delay-200">
                    {!! $section?->content ?: ($page->body ?: '<p>Fundado com a missão de democratizar o acesso à advocacia de alto nível, o escritório Pujani Advogados reúne profissionais especializados com formação nas melhores instituições do país.</p>') !!}
                </div>

                <div class="space-y-6 aos delay-300">
                    @foreach($timeline as $item)
                        <div class="flex gap-4 items-start">
                            <div class="timeline-dot mt-1.5"></div>
                            <div>
                                <div class="text-xs text-gold/60 tracking-widest mb-1">{{ $item['year'] }}</div>
                                <div class="text-cream/80 text-sm">{{ $item['text'] }}</div>
                            </div>
                        </div>
                        @if(! $loop->last)
                            <div class="w-px h-6 bg-gold/20 ml-1"></div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 aos-right delay-100">
                @foreach($valueCards as $card)
                    <div class="card-glass p-7">
                        <div class="text-gold mb-3">
                            @if(($card['icon'] ?? '') === 'shield')
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L3 7v5c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V7L12 2z"/></svg>
                            @elseif(($card['icon'] ?? '') === 'clock')
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            @else
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87m-4-12a4 4 0 0 1 0 7.75"/></svg>
                            @endif
                        </div>
                        <h3 class="font-display text-xl font-semibold text-cream/90 mb-2">{{ $card['title'] }}</h3>
                        <p class="text-cream/45 text-sm leading-relaxed">{{ $card['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

@if($embedded)
    <div class="divider mx-auto" style="max-width:1200px;"></div>
@endif
