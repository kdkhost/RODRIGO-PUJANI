@php
    $seo = $page->seoMeta;
    $branding = branding_config();
    $pwa = pwa_config();
    $recaptcha = recaptcha_config();
    $title = $seo?->title ?: ($page->title.' | '.$branding['brand_name']);
    $description = $seo?->description ?: ($page->excerpt ?: 'Pujani Advogados com atuação estratégica, ética e resultado.');
    $keywords = $seo?->keywords ?: 'Pujani Advogados, advocacia, direito, consultoria jurídica, advogado';
    $hashtags = collect($seo?->hashtags)->filter();
    $themeColor = $pwa['theme_color'];
    $backgroundColor = $pwa['background_color'];
    $preloader = preloader_config('site');
    $companyName = $branding['brand_name'];
    $companyPhone = setting('site.company_phone', '(11) 3456-7890');
    $companyEmail = setting('site.company_email', 'contato@pujani.adv.br');
    $companyAddress = setting('site.company_address', 'Av. Paulista, 1842 · Bela Vista · São Paulo/SP');
    $whatsappDigits = preg_replace('/\D+/', '', setting('site.company_whatsapp', '(11) 99876-5432'));
    $currentUrl = $seo?->canonical_url ?: url()->current();

    $resolveAsset = function (?string $path): ?string {
        if (! filled($path)) {
            return null;
        }

        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        if (file_exists(public_path($normalized))) {
            return asset($normalized);
        }

        return asset('storage/'.$normalized);
    };

    $ogImage = $resolveAsset($seo?->og_image_path ?: ($pwa['icon_512_path'] ?: 'pwa/icon-512.png'));
    $icon192 = $branding['favicon_url'] ?: $pwa['icon_192_url'];
    $icon512 = $branding['favicon_url'] ?: $pwa['icon_512_url'];
    $homeUrl = route('site.home');
    $menuPages = collect($publicPages ?? [])
        ->reject(fn ($menuPage) => ($menuPage->is_home ?? false) || ($menuPage->slug ?? null) === 'home')
        ->values();
    $pageUrl = function (?string $slug, ?string $fallback = null) use ($menuPages, $homeUrl): string {
        if (! filled($slug)) {
            return $fallback ?: $homeUrl;
        }

        $menuPage = $menuPages->first(fn ($item) => ($item->slug ?? null) === $slug);

        if ($menuPage) {
            return route('site.show', $menuPage->slug);
        }

        if ($slug === 'home') {
            return $homeUrl;
        }

        return $fallback ?: route('site.show', $slug);
    };
    $contactUrl = $pageUrl('contato', $homeUrl);
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => $seo?->schema_type ?: 'LegalService',
        'name' => $companyName,
        'url' => $currentUrl,
        'description' => $description,
        'telephone' => $companyPhone,
        'email' => $companyEmail,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $companyAddress,
            'addressLocality' => 'São Paulo',
            'addressRegion' => 'SP',
            'addressCountry' => 'BR',
            'postalCode' => setting('site.company_cep', '01310-200'),
        ],
    ];
