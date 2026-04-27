<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuthAppearanceController extends Controller
{
    private const SETTINGS = [
        'auth.panel_eyebrow' => ['label' => 'Tela de login - chamada curta', 'default' => 'Admin Suite', 'sort' => 400],
        'auth.panel_title' => ['label' => 'Tela de login - titulo', 'default' => 'Gestao juridica com acesso seguro.', 'sort' => 401],
        'auth.panel_description' => ['label' => 'Tela de login - descricao', 'default' => 'Painel administrativo para conteudo, agenda, midias, usuarios e permissoes do escritorio.', 'sort' => 402],
        'auth.metric_1_title' => ['label' => 'Tela de login - metrica 1 titulo', 'default' => 'Laravel 13', 'sort' => 410],
        'auth.metric_1_subtitle' => ['label' => 'Tela de login - metrica 1 subtitulo', 'default' => 'Base atual', 'sort' => 411],
        'auth.metric_2_title' => ['label' => 'Tela de login - metrica 2 titulo', 'default' => 'ACL', 'sort' => 412],
        'auth.metric_2_subtitle' => ['label' => 'Tela de login - metrica 2 subtitulo', 'default' => 'Permissoes', 'sort' => 413],
        'auth.metric_3_title' => ['label' => 'Tela de login - metrica 3 titulo', 'default' => 'PWA', 'sort' => 414],
        'auth.metric_3_subtitle' => ['label' => 'Tela de login - metrica 3 subtitulo', 'default' => 'Experiencia app', 'sort' => 415],
    ];

    public function index(): View
    {
        return view('admin.auth-appearance.index', [
            'pageTitle' => 'Tela de login',
            'config' => $this->config(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'panel_eyebrow' => ['nullable', 'string', 'max:80'],
            'panel_title' => ['nullable', 'string', 'max:120'],
            'panel_description' => ['nullable', 'string', 'max:220'],
            'metric_1_title' => ['nullable', 'string', 'max:40'],
            'metric_1_subtitle' => ['nullable', 'string', 'max:60'],
            'metric_2_title' => ['nullable', 'string', 'max:40'],
            'metric_2_subtitle' => ['nullable', 'string', 'max:60'],
            'metric_3_title' => ['nullable', 'string', 'max:40'],
            'metric_3_subtitle' => ['nullable', 'string', 'max:60'],
        ]);

        $payload = [
            'auth.panel_eyebrow' => $validated['panel_eyebrow'] ?? '',
            'auth.panel_title' => $validated['panel_title'] ?? '',
            'auth.panel_description' => $validated['panel_description'] ?? '',
            'auth.metric_1_title' => $validated['metric_1_title'] ?? '',
            'auth.metric_1_subtitle' => $validated['metric_1_subtitle'] ?? '',
            'auth.metric_2_title' => $validated['metric_2_title'] ?? '',
            'auth.metric_2_subtitle' => $validated['metric_2_subtitle'] ?? '',
            'auth.metric_3_title' => $validated['metric_3_title'] ?? '',
            'auth.metric_3_subtitle' => $validated['metric_3_subtitle'] ?? '',
        ];

        foreach (self::SETTINGS as $key => $meta) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => 'auth',
                    'label' => $meta['label'],
                    'type' => 'text',
                    'value' => $payload[$key] ?? '',
                    'json_value' => null,
                    'is_public' => true,
                    'sort_order' => $meta['sort'],
                ],
            );
        }

        $this->clearSettingsCaches();

        activity_log('auth-appearance', 'updated', null, $payload, 'Tela de login atualizada.');

        return response()->json([
            'message' => 'Tela de login atualizada com sucesso.',
            'redirect' => route('admin.auth-appearance.index'),
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
        ] as $key) {
            Cache::forget($key);
        }
    }
}
