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
        $manifest = [
            'name' => setting('pwa.app_name', config('app.name')),
            'short_name' => setting('pwa.short_name', 'Pujani'),
            'description' => setting('pwa.description', 'Portal institucional e administrativo da Pujani Advogados.'),
            'start_url' => setting('pwa.start_path', '/'),
            'scope' => '/',
            'display' => setting('pwa.display', 'standalone'),
            'orientation' => 'portrait',
            'background_color' => setting('pwa.background_color', '#0B0C10'),
            'theme_color' => setting('pwa.theme_color', '#0B0C10'),
            'lang' => 'pt-BR',
            'dir' => 'ltr',
            'categories' => ['business', 'legal', 'productivity'],
            'icons' => array_values(array_filter([
                $this->manifestIcon(setting('pwa.icon_192', 'pwa/icon-192.png'), '192x192'),
                $this->manifestIcon(setting('pwa.icon_512', 'pwa/icon-512.png'), '512x512'),
            ])),
        ];

        return response()->json($manifest, 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
        ]);
    }

    public function serviceWorker(): Response
    {
        $buildManifestPath = public_path('build/manifest.json');
        $buildVersion = file_exists($buildManifestPath)
            ? substr(sha1_file($buildManifestPath), 0, 12)
            : config('app.version', '1');
        $cacheName = 'pujani-site-'.$buildVersion;
        $offlineUrl = route('site.offline');
        $manifestUrl = route('site.manifest');
        $homeUrl = route('site.home');

        $script = <<<JS
const CACHE_NAME = '{$cacheName}';
const OFFLINE_URL = '{$offlineUrl}';
const PRECACHE_URLS = ['{$homeUrl}', '{$offlineUrl}', '{$manifestUrl}'];
const BUILD_PATH_PREFIX = '/build/';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys
                .filter((key) => key !== CACHE_NAME)
                .map((key) => caches.delete(key))
        )).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin || url.pathname.startsWith('/admin')) {
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
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
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
                if (response.ok) {
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
        return view('site.offline', [
            'appName' => config('app.name'),
            'phone' => setting('site.company_phone', '(11) 3456-7890'),
            'email' => setting('site.company_email', 'contato@pujani.adv.br'),
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

    protected function manifestIcon(?string $path, string $sizes): ?array
    {
        $src = $this->resolveAssetUrl($path);

        if (! $src) {
            return null;
        }

        return [
            'src' => $src,
            'sizes' => $sizes,
            'type' => 'image/png',
            'purpose' => 'any maskable',
        ];
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
