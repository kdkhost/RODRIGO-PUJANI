<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\PublicUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PreloaderController extends Controller
{
    private const SETTINGS = [
        'preloader.enabled' => ['label' => 'Ativar preloader', 'type' => 'boolean', 'public' => true],
        'preloader.scope' => ['label' => 'Exibicao', 'type' => 'text', 'public' => true],
        'preloader.style' => ['label' => 'Estilo', 'type' => 'text', 'public' => true],
        'preloader.brand' => ['label' => 'Marca', 'type' => 'text', 'public' => true],
        'preloader.message' => ['label' => 'Mensagem', 'type' => 'text', 'public' => true],
        'preloader.background_color' => ['label' => 'Cor de fundo', 'type' => 'text', 'public' => true],
        'preloader.accent_color' => ['label' => 'Cor principal', 'type' => 'text', 'public' => true],
        'preloader.text_color' => ['label' => 'Cor do texto', 'type' => 'text', 'public' => true],
        'preloader.logo_path' => ['label' => 'Logo', 'type' => 'text', 'public' => true],
        'preloader.min_duration' => ['label' => 'Duracao minima', 'type' => 'text', 'public' => true],
        'preloader.custom_css' => ['label' => 'CSS personalizado', 'type' => 'textarea', 'public' => false],
    ];

    public function index(): View
    {
        return view('admin.preloader.index', [
            'pageTitle' => 'Preloader',
            'config' => preloader_config('admin', false),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'scope' => ['required', Rule::in(['admin', 'site', 'all'])],
            'style' => ['required', Rule::in(['spinner', 'bar', 'orbit', 'pulse'])],
            'brand' => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:160'],
            'background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'min_duration' => ['required', 'integer', 'min:0', 'max:6000'],
            'custom_css' => ['nullable', 'string', 'max:8000'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'preloader.enabled' => $request->boolean('enabled') ? '1' : '0',
            'preloader.scope' => $validated['scope'],
            'preloader.style' => $validated['style'],
            'preloader.brand' => $validated['brand'] ?? '',
            'preloader.message' => $validated['message'] ?? '',
            'preloader.background_color' => $validated['background_color'],
            'preloader.accent_color' => $validated['accent_color'],
            'preloader.text_color' => $validated['text_color'],
            'preloader.min_duration' => (string) $validated['min_duration'],
            'preloader.custom_css' => $validated['custom_css'] ?? '',
        ];

        $currentLogo = (string) setting('preloader.logo_path', '');
        $payload['preloader.logo_path'] = $request->boolean('remove_logo') ? '' : $currentLogo;

        if ($request->boolean('remove_logo') && ! $request->hasFile('logo')) {
            PublicUpload::delete($currentLogo);
        }

        if ($request->hasFile('logo')) {
            $payload['preloader.logo_path'] = $this->storeLogo($request->file('logo'), $currentLogo);
        }

        foreach (self::SETTINGS as $key => $meta) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => 'preloader',
                    'label' => $meta['label'],
                    'type' => $meta['type'],
                    'value' => $payload[$key] ?? '',
                    'json_value' => null,
                    'is_public' => $meta['public'],
                    'sort_order' => 300 + array_search($key, array_keys(self::SETTINGS), true),
                ],
            );
        }

        Cache::forget('site_settings.map.v2');
        Cache::forget('preloader.settings.v1');

        activity_log('preloader', 'updated', null, $payload, 'Preloader atualizado.');

        return response()->json([
            'message' => 'Preloader atualizado com sucesso.',
            'redirect' => route('admin.preloader.index'),
            'closeModal' => false,
        ]);
    }

    private function storeLogo(UploadedFile $file, ?string $currentPath): string
    {
        return PublicUpload::store($file, 'preloader', $currentPath, auth()->id());
    }
}
