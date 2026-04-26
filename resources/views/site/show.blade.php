@extends('site.layouts.app')

@php
    $template = $page->template ?: 'default';
    $title = $page->hero_title ?: $page->title;
    $subtitle = $page->hero_subtitle ?: $page->excerpt;
    $isHome = $template === 'home';
@endphp

@section('content')
    @if($isHome)
        @include('site.partials.home-hero', [
            'page' => $page,
            'siteMetrics' => $siteMetrics,
        ])

        @include('site.partials.about-section', [
            'page' => $page,
            'timeline' => $timeline,
            'valueCards' => $valueCards,
            'embedded' => true,
        ])

        @include('site.partials.areas-section', [
            'featuredAreas' => $featuredAreas,
            'embedded' => true,
        ])

        @include('site.partials.results-section', [
            'page' => $page,
            'siteMetrics' => $siteMetrics,
            'differentials' => $differentials,
            'embedded' => true,
        ])

        @include('site.partials.team-section', [
            'teamMembers' => $teamMembers,
            'embedded' => true,
        ])

        @include('site.partials.testimonials-section', [
            'testimonials' => $testimonials,
            'recognitions' => $recognitions,
            'embedded' => true,
        ])

        @include('site.partials.contact-section', [
            'page' => $page,
            'embedded' => true,
        ])
    @else
        <section class="hero-bg grid-pattern relative min-h-[60vh] flex flex-col justify-center overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-px" style="background:linear-gradient(180deg,transparent,rgba(196,154,60,0.3),transparent);"></div>
            <div class="absolute right-0 top-0 bottom-0 w-px" style="background:linear-gradient(180deg,transparent,rgba(196,154,60,0.15),transparent);"></div>
            <div class="absolute top-1/4 right-8 lg:right-24 w-40 h-40 lg:w-64 lg:h-64 border border-gold/8 rotate-12 parallax" data-speed="0.3"></div>
            <div class="absolute top-1/4 right-8 lg:right-24 w-40 h-40 lg:w-64 lg:h-64 border border-gold/5 -rotate-6 parallax" data-speed="0.5"></div>
            <div class="max-w-7xl mx-auto px-6 lg:px-16 pt-32 pb-20 w-full relative">
                <div class="grid lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-7">
                        <div class="section-label aos mb-8">— {{ $page->menu_title ?: $page->title }}</div>
                        <h1 class="font-display leading-none mb-6 aos delay-100" style="font-size:clamp(3rem,7vw,6rem);font-weight:300;">{!! nl2br(e($title)) !!}</h1>
                        @if($subtitle)
                            <p class="text-cream/50 max-w-2xl leading-relaxed mb-10 aos delay-200" style="font-size:1.05rem;">{{ $subtitle }}</p>
                        @endif
                        <div class="flex flex-wrap gap-4 aos delay-300">
                            <a href="{{ $page->hero_cta_url ?: route('site.show', 'contato') }}" class="btn-primary px-8 py-4 inline-flex items-center gap-3"><span>{{ $page->hero_cta_label ?: 'Falar com um advogado' }}</span></a>
                            <a href="{{ route('site.show', 'areas-de-atuacao') }}" class="btn-ghost px-8 py-4 inline-block">Áreas de Atuação</a>
                        </div>
                    </div>
                    <div class="lg:col-span-5">
                        <div class="card-glass p-8 lg:p-10 aos-right delay-100">
                            <div class="font-display text-2xl mb-4">Advocacia estratégica</div>
                            <p class="text-cream/50 leading-relaxed">{{ $page->excerpt ?: 'Atuação orientada por estratégia jurídica, leitura precisa do risco e compromisso com resultado.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @switch($template)
            @case('about')
                @include('site.partials.about-section', ['page' => $page, 'timeline' => $timeline, 'valueCards' => $valueCards, 'embedded' => false])
                @break

            @case('practice-areas')
                @include('site.partials.areas-section', ['featuredAreas' => $featuredAreas, 'embedded' => false])
                @break

            @case('results')
                @include('site.partials.results-section', ['page' => $page, 'siteMetrics' => $siteMetrics, 'differentials' => $differentials, 'embedded' => false])
                @break

            @case('team')
                @include('site.partials.team-section', ['teamMembers' => $teamMembers, 'embedded' => false])
                @break

            @case('testimonials')
                @include('site.partials.testimonials-section', ['testimonials' => $testimonials, 'recognitions' => $recognitions, 'embedded' => false])
                @break

            @case('contact')
                @include('site.partials.contact-section', ['page' => $page, 'embedded' => false])
                @break

            @case('legal')
            @default
                @include('site.partials.legal-section', ['page' => $page])
        @endswitch
    @endif
@endsection
