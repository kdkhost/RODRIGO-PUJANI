<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\PracticeArea;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Services\RecaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        return $this->renderPage($this->resolveHomePage());
    }

    public function show(string $slug): View
    {
        $page = $this->basePageQuery()
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->renderPage($page);
    }

    public function submitContact(Request $request, RecaptchaService $recaptcha): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'area_interest' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $recaptcha->validateOrFail($request, 'contact_message');

        $data['consent'] = $request->boolean('consent');
        $data['status'] = 'new';
        $data['source_page'] = $request->input('source_page');
        $data['source_url'] = $request->headers->get('referer');
        $data['referrer'] = $request->headers->get('referer');
        $data['ip_address'] = $request->ip();
        $data['user_agent'] = $request->userAgent();

        ContactMessage::query()->create($data);

        return response()->json([
            'message' => 'Sua solicitação foi enviada com sucesso. Em breve entraremos em contato.',
        ]);
    }

    public function sitemap(): Response
    {
        $pages = Schema::hasTable('pages')
            ? Page::query()
                ->where('status', 'published')
                ->orderByDesc('updated_at')
                ->get(['slug', 'is_home', 'updated_at'])
            : collect();

        $urls = collect([
            [
                'loc' => route('site.home'),
                'lastmod' => optional($pages->firstWhere('is_home', true))->updated_at?->toAtomString() ?? now()->toAtomString(),
                'priority' => '1.0',
            ],
        ])->merge(
            $pages
                ->reject(fn (Page $page): bool => $page->is_home || $page->slug === 'home')
                ->map(fn (Page $page): array => [
                    'loc' => route('site.show', $page->slug),
                    'lastmod' => optional($page->updated_at)->toAtomString(),
                    'priority' => '0.8',
                ])
        );

        $xml = view('site.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    public function robots(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /dashboard',
            'Sitemap: '.route('site.sitemap'),
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function manifest(): JsonResponse
    {
        $pwa = pwa_config();
        $startUrl = $this->normalizePwaPath($pwa['start_path'] ?: '/');
        $scope = $this->normalizePwaPath($pwa['scope'] ?: '/');
        $displayOverride = array_values(array_unique(array_filter([
            $pwa['display'],
            'standalone',
            'minimal-ui',
        ])));

        $manifest = [
            'id' => rtrim(route('site.home'), '/').'/?pwa_id=pujani-advogados',
            'name' => $pwa['app_name'],
            'short_name' => $pwa['short_name'],
            'description' => $pwa['description'],
            'start_url' => $startUrl,
            'scope' => $scope,
            'display' => $pwa['display'],
            'display_override' => $displayOverride,
            'orientation' => $pwa['orientation'],
            'background_color' => $pwa['background_color'],
            'theme_color' => $pwa['theme_color'],
            'lang' => 'pt-BR',
            'dir' => 'ltr',
            'categories' => ['business', 'legal', 'productivity'],
            'prefer_related_applications' => false,
            'handle_links' => 'preferred',
            'launch_handler' => [
                'client_mode' => ['navigate-existing', 'auto'],
            ],
            'icons' => array_values(array_filter([
                $this->manifestIcon($pwa['icon_192_path'] ?: 'pwa/icon-192.png', '192x192', 'any'),
                $this->manifestIcon($pwa['icon_512_path'] ?: 'pwa/icon-512.png', '512x512', 'any'),
                $this->manifestIcon($pwa['icon_512_path'] ?: 'pwa/icon-512.png', '512x512', 'maskable'),
            ])),
            'shortcuts' => [
                [
                    'name' => 'Início',
                    'short_name' => 'Início',
                    'description' => 'Abrir o site do escritório',
                    'url' => '/',
                    'icons' => array_values(array_filter([
                        $this->manifestIcon($pwa['icon_192_path'] ?: 'pwa/icon-192.png', '192x192', 'any'),
                    ])),
                ],
                [
                    'name' => 'Portal do cliente',
                    'short_name' => 'Portal',
                    'description' => 'Abrir o portal do cliente',
                    'url' => '/portal-cliente',
                    'icons' => array_values(array_filter([
                        $this->manifestIcon($pwa['icon_192_path'] ?: 'pwa/icon-192.png', '192x192', 'any'),
                    ])),
                ],
            ],
        ];

        return response()->json($manifest, 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function serviceWorker(): Response
    {
        $pwa = pwa_config();

        if (! $pwa['enabled']) {
            $cleanupScript = <<<'JS'
self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.map((key) => caches.delete(key))))
            .then(() => self.registration.unregister())
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'PUJANI_CLEAR_PWA') {
        event.waitUntil(
            caches.keys()
                .then((keys) => Promise.all(keys.map((key) => caches.delete(key))))
                .then(() => self.registration.unregister())
        );
    }
});
JS;

            return response($cleanupScript, 200, [
                'Content-Type' => 'application/javascript; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        }

        $buildManifestPath = public_path('build/manifest.json');
        $buildVersion = file_exists($buildManifestPath)
            ? substr(sha1_file($buildManifestPath), 0, 12)
            : config('app.version', '1');
        $pwaFingerprint = substr(sha1(json_encode([
            $pwa['enabled'],
            $pwa['installation_enabled'],
            $pwa['app_name'],
            $pwa['short_name'],
            $pwa['start_path'],
            $pwa['scope'],
            $pwa['display'],
            $pwa['theme_color'],
            $pwa['background_color'],
            $pwa['icon_192_path'],
            $pwa['icon_512_path'],
        ])), 0, 12);
        $cacheName = 'pujani-pwa-'.$buildVersion.'-'.$pwaFingerprint;
        $offlineUrl = route('site.offline');
        $homeUrl = route('site.home');

        $script = <<<JS
const CACHE_NAME = '{$cacheName}';
const OWN_CACHE_PREFIX = 'pujani-';
const OFFLINE_URL = '{$offlineUrl}';
const PRECACHE_URLS = ['{$homeUrl}', '{$offlineUrl}'];
const BUILD_PATH_PREFIX = '/build/';
const ADMIN_PATH_PREFIX = '/admin';

const clearOwnCaches = () => caches.keys().then((keys) => Promise.all(
    keys
        .filter((key) => key.startsWith(OWN_CACHE_PREFIX) && key !== CACHE_NAME)
        .map((key) => caches.delete(key))
));

const shouldIgnoreRequest = (request, url) => {
    return request.method !== 'GET'
        || url.origin !== self.location.origin
        || url.pathname.startsWith(ADMIN_PATH_PREFIX)
        || url.pathname === '/sw.js'
        || url.pathname === '/manifest.webmanifest'
        || url.pathname.startsWith('/login')
        || url.pathname.startsWith('/logout');
};

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        clearOwnCaches().then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'PUJANI_CLEAR_PWA') {
        event.waitUntil(
            caches.keys()
                .then((keys) => Promise.all(keys.filter((key) => key.startsWith(OWN_CACHE_PREFIX)).map((key) => caches.delete(key))))
                .then(() => self.registration.unregister())
        );
    }

    if (event.data && event.data.type === 'PUJANI_UPDATE_PWA') {
        event.waitUntil(clearOwnCaches());
    }
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (shouldIgnoreRequest(request, url)) {
        return;
    }

    if (url.pathname.startsWith(BUILD_PATH_PREFIX)) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    }

                    return response;
                })
                .catch(() => caches.match(request))
        );

        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const contentType = response.headers.get('content-type') || '';

                    if (response.ok && contentType.includes('text/html')) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    }

                    return response;
                })
                .catch(async () => {
                    const cached = await caches.match(request);
                    return cached || caches.match(OFFLINE_URL);
                })
        );

        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(request).then((response) => {
                const contentType = response.headers.get('content-type') || '';

                if (response.ok && !contentType.includes('text/html')) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                }

                return response;
            });
        })
    );
});
JS;

        return response($script, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    public function offline(): View
    {
        $pwa = pwa_config();

        return view('site.offline', [
            'appName' => $pwa['app_name'],
            'phone' => setting('site.company_phone', '(11) 3456-7890'),
            'email' => setting('site.company_email', 'contato@pujani.adv.br'),
            'offlineTitle' => $pwa['offline_title'],
            'offlineMessage' => $pwa['offline_message'],
            'offlineButtonLabel' => $pwa['offline_button_label'],
        ]);
    }

    public function pwaCleanup(): Response
    {
        $homeUrl = route('site.home');
        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atualização do aplicativo</title>
    <meta name="robots" content="noindex,nofollow">
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0b0c10;color:#f5f1e8;font-family:Arial,sans-serif}
        main{width:min(92vw,520px);padding:32px;border:1px solid rgba(196,154,60,.34);border-radius:18px;background:rgba(255,255,255,.05)}
        h1{margin:0 0 12px;font-size:24px}p{line-height:1.55;color:#d8d1c2}a{color:#c49a3c;font-weight:700}
    </style>
</head>
<body>
<main>
    <h1>Aplicativo atualizado</h1>
    <p>Os dados locais do PWA foram limpos neste navegador. Reinstale o aplicativo pela tela inicial para receber o pacote mais recente.</p>
    <p><a href="{$homeUrl}">Voltar ao site</a></p>
</main>
<script>
(async () => {
    try {
        if ('serviceWorker' in navigator) {
            const registrations = await navigator.serviceWorker.getRegistrations();
            await Promise.all(registrations.map((registration) => {
                registration.active?.postMessage({ type: 'PUJANI_CLEAR_PWA' });
                registration.waiting?.postMessage({ type: 'PUJANI_CLEAR_PWA' });
                registration.installing?.postMessage({ type: 'PUJANI_CLEAR_PWA' });
                return registration.unregister();
            }));
        }

        if ('caches' in window) {
            const keys = await caches.keys();
            await Promise.all(keys.filter((key) => key.startsWith('pujani-')).map((key) => caches.delete(key)));
        }

        window.localStorage?.removeItem('site-pwa-promo-dismissed-v1');
        window.sessionStorage?.clear?.();

        if ('indexedDB' in window && typeof indexedDB.databases === 'function') {
            const databases = await indexedDB.databases();
            await Promise.all(
                databases
                    .map((item) => item?.name)
                    .filter(Boolean)
                    .map((name) => new Promise((resolve) => {
                        const request = indexedDB.deleteDatabase(name);
                        request.onsuccess = () => resolve(true);
                        request.onerror = () => resolve(false);
                        request.onblocked = () => resolve(false);
                    }))
            );
        }
    } catch (error) {
        console.warn('Não foi possível limpar todos os dados locais do PWA.', error);
    }
})();
</script>
</body>
</html>
HTML;

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
            'Clear-Site-Data' => '"cache", "storage"',
        ]);
    }

    protected function renderPage(Page $page): View
    {
        $sectionBlocks = $page->sections
            ->where('is_active', true)
            ->keyBy('section_key');

        return view('site.show', [
            'page' => $page,
            'sectionBlocks' => $sectionBlocks,
            'featuredAreas' => PracticeArea::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'teamMembers' => TeamMember::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'testimonials' => Testimonial::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'siteMetrics' => $this->sectionItems($sectionBlocks, 'metrics', $this->siteMetrics()),
            'recognitions' => $this->sectionItems($sectionBlocks, 'recognitions', $this->recognitions()),
            'timeline' => $this->sectionItems($sectionBlocks, 'timeline', $this->timeline()),
            'valueCards' => $this->sectionItems($sectionBlocks, 'values', $this->valueCards()),
            'differentials' => $this->sectionItems($sectionBlocks, 'differentials', $this->differentials()),
        ]);
    }

    protected function resolveHomePage(): Page
    {
        return $this->basePageQuery()
            ->where(function ($query): void {
                $query->where('is_home', true)->orWhere('slug', 'home');
            })
            ->firstOrFail();
    }

    protected function basePageQuery()
    {
        return Page::query()
            ->where('status', 'published')
            ->with(['sections', 'seoMeta']);
    }

    protected function siteMetrics(): Collection
    {
        return collect([
            ['value' => '20+', 'counter' => 20, 'suffix' => '+', 'label' => 'Anos de mercado'],
            ['value' => '2400+', 'counter' => 2400, 'suffix' => '+', 'label' => 'Casos atendidos'],
            ['value' => '35+', 'counter' => 35, 'suffix' => '+', 'label' => 'Especialistas'],
            ['value' => '98%', 'counter' => 98, 'suffix' => '%', 'label' => 'Satisfação dos clientes'],
        ]);
    }

    protected function recognitions(): Collection
    {
        return collect([
            ['title' => 'OAB/SP', 'subtitle' => 'Certificado'],
            ['title' => 'Análise Advocacia', 'subtitle' => 'Top 50 · 2024'],
            ['title' => 'Chambers Brazil', 'subtitle' => 'Ranked'],
            ['title' => 'IBDP', 'subtitle' => 'Membro associado'],
            ['title' => 'ISO 9001', 'subtitle' => 'Certificado'],
        ]);
    }

    protected function timeline(): Collection
    {
        return collect([
            ['year' => '2003', 'text' => 'Fundação do escritório em São Paulo com foco em Direito Civil e Empresarial.'],
            ['year' => '2010', 'text' => 'Expansão com novos sócios e consolidação das áreas Tributária e Trabalhista.'],
            ['year' => '2024', 'text' => 'Reconhecimento nacional entre os escritórios mais admirados em atuação estratégica.'],
        ]);
    }

    protected function valueCards(): Collection
    {
        return collect([
            [
                'title' => 'Ética e integridade',
                'text' => 'Cada caso é conduzido com transparência, clareza e rigor técnico compatíveis com a responsabilidade da advocacia.',
                'icon' => 'shield',
            ],
            [
                'title' => 'Agilidade e precisão',
                'text' => 'Prazos respeitados, estratégia definida e resposta objetiva nos momentos mais críticos do cliente.',
                'icon' => 'clock',
            ],
            [
                'title' => 'Atendimento humanizado',
                'text' => 'Por trás de cada processo existe uma pessoa. O atendimento combina acolhimento, postura e comunicação clara.',
                'icon' => 'users',
            ],
        ]);
    }

    protected function differentials(): Collection
    {
        return collect([
            [
                'title' => 'Atendimento 100% personalizado',
                'text' => 'Cada cliente recebe condução próxima de advogado responsável pelo caso.',
            ],
            [
                'title' => 'Transparência de honorários',
                'text' => 'Propostas claras, contrato detalhado e previsibilidade financeira.',
            ],
            [
                'title' => 'Presença digital completa',
                'text' => 'Fluxos preparados para acompanhamento remoto, comunicação ágil e captação online.',
            ],
            [
                'title' => 'Equipe multidisciplinar',
                'text' => 'Casos complexos contam com especialistas de áreas complementares trabalhando em conjunto.',
            ],
        ]);
    }

    protected function sectionItems(Collection $sectionBlocks, string $key, Collection $fallback): Collection
    {
        $items = data_get($sectionBlocks->get($key)?->data, 'items');

        return is_array($items) && $items !== []
            ? collect($items)
            : $fallback;
    }

    protected function normalizePwaPath(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '/';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            $parsedPath = parse_url($path, PHP_URL_PATH) ?: '/';
            $query = parse_url($path, PHP_URL_QUERY);

            return $parsedPath.($query ? '?'.$query : '');
        }

        return Str::startsWith($path, '/') ? $path : '/'.$path;
    }

    protected function manifestIcon(?string $path, string $sizes, string $purpose = 'any'): ?array
    {
        $src = $this->resolveAssetUrl($path);

        if (! $src) {
            return null;
        }

        return [
            'src' => $this->versionedAssetUrl($src, $path),
            'sizes' => $sizes,
            'type' => 'image/png',
            'purpose' => $purpose,
        ];
    }

    protected function versionedAssetUrl(string $src, ?string $path): string
    {
        if (! filled($path) || Str::contains($src, '?') || Str::startsWith($path, ['http://', 'https://'])) {
            return $src;
        }

        $normalized = ltrim($path, '/');
        $publicPath = file_exists(public_path($normalized))
            ? public_path($normalized)
            : public_path('storage/'.$normalized);

        $version = file_exists($publicPath) ? filemtime($publicPath) : substr(sha1((string) $path), 0, 12);

        return $src.'?v='.$version;
    }

    protected function resolveAssetUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        if (file_exists(public_path($normalized))) {
            return asset($normalized);
        }

        return asset('storage/'.$normalized);
    }
}
