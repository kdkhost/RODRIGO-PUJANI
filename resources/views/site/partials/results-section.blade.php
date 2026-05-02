@php
    $section = $sectionBlocks->get('results') ?? null;
    $caseUrl = route('site.show', 'contato');
@endphp

<section id="{{ $embedded ? 'numeros' : 'resultados' }}" class="py-24 lg:py-36 relative overflow-hidden" style="background:#0F1017;">
    <div class="absolute left-0 top-0 w-full h-full" style="background:radial-gradient(ellipse 70% 50% at 30% 60%, rgba(196,154,60,0.06), transparent);"></div>
    <div class="max-w-7xl mx-auto px-6 lg:px-16 relative">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 mb-24">
            @foreach($siteMetrics as $metric)
                <div class="text-center aos delay-{{ $loop->index * 100 }}">
                    <div class="font-display font-semibold mb-2" style="font-size:clamp(2.5rem,7vw,5rem);line-height:1.1;">
                        <span class="counter text-gold-gradient" data-target="{{ $metric['counter'] }}">0</span><span class="text-gold-gradient">{{ $metric['suffix'] }}</span>
                    </div>
                    <div class="divider mb-4 mx-auto w-12"></div>
                    <div class="text-cream/40 text-[0.65rem] tracking-[0.2em] uppercase">{{ $metric['label'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <div class="section-label aos mb-6">â€” Por Que Nos Escolher</div>
                <h2 class="font-display leading-tight mb-8 aos delay-100" style="font-size:clamp(2rem,4vw,3.2rem);font-weight:300;">
                    {!! $section?->title ?: 'EstratÃ©gia jurÃ­dica que<br><span class="text-gold-gradient font-semibold">transforma resultados</span>' !!}
                </h2>
                <div class="text-cream/50 leading-relaxed mb-8 aos delay-200">
                    {!! $section?->content ?: e($page->excerpt ?: 'NÃ£o apenas representamos. ConstruÃ­mos estratÃ©gias personalizadas que consideram cada detalhe do caso, o contexto do cliente e os objetivos de negÃ³cio.') !!}
                </div>
                <a href="{{ $caseUrl }}" class="btn-primary px-8 py-4 inline-block aos delay-300"><span>Iniciar Meu Caso</span></a>
            </div>

            <div class="space-y-4">
                @foreach($differentials as $item)
                    <div class="flex gap-5 items-start card-glass p-6 aos delay-{{ (($loop->index + 1) * 100) }}">
                        <div class="w-10 h-10 border border-gold/30 flex items-center justify-center flex-shrink-0">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <div>
                            <div class="font-semibold text-cream/90 mb-1">{{ $item['title'] }}</div>
                            <div class="text-sm text-cream/45">{{ $item['text'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
