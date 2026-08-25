@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Configuração protegida</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Configure um provedor compatível para resumos e transcrições. A chave é criptografada em repouso e nunca reaparece na tela.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card admin-premium-card">
                <div class="card-body p-4">
                    <form action="{{ route('admin.legal-ai.update') }}" method="POST" data-ajax-form>
                        @csrf
                        @method('PUT')
                        <div class="row g-3 admin-premium-form">
                            <div class="col-md-4">
                                <label class="form-label">Provedor</label>
                                <select name="provider" class="form-select" required>
                                    <option value="openai_compatible" @selected(($configuration['provider'] ?? 'openai_compatible') === 'openai_compatible')>API compatível com OpenAI</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">URL-base HTTPS</label>
                                <input type="url" name="base_url" class="form-control" value="{{ old('base_url', $configuration['base_url'] ?? 'https://api.openai.com/v1') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Modelo de texto</label>
                                <input type="text" name="chat_model" class="form-control" value="{{ old('chat_model', $configuration['chat_model'] ?? 'gpt-4.1-mini') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Modelo de transcrição</label>
                                <input type="text" name="transcription_model" class="form-control" value="{{ old('transcription_model', $configuration['transcription_model'] ?? 'whisper-1') }}" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Chave da API</label>
                                <input type="password" name="api_key" class="form-control" autocomplete="new-password" placeholder="{{ filled($credential->secret) ? 'Chave já configurada — deixe vazio para preservar' : 'Informe a chave' }}">
                                <div class="form-text">O valor não é registrado no log nem devolvido ao navegador.</div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="enabled" value="1" id="legal-ai-enabled" @checked(old('enabled', $credential->enabled))>
                                    <label class="form-check-label" for="legal-ai-enabled">Integração ativa</label>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-warning mt-4 mb-0">
                            Conteúdo jurídico gerado permanece em rascunho e exige revisão, aprovação e publicação humana separadas.
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-shield-lock me-1"></i>Salvar com segurança</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