@endphp
<!DOCTYPE html>
<html
    lang="pt-BR"
    class="scroll-smooth"
    data-recaptcha-enabled="{{ $recaptcha['enabled'] ? '1' : '0' }}"
    data-recaptcha-site-key="{{ $recaptcha['enabled'] ? $recaptcha['site_key'] : '' }}"
    data-pwa-enabled="{{ $pwa['enabled'] ? '1' : '0' }}"
    data-pwa-install-enabled="{{ $pwa['installation_enabled'] ? '1' : '0' }}"
    data-pwa-prompt-enabled="{{ $pwa['install_prompt_enabled'] ? '1' : '0' }}"
    data-pwa-prompt-storage-key="{{ $pwa['prompt_storage_key'] }}"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="keywords" content="{{ $keywords }}">
    <meta name="robots" content="{{ $seo?->noindex ? 'noindex,nofollow' : ($seo?->robots ?: 'index,follow') }}">
    <meta name="theme-color" content="{{ $themeColor }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $pwa['short_name'] }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $companyName }}">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:title" content="{{ $seo?->og_title ?: $title }}">
    <meta property="og:description" content="{{ $seo?->og_description ?: $description }}">
    @if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo?->og_title ?: $title }}">
    <meta name="twitter:description" content="{{ $seo?->og_description ?: $description }}">
    @if($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif
    @if($seo?->canonical_url)<link rel="canonical" href="{{ $seo->canonical_url }}">@endif
    @if($pwa['enabled'] && $pwa['installation_enabled'])
        <link rel="manifest" href="{{ route('site.manifest') }}">
    @endif
    @if($branding['favicon_url'])<link rel="icon" href="{{ $branding['favicon_url'] }}">@endif
    @if($icon192)<link rel="apple-touch-icon" href="{{ $icon192 }}">@endif
    @if(!$branding['favicon_url'] && $icon512)<link rel="icon" href="{{ $icon512 }}" sizes="512x512" type="image/png">@endif
    @foreach($hashtags as $hashtag)
        <meta property="article:tag" content="{{ $hashtag }}">
    @endforeach
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Jost:wght@300;400;500;600&family=Cinzel:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/site.css', 'resources/js/site.js'])
    <style>
        :root { --gold:#C49A3C; --gold-light:#E0BB6A; --gold-pale:#F4E4B8; --ink:#0B0C10; --ink-2:#111318; --ink-3:#1A1C22; --cream:#F0E9DC; --cream-2:#E8DED0; --muted:#7A7468; --border:rgba(196,154,60,0.18); }
        *{margin:0;padding:0;box-sizing:border-box}html{font-size:16px}
        body { background:var(--ink); color:var(--cream); font-family:'Jost',sans-serif; font-weight:300; overflow-x:hidden; cursor:none; }
        .cursor,.cursor-ring{position:fixed;top:0;left:0;pointer-events:none;z-index:99998}.cursor{width:10px;height:10px;background:var(--gold);border-radius:50%;mix-blend-mode:difference;z-index:99999;transition:transform .1s ease}.cursor-ring{width:36px;height:36px;border:1px solid var(--gold);border-radius:50%;opacity:.6;transition:transform .18s ease,width .2s,height .2s,opacity .2s}
        body:hover .cursor{transform:translate(-50%,-50%)}
        body::before{content:'';position:fixed;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");opacity:.03;pointer-events:none;z-index:9999}
        .font-display{font-family:'Cormorant Garamond',serif}.font-title{font-family:'Cinzel',serif}
        .text-gold-gradient{background:linear-gradient(135deg,var(--gold-pale) 0%,var(--gold) 40%,var(--gold-light) 70%,var(--gold-pale) 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .section-line{width:60px;height:1px;background:linear-gradient(90deg,var(--gold),transparent)}
        .divider{height:1px;background:linear-gradient(90deg,transparent,var(--gold),transparent)}
        .aos,.aos-left,.aos-right,.aos-scale{opacity:1;transform:none;transition:opacity .8s ease,transform .8s ease}.site-animations-ready .aos:not(.visible){opacity:0;transform:translateY(30px)}.site-animations-ready .aos-left:not(.visible){opacity:0;transform:translateX(-40px)}.site-animations-ready .aos-right:not(.visible){opacity:0;transform:translateX(40px)}.site-animations-ready .aos-scale:not(.visible){opacity:0;transform:scale(.92)}.aos.visible,.aos-left.visible,.aos-right.visible,.aos-scale.visible{opacity:1;transform:none}
        .delay-100{transition-delay:.1s}.delay-200{transition-delay:.2s}.delay-300{transition-delay:.3s}.delay-400{transition-delay:.4s}.delay-500{transition-delay:.5s}.delay-600{transition-delay:.6s}
        .hero-bg{background:radial-gradient(ellipse 80% 60% at 70% 40%, rgba(196,154,60,0.07) 0%, transparent 60%),radial-gradient(ellipse 50% 80% at 10% 80%, rgba(196,154,60,0.04) 0%, transparent 50%),linear-gradient(160deg, #0B0C10 0%, #0F1017 50%, #0B0C10 100%)}
        .grid-pattern{background-image:linear-gradient(rgba(196,154,60,0.04) 1px, transparent 1px),linear-gradient(90deg, rgba(196,154,60,0.04) 1px, transparent 1px);background-size:60px 60px}
        .card-glass{background:rgba(255,255,255,0.02);border:1px solid var(--border);backdrop-filter:blur(10px);transition:background .3s,border-color .3s,transform .3s}
        .card-glass:hover{background:rgba(196,154,60,0.05);border-color:rgba(196,154,60,0.35);transform:translateY(-4px)}
        .site-hero-proof{border-left:1px solid rgba(196,154,60,.36);background:linear-gradient(90deg,rgba(196,154,60,.08),transparent);backdrop-filter:blur(10px)}
        .site-hero-proof .font-display{color:rgba(240,233,220,.92)}
        .area-icon{width:48px;height:48px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;transition:border-color .3s,background .3s}.card-glass:hover .area-icon{border-color:var(--gold);background:rgba(196,154,60,0.1)}
        .team-card .overlay{opacity:0;transition:opacity .4s}.team-card:hover .overlay{opacity:1}
        .timeline-dot{width:10px;height:10px;border:2px solid var(--gold);border-radius:50%;background:var(--ink);flex-shrink:0}
        .testimonial-card{position:relative}.testimonial-card::before{content:'“';font-family:'Cormorant Garamond',serif;font-size:6rem;line-height:1;color:var(--gold);opacity:.15;position:absolute;top:-10px;left:16px}
        nav{position:fixed;top:0;left:0;right:0;z-index:1000;transition:background .4s,backdrop-filter .4s,border-color .4s}
        nav.scrolled{background:rgba(11,12,16,0.92);backdrop-filter:blur(20px);border-bottom:1px solid var(--border)}
        .nav-link{position:relative;letter-spacing:.12em;text-transform:uppercase;font-size:.72rem;font-weight:500;color:rgba(240,233,220,.65);transition:color .3s}
        .nav-link::after{content:'';position:absolute;bottom:-2px;left:0;right:0;height:1px;background:var(--gold);transform:scaleX(0);transition:transform .3s}
        .nav-link:hover{color:var(--gold-pale)}.nav-link:hover::after{transform:scaleX(1)}
        #scroll-progress{position:fixed;top:0;left:0;height:2px;background:linear-gradient(90deg,var(--gold),var(--gold-light));z-index:99997;width:0%;transition:width .1s}
        .hero-em{position:relative;display:inline-block}.hero-em::after{content:'';position:absolute;bottom:4px;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold),transparent)}
        .form-input{background:rgba(255,255,255,.03);border:1px solid rgba(196,154,60,.2);color:var(--cream);transition:border-color .3s,background .3s;outline:none;font-family:'Jost',sans-serif}.form-input::placeholder{color:var(--muted)}.form-input:focus{border-color:var(--gold);background:rgba(196,154,60,.04)}.form-input:focus + .input-label,.form-input:not(:placeholder-shown) + .input-label{transform:translateY(-100%) scale(.8);color:var(--gold)}
        .btn-primary{position:relative;overflow:hidden;background:transparent;border:1px solid var(--gold);color:var(--gold-pale);letter-spacing:.15em;text-transform:uppercase;font-size:.75rem;font-weight:500;font-family:'Jost',sans-serif;cursor:pointer;transition:color .3s}
        .btn-primary::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,var(--gold),var(--gold-light));transform:translateX(-101%);transition:transform .4s cubic-bezier(0.76,0,0.24,1)}.btn-primary:hover{color:var(--ink)}.btn-primary:hover::before{transform:translateX(0)}.btn-primary span{position:relative;z-index:1}
        .btn-ghost{border:1px solid rgba(240,233,220,.2);color:rgba(240,233,220,.7);letter-spacing:.12em;text-transform:uppercase;font-size:.72rem;font-weight:400;transition:border-color .3s,color .3s;cursor:pointer;background:transparent;font-family:'Jost',sans-serif}
        .btn-ghost:hover{border-color:var(--gold);color:var(--gold-pale)}
        .counter{display:inline-block}.mobile-menu{transform:translateX(100%);transition:transform .4s cubic-bezier(0.76,0,0.24,1)}.mobile-menu.open{transform:translateX(0)}.input-wrap{position:relative}
        .whatsapp-float{position:fixed;bottom:2rem;right:2rem;z-index:9000;width:56px;height:56px;border-radius:50%;background:#25D366;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 20px rgba(37,211,102,.35);transition:transform .3s,box-shadow .3s;animation:pulse-wpp 2.5s infinite}.whatsapp-float:hover{transform:scale(1.1);box-shadow:0 6px 28px rgba(37,211,102,.5)}@keyframes pulse-wpp{0%,100%{box-shadow:0 4px 20px rgba(37,211,102,.35)}50%{box-shadow:0 4px 32px rgba(37,211,102,.6)}}
        .site-pwa-install{display:inline-flex;align-items:center;justify-content:center;gap:.55rem;padding:.85rem 1.1rem;border:1px solid rgba(196,154,60,.22);background:rgba(196,154,60,.08);color:var(--gold-pale);font-size:.76rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;transition:border-color .3s,background .3s,color .3s}
        .site-pwa-install:hover{border-color:rgba(196,154,60,.55);background:rgba(196,154,60,.16);color:var(--gold-light)}
        .site-pwa-install svg{flex:0 0 auto}
        .site-pwa-promo{position:fixed;left:1.25rem;right:1.25rem;bottom:1.25rem;z-index:9400;max-width:460px;margin-left:auto;padding:1.1rem 1.1rem 1rem;border:1px solid rgba(196,154,60,.22);background:linear-gradient(180deg,rgba(17,19,24,.96),rgba(11,12,16,.98));box-shadow:0 24px 64px rgba(0,0,0,.32);backdrop-filter:blur(18px);opacity:0;transform:translateY(18px);pointer-events:none;transition:opacity .28s ease,transform .28s ease}
        .site-pwa-promo.is-visible{opacity:1;transform:translateY(0);pointer-events:auto}
        .site-pwa-promo-badge{display:inline-flex;align-items:center;gap:.45rem;padding:.3rem .65rem;border:1px solid rgba(196,154,60,.18);background:rgba(196,154,60,.1);color:var(--gold-pale);font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
        .site-pwa-promo-head{display:flex;gap:.9rem;align-items:flex-start;margin-top:.9rem}
        .site-pwa-promo-icon{display:inline-flex;width:54px;height:54px;align-items:center;justify-content:center;border:1px solid rgba(196,154,60,.22);background:rgba(255,255,255,.03);flex:0 0 auto}
        .site-pwa-promo-icon img{width:100%;height:100%;object-fit:cover}
        .site-pwa-promo-copy h3{margin:0;font-family:'Cormorant Garamond',serif;font-size:1.9rem;font-weight:600;line-height:1}
        .site-pwa-promo-copy p{margin:.55rem 0 0;color:rgba(240,233,220,.72);line-height:1.65;font-size:.94rem}
        .site-pwa-promo-actions{display:flex;flex-wrap:wrap;gap:.75rem;margin-top:1rem}
        .site-pwa-promo-dismiss{padding:.85rem 1rem;border:1px solid rgba(240,233,220,.14);background:transparent;color:rgba(240,233,220,.74);font-size:.74rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;transition:border-color .3s,color .3s}
        .site-pwa-promo-dismiss:hover{border-color:rgba(196,154,60,.35);color:var(--gold-pale)}
        body.app-installed .site-pwa-install,body.app-installed .site-pwa-promo{display:none!important}
        .section-label{letter-spacing:.3em;text-transform:uppercase;font-size:.65rem;font-weight:500;color:var(--gold)}
        .parallax{will-change:transform}
        ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:var(--ink)}::-webkit-scrollbar-thumb{background:var(--gold);border-radius:2px}
        body.app-installed{padding-top:env(safe-area-inset-top)}body.app-installed .whatsapp-float{bottom:calc(1.5rem + env(safe-area-inset-bottom))}
        @media (max-width:768px){body{cursor:auto}.cursor,.cursor-ring{display:none}.site-pwa-promo{left:1rem;right:1rem;bottom:1rem;max-width:none}}
        @media (max-width:480px){#hero .btn-primary,#hero .btn-ghost{width:calc(100vw - 4rem);max-width:calc(100vw - 4rem);justify-content:center}#hero p{width:calc(100vw - 4rem);max-width:calc(100vw - 4rem)}}
    </style>
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}</script>
</head>
<body>
    @if (session('status'))
        <div data-page-toast data-type="success" data-message="{{ session('status') }}"></div>
    @endif
    @if (session('error'))
        <div data-page-toast data-type="error" data-message="{{ session('error') }}"></div>
    @endif
    @foreach ($errors->all() as $message)
        <div data-page-toast data-type="warning" data-message="{{ $message }}"></div>
    @endforeach
    @if ($preloader['enabled'])
        @include('shared.preloader', ['preloader' => $preloader])
    @endif
    <div id="scroll-progress"></div>
    <div class="cursor" id="cursor"></div>
    <div class="cursor-ring" id="cursor-ring"></div>

    <nav id="navbar" class="px-6 lg:px-16 py-5">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="{{ $homeUrl }}" class="flex items-center gap-3 group">
                <div class="w-8 h-8 border border-gold/40 flex items-center justify-center group-hover:border-gold/80 transition-colors duration-300">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><path d="M12 2L3 7v5c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V7L12 2z"/></svg>
                </div>
                <div>
                    <div class="font-title text-sm tracking-widest text-cream/90 leading-none">PUJANI</div>
                    <div class="text-[0.55rem] tracking-[0.35em] text-gold/70 uppercase leading-none mt-0.5">Advogados</div>
                </div>
            </a>
            <div class="hidden lg:flex items-center gap-10">
                @foreach($menuPages as $menuPage)
                    <a href="{{ $pageUrl($menuPage->slug) }}" class="nav-link">{{ $menuPage->menu_title ?: $menuPage->title }}</a>
                @endforeach
            </div>
            <a href="{{ $contactUrl }}" class="hidden lg:block btn-primary px-7 py-3"><span>Consultoria Gratuita</span></a>
            <button id="menu-btn" class="lg:hidden flex flex-col gap-1.5 p-2" aria-label="Menu">
                <span class="w-6 h-px bg-cream/70 transition-all duration-300" id="h1"></span>
                <span class="w-4 h-px bg-gold transition-all duration-300" id="h2"></span>
                <span class="w-6 h-px bg-cream/70 transition-all duration-300" id="h3"></span>
            </button>
        </div>
    </nav>

    <div class="mobile-menu fixed inset-y-0 right-0 w-80 bg-ink-2 z-50 lg:hidden flex flex-col" id="mobile-menu" style="background:#111318;border-left:1px solid rgba(196,154,60,0.15);">
        <div class="p-8 pt-20 flex flex-col gap-8">
            @foreach($menuPages as $menuPage)
                <a href="{{ $pageUrl($menuPage->slug) }}" class="nav-link text-lg" onclick="closeMobile()">{{ $menuPage->menu_title ?: $menuPage->title }}</a>
            @endforeach
            <a href="{{ $contactUrl }}" class="btn-primary px-6 py-4 text-center mt-4" onclick="closeMobile()"><span>Consultoria Gratuita</span></a>
            @if($pwa['enabled'] && $pwa['installation_enabled'] && $pwa['mobile_install_enabled'])
                <button type="button" class="site-pwa-install text-center mt-2" data-pwa-install onclick="closeMobile()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                    <span>{{ $pwa['mobile_menu_label'] }}</span>
                </button>
            @endif
        </div>
    </div>
    <div id="menu-overlay" class="fixed inset-0 bg-black/50 z-40 hidden" onclick="closeMobile()"></div>

    @yield('content')

    <footer style="background:#070810;border-top:1px solid rgba(196,154,60,0.12);">
        <div class="max-w-7xl mx-auto px-6 lg:px-16 py-16">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-8 h-8 border border-gold/40 flex items-center justify-center">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><path d="M12 2L3 7v5c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V7L12 2z"/></svg>
                        </div>
                        <div>
                            <div class="font-title text-sm tracking-widest text-cream/90">PUJANI ADVOGADOS</div>
                            <div class="text-[0.55rem] tracking-[0.3em] text-gold/50 uppercase">Advocacia de Excelência</div>
                        </div>
                    </div>
                    <p class="text-cream/35 text-sm leading-relaxed max-w-sm">{{ $page->excerpt ?: 'Mais de duas décadas defendendo direitos e construindo resultados com ética, precisão e compromisso.' }}</p>
                    <div class="flex gap-3 mt-6">
                        <a href="{{ setting('site.social_linkedin', '#') }}" target="_blank" rel="noopener" class="w-9 h-9 border border-gold/20 flex items-center justify-center text-gold/50 hover:border-gold/50 hover:text-gold/80 transition-all duration-300" aria-label="LinkedIn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                        </a>
                        <a href="{{ setting('site.social_twitter', '#') }}" target="_blank" rel="noopener" class="w-9 h-9 border border-gold/20 flex items-center justify-center text-gold/50 hover:border-gold/50 hover:text-gold/80 transition-all duration-300" aria-label="Twitter">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
                        </a>
                        <a href="{{ setting('site.social_instagram', '#') }}" target="_blank" rel="noopener" class="w-9 h-9 border border-gold/20 flex items-center justify-center text-gold/50 hover:border-gold/50 hover:text-gold/80 transition-all duration-300" aria-label="Instagram">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" fill="#070810"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5" stroke="#070810" stroke-width="2"/></svg>
                        </a>
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gold/50 tracking-widest uppercase mb-5">Navegação</div>
                    <div class="space-y-3">
                        @foreach($menuPages as $menuPage)
                            <a href="{{ $pageUrl($menuPage->slug) }}" class="block text-sm text-cream/40 hover:text-gold/70 transition-colors">{{ $menuPage->menu_title ?: $menuPage->title }}</a>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gold/50 tracking-widest uppercase mb-5">Legal</div>
                    <div class="space-y-3">
                        <a href="{{ route('site.show', 'politica-de-privacidade') }}" class="block text-sm text-cream/40 hover:text-gold/70 transition-colors">Política de Privacidade</a>
                        <a href="{{ route('site.show', 'termos-de-uso') }}" class="block text-sm text-cream/40 hover:text-gold/70 transition-colors">Termos de Uso</a>
                        <a href="https://www.oab.org.br" target="_blank" rel="noopener" class="block text-sm text-cream/40 hover:text-gold/70 transition-colors">Código de Ética OAB</a>
                        <a href="{{ route('site.show', 'aviso-lgpd') }}" class="block text-sm text-cream/40 hover:text-gold/70 transition-colors">Aviso LGPD</a>
                    </div>
                    <div class="mt-8">
                        <div class="text-xs text-gold/50 tracking-widest uppercase mb-2">OAB/SP</div>
                        <div class="text-xs text-cream/25">{{ setting('site.oab_registration', 'Inscr. 183.472 / 201.887') }}</div>
                    </div>
                    @if($pwa['enabled'] && $pwa['installation_enabled'] && $pwa['footer_install_enabled'])
                        <div class="mt-8">
                            <div class="text-xs text-gold/50 tracking-widest uppercase mb-3">Aplicativo</div>
                            <button type="button" class="site-pwa-install" data-pwa-install>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                                <span>{{ $pwa['footer_label'] }}</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
            <div class="divider"></div>
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 pt-8">
                <div class="text-xs text-cream/20 tracking-widest">© {{ now()->year }} {{ $companyName }} · Todos os direitos reservados</div>
                <div class="text-xs text-cream/15">Este site não constitui consultoria jurídica. Consulte um advogado para orientação específica.</div>
            </div>
        </div>
    </footer>

    @if($whatsappDigits)
        <a href="https://wa.me/{{ $whatsappDigits }}?text=Ol%C3%A1!%20Gostaria%20de%20uma%20consulta%20com%20a%20Pujani%20Advogados." class="whatsapp-float" target="_blank" rel="noopener" aria-label="WhatsApp" title="Fale pelo WhatsApp">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347"/></svg>
        </a>
    @endif
    @if($pwa['enabled'] && $pwa['installation_enabled'] && $pwa['install_prompt_enabled'])
        <div class="site-pwa-promo" data-pwa-promo hidden>
            <span class="site-pwa-promo-badge">{{ $pwa['popup_badge'] }}</span>
            <div class="site-pwa-promo-head">
                <span class="site-pwa-promo-icon">
                    @if($pwa['icon_192_url'])
                        <img src="{{ $pwa['icon_192_url'] }}" alt="{{ $pwa['app_name'] }}">
                    @else
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.6"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                    @endif
                </span>
                <div class="site-pwa-promo-copy">
                    <h3>{{ $pwa['popup_title'] }}</h3>
                    <p>{{ $pwa['popup_description'] }}</p>
                </div>
            </div>
            <div class="site-pwa-promo-actions">
                <button type="button" class="site-pwa-install" data-pwa-install>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                    <span>{{ $pwa['popup_primary_label'] }}</span>
                </button>
                <button type="button" class="site-pwa-promo-dismiss" data-pwa-dismiss>{{ $pwa['popup_secondary_label'] }}</button>
            </div>
        </div>
    @endif
</body>
</html>
