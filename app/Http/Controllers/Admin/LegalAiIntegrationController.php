<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LegalAiIntegrationController extends Controller
{
    public function index(): View
    {
        $credential = IntegrationCredential::query()->firstOrNew(['service' => 'legal_ai']);
        $configuration = is_array($credential->configuration) ? $credential->configuration : [];

        return view('admin.legal-ai.index', [
            'pageTitle' => 'IA e transcrição',
            'credential' => $credential,
            'configuration' => $configuration,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', Rule::in(['openai_compatible'])],
            'base_url' => ['required', 'url', 'max:500'],
            'chat_model' => ['required', 'string', 'max:120'],
            'transcription_model' => ['required', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'min:10', 'max:2000'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        if (app()->environment('production') && ! str_starts_with(strtolower($validated['base_url']), 'https://')) {
            return response()->json(['message' => 'Em produção, a URL do provedor deve utilizar HTTPS.'], 422);
        }

        $credential = IntegrationCredential::query()->firstOrNew(['service' => 'legal_ai']);
        $credential->fill([
            'enabled' => $request->boolean('enabled'),
            'configuration' => [
                'provider' => $validated['provider'],
                'base_url' => rtrim($validated['base_url'], '/'),
                'chat_model' => $validated['chat_model'],
                'transcription_model' => $validated['transcription_model'],
            ],
            'updated_by' => $request->user()?->id,
        ]);
        if (filled($validated['api_key'] ?? null)) {
            $credential->secret = $validated['api_key'];
        }
        if ($credential->enabled && blank($credential->secret)) {
            return response()->json(['message' => 'Informe a chave do provedor antes de ativar a integração.'], 422);
        }
        $credential->save();

        activity_log('integrations', 'legal_ai_updated', $credential, [
            'enabled' => $credential->enabled,
            'configuration' => $credential->configuration,
            'secret_configured' => filled($credential->secret),
        ], 'Configuração segura de IA e transcrição atualizada.');

        return response()->json(['message' => 'Configuração salva sem expor a chave.', 'reload' => true]);
    }
}
