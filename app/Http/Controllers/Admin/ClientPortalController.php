<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\PublicUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ClientPortalController extends Controller
{
    private const SETTINGS = [
        'portal.login_eyebrow' => ['label' => 'Portal do cliente - chamada curta', 'default' => 'Acompanhamento digital', 'type' => 'text', 'sort' => 500, 'public' => true],
        'portal.login_title' => ['label' => 'Portal do cliente - titulo', 'default' => 'Acompanhe seus processos com clareza e seguranca.', 'type' => 'text', 'sort' => 501, 'public' => true],
        'portal.login_description' => ['label' => 'Portal do cliente - descricao', 'default' => 'Consulte movimentacoes, prazos relevantes e documentos compartilhados pelo escritorio em um ambiente reservado.', 'type' => 'text', 'sort' => 502, 'public' => true],
        'portal.login_background_path' => ['label' => 'Portal do cliente - imagem de fundo', 'default' => '', 'type' => 'text', 'sort' => 503, 'public' => true],
        'portal.metric_1_title' => ['label' => 'Portal do cliente - bloco 1 titulo', 'default' => 'Processos', 'type' => 'text', 'sort' => 510, 'public' => true],
        'portal.metric_1_subtitle' => ['label' => 'Portal do cliente - bloco 1 subtitulo', 'default' => 'Historico organizado', 'type' => 'text', 'sort' => 511, 'public' => true],
        'portal.metric_2_title' => ['label' => 'Portal do cliente - bloco 2 titulo', 'default' => 'Documentos', 'type' => 'text', 'sort' => 512, 'public' => true],
        'portal.metric_2_subtitle' => ['label' => 'Portal do cliente - bloco 2 subtitulo', 'default' => 'Arquivos compartilhados', 'type' => 'text', 'sort' => 513, 'public' => true],
        'portal.metric_3_title' => ['label' => 'Portal do cliente - bloco 3 titulo', 'default' => 'Prazos', 'type' => 'text', 'sort' => 514, 'public' => true],
        'portal.metric_3_subtitle' => ['label' => 'Portal do cliente - bloco 3 subtitulo', 'default' => 'Visao objetiva do caso', 'type' => 'text', 'sort' => 515, 'public' => true],
        'portal.support_text' => ['label' => 'Portal do cliente - suporte', 'default' => 'Para suporte de acesso, fale com a equipe do escritorio pelo telefone ou WhatsApp cadastrado.', 'type' => 'textarea', 'sort' => 516, 'public' => true],
        'portal.datajud_api_key' => ['label' => 'Portal do cliente - chave DataJud', 'default' => '', 'type' => 'text', 'sort' => 517, 'public' => false],
    ];

    public function index(): View
    {
        return view('admin.client-portal.index', [
            'pageTitle' => 'Portal do cliente',
            'config' => $this->config(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login_eyebrow' => ['nullable', 'string', 'max:80'],
            'login_title' => ['nullable', 'string', 'max:120'],
            'login_description' => ['nullable', 'string', 'max:240'],
            'metric_1_title' => ['nullable', 'string', 'max:40'],
            'metric_1_subtitle' => ['nullable', 'string', 'max:70'],
            'metric_2_title' => ['nullable', 'string', 'max:40'],
            'metric_2_subtitle' => ['nullable', 'string', 'max:70'],
            'metric_3_title' => ['nullable', 'string', 'max:40'],
            'metric_3_subtitle' => ['nullable', 'string', 'max:70'],
            'support_text' => ['nullable', 'string', 'max:280'],
            'datajud_api_key' => ['nullable', 'string', 'max:255'],
            'login_background' => ['nullable', 'image', 'max:6144'],
            'remove_login_background' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'portal.login_eyebrow' => $validated['login_eyebrow'] ?? '',
            'portal.login_title' => $validated['login_title'] ?? '',
            'portal.login_description' => $validated['login_description'] ?? '',
            'portal.metric_1_title' => $validated['metric_1_title'] ?? '',
            'portal.metric_1_subtitle' => $validated['metric_1_subtitle'] ?? '',
            'portal.metric_2_title' => $validated['metric_2_title'] ?? '',
            'portal.metric_2_subtitle' => $validated['metric_2_subtitle'] ?? '',
            'portal.metric_3_title' => $validated['metric_3_title'] ?? '',
            'portal.metric_3_subtitle' => $validated['metric_3_subtitle'] ?? '',
            'portal.support_text' => $validated['support_text'] ?? '',
            'portal.datajud_api_key' => $validated['datajud_api_key'] ?? '',
        ];

        $currentBackground = (string) setting('portal.login_background_path', '');
        $payload['portal.login_background_path'] = $request->boolean('remove_login_background') ? '' : $currentBackground;

        if ($request->boolean('remove_login_background') && ! $request->hasFile('login_background')) {
            PublicUpload::delete($currentBackground);
        }

        if ($request->hasFile('login_background')) {
            $payload['portal.login_background_path'] = $this->storeBackground($request->file('login_background'), $currentBackground);
        }

        foreach (self::SETTINGS as $key => $meta) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => 'portal',
                    'label' => $meta['label'],
                    'type' => $meta['type'],
                    'value' => $payload[$key] ?? '',
                    'json_value' => null,
                    'is_public' => $meta['public'],
                    'sort_order' => $meta['sort'],
                ],
            );
        }

        $this->clearSettingsCaches();

        activity_log('client_portal', 'updated', null, $payload, 'Configuracoes do portal do cliente atualizadas.');

        return response()->json([
            'message' => 'Portal do cliente atualizado com sucesso.',
            'redirect' => route('admin.client-portal.index'),
            'closeModal' => false,
        ]);
    }

    private function config(): array
    {
        return collect(self::SETTINGS)
            ->mapWithKeys(fn (array $meta, string $key): array => [$key => (string) setting($key, $meta['default'])])
            ->all();
    }

    private function clearSettingsCaches(): void
    {
        foreach ([
            'site_settings.all',
            'site_settings.all.v2',
            'site_settings.map',
            'site_settings.map.v2',
            'portal.datajud.public_key',
        ] as $key) {
            Cache::forget($key);
        }
    }

    private function storeBackground(UploadedFile $file, ?string $currentPath): string
    {
        return PublicUpload::store($file, 'portal-backgrounds', $currentPath, auth()->id());
    }
}
