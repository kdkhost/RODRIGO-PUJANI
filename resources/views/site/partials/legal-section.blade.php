<section class="py-24 lg:py-36" style="background:#0F1017;">
    <div class="max-w-5xl mx-auto px-6 lg:px-16">
        <div class="card-glass p-8 lg:p-12">
            <div class="prose prose-lg prose-invert max-w-none text-cream/70">{!! $page->body !!}</div>
            @foreach($page->sections as $section)
                <div class="divider my-10"></div>
                <div class="section-label mb-3">— {{ $section->section_key }}</div>
                <h3 class="font-display text-3xl mb-4">{{ $section->title }}</h3>
                @if($section->subtitle)
                    <p class="text-cream/50 mb-4">{{ $section->subtitle }}</p>
                @endif
                <div class="prose prose-invert max-w-none text-cream/70">{!! $section->content !!}</div>
            @endforeach
        </div>
    </div>
</section>
