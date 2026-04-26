<section id="{{ $embedded ? 'equipe' : 'nossa-equipe' }}" class="py-24 lg:py-36" style="background:#0B0C10;">
    <div class="max-w-7xl mx-auto px-6 lg:px-16">
        <div class="text-center mb-16">
            <div class="section-label aos mb-4 inline-block">— Profissionais de Alto Nível</div>
            <h2 class="font-display leading-tight aos delay-100" style="font-size:clamp(2.2rem,5vw,4rem);font-weight:300;">Nossa <span class="text-gold-gradient font-semibold">Equipe</span></h2>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($teamMembers as $member)
                @php
                    $initials = collect(explode(' ', $member->name))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                        ->implode('');
                @endphp
                <article class="team-card relative overflow-hidden aos delay-{{ (($loop->index % 4) + 1) * 100 }}">
                    <div class="relative" style="aspect-ratio:3/4;background:#161820;border:1px solid rgba(196,154,60,0.12);">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-24 h-24 rounded-full border-2 border-gold/30 flex items-center justify-center" style="background:rgba(196,154,60,0.08);">
                                <span class="font-title text-3xl text-gold/60">{{ $initials }}</span>
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-6">
                            <h4 class="font-display text-lg font-semibold text-cream/90">{{ $member->name }}</h4>
                            <div class="text-xs text-gold/60 tracking-widest uppercase mt-1">{{ $member->role }}</div>
                            <div class="text-xs text-cream/35 mt-2">{{ $member->oab_number }}</div>
                        </div>
                        <div class="overlay absolute inset-0 flex flex-col items-center justify-center p-6 text-center" style="background:rgba(11,12,16,0.92);border:1px solid rgba(196,154,60,0.3);">
                            <div class="font-display text-2xl font-semibold text-cream/90 mb-1">{{ $member->name }}</div>
                            <div class="text-xs text-gold/60 tracking-widest uppercase mb-4">{{ $member->role }}</div>
                            <p class="text-xs text-cream/50 leading-relaxed mb-5">{!! strip_tags($member->bio) !!}</p>
                            <div class="flex gap-3 justify-center">
                                @if($member->linkedin_url)
                                    <a href="{{ $member->linkedin_url }}" target="_blank" rel="noopener" class="w-8 h-8 border border-gold/30 flex items-center justify-center text-gold/60 hover:border-gold hover:text-gold transition-colors">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                                    </a>
                                @endif
                                @if($member->email)
                                    <a href="mailto:{{ $member->email }}" class="w-8 h-8 border border-gold/30 flex items-center justify-center text-gold/60 hover:border-gold hover:text-gold transition-colors">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
