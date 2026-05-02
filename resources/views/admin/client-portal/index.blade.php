@extends('admin.layouts.app')

@php
    $backgroundPath = (string) ($config['portal.login_background_path'] ?? '');
    $backgroundUrl = $backgroundPath !== '' ? site_asset_url($backgroundPath) : '';
@endphp

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Experiencia do cliente</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Personalize a entrada do portal do cliente e defina o comportamento da integracao opcional com o DataJud.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-phone"></i>
                    <span>Portal premium</span>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form action="{{ route('admin.client-portal.update') }}" method="POST" enctype="multipart/form-data" data-ajax-form class="row g-4">
                @csrf
                @method('PUT')

                <div class="col-xl-7">
                    <div class="card admin-table-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Conteudo</div>
                                <h3 class="card-title">Textos e suporte do portal</h3>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3 admin-premium-form">
                                <div class="col-md-6">
                                    <label class="form-label">Chamada curta</label>
                                    <input type="text" name="login_eyebrow" class="form-control" maxlength="80" value="{{ old('login_eyebrow', $config['portal.login_eyebrow']) }}" data-portal-preview-source="login_eyebrow">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Titulo principal</label>
                                    <input type="text" name="login_title" class="form-control" maxlength="120" value="{{ old('login_title', $config['portal.login_title']) }}" data-portal-preview-source="login_title">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Descricao</label>
                                    <textarea name="login_description" class="form-control" rows="3" maxlength="240" data-portal-preview-source="login_description">{{ old('login_description', $config['portal.login_description']) }}</textarea>
                                </div>

                                <div class="col-12">
                                    <div class="border rounded-4 p-3 bg-body-tertiary">
                                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                                            <div>
                                                <label class="form-label mb-1">Imagem de fundo do login do portal</label>
                                                <div class="small text-muted">Imagem usada na entrada do portal do cliente antes do login.</div>
                                            </div>
                                            @if($backgroundUrl !== '')
                                                <a href="{{ $backgroundUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-image me-1"></i>Ver imagem atual
                                                </a>
                                            @endif
                                        </div>

                                        <div class="row g-3 align-items-start">
                                            <div class="col-lg-8">
                                                <input
                                                    type="file"
                                                    name="login_background"
                                                    class="form-control"
                                                    data-filepond
                                                    data-accepted="image/png,image/jpeg,image/webp,image/svg+xml"
                                                    data-current-url="{{ $backgroundUrl }}"
                                                    data-current-name="{{ $backgroundPath !== '' ? basename($backgroundPath) : '' }}"
                                                >
                                                <div class="form-check mt-3">
                                                    <input type="checkbox" id="remove_login_background" name="remove_login_background" value="1" class="form-check-input">
                                                    <label class="form-check-label" for="remove_login_background">Remover imagem personalizada e voltar ao padrao</label>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="small text-uppercase fw-semibold text-muted mb-2">Previa atual</div>
                                                <div class="rounded-4 border overflow-hidden bg-dark-subtle" style="min-height: 168px;">
                                                    @if($backgroundUrl !== '')
                                                        <img src="{{ $backgroundUrl }}" alt="Fundo atual do login do portal" class="w-100 h-100 object-fit-cover" style="min-height: 168px;">
                                                    @else
                                                        <div class="d-flex align-items-center justify-content-center h-100 text-muted px-3 py-5 text-center">
                                                            Nenhuma imagem personalizada cadastrada.
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @foreach ([1, 2, 3] as $index)
                                    <div class="col-md-6">
                                        <label class="form-label">Bloco {{ $index }} - titulo</label>
                                        <input type="text" name="metric_{{ $index }}_title" class="form-control" maxlength="40" value="{{ old("metric_{$index}_title", $config["portal.metric_{$index}_title"]) }}" data-portal-preview-source="metric_{{ $index }}_title">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Bloco {{ $index }} - subtitulo</label>
                                        <input type="text" name="metric_{{ $index }}_subtitle" class="form-control" maxlength="70" value="{{ old("metric_{$index}_subtitle", $config["portal.metric_{$index}_subtitle"]) }}" data-portal-preview-source="metric_{{ $index }}_subtitle">
                                    </div>
                                @endforeach

                                <div class="col-12">
                                    <label class="form-label">Texto de suporte</label>
                                    <textarea name="support_text" class="form-control" rows="3" maxlength="280" data-portal-preview-source="support_text">{{ old('support_text', $config['portal.support_text']) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card admin-table-card mb-4">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Integracao CNJ</div>
                                <h3 class="card-title">DataJud</h3>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="admin-premium-form">
                                <label class="form-label">Chave publica do DataJud</label>
                                <input type="text" name="datajud_api_key" class="form-control" value="{{ old('datajud_api_key', $config['portal.datajud_api_key']) }}" placeholder="Opcional: deixe em branco para usar a chave publica atual do CNJ">
                                <div class="form-text mt-2">
                                    Se este campo ficar vazio, o sistema tenta buscar automaticamente a chave publica vigente na wiki oficial do DataJud.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card admin-table-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Previa</div>
                                <h3 class="card-title">Entrada do portal</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-auth-preview" data-portal-preview-bg style="{{ $backgroundUrl !== '' ? 'background-image:url(\''.$backgroundUrl.'\');background-size:cover;background-position:center;' : '' }}">
                                <div class="admin-auth-preview-brand">
                                    <span>P</span>
                                    <div>
                                        <strong>Pujani</strong>
                                        <small>Portal</small>
                                    </div>
                                </div>

                                <div class="admin-auth-preview-copy">
                                    <span data-portal-preview-target="login_eyebrow">{{ $config['portal.login_eyebrow'] }}</span>
                                    <h2 data-portal-preview-target="login_title">{{ $config['portal.login_title'] }}</h2>
                                    <p data-portal-preview-target="login_description">{{ $config['portal.login_description'] }}</p>
                                </div>

                                <div class="admin-auth-preview-metrics">
                                    @foreach ([1, 2, 3] as $index)
                                        <div>
                                            <strong data-portal-preview-target="metric_{{ $index }}_title">{{ $config["portal.metric_{$index}_title"] }}</strong>
                                            <span data-portal-preview-target="metric_{{ $index }}_subtitle">{{ $config["portal.metric_{$index}_subtitle"] }}</span>
                                        </div>
                                    @endforeach
                                </div>

                                <p class="small text-muted mb-0 mt-3" data-portal-preview-target="support_text">{{ $config['portal.support_text'] }}</p>
                            </div>
                            @if($backgroundPath !== '')
                                <div class="mt-3 small text-muted">
                                    Arquivo atual: <a href="{{ $backgroundUrl }}" target="_blank" rel="noopener">{{ $backgroundPath }}</a>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-transparent d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Salvar portal
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-portal-preview-source]').forEach((input) => {
            const target = document.querySelector(`[data-portal-preview-target="${input.dataset.portalPreviewSource}"]`);
            if (!target) return;

            input.addEventListener('input', () => {
                target.textContent = input.value;
            });
        });

        const previewBackground = document.querySelector('[data-portal-preview-bg]');
        const backgroundInput = document.querySelector('input[name="login_background"]');
        const removeInput = document.querySelector('#remove_login_background');

        if (previewBackground && backgroundInput) {
            backgroundInput.addEventListener('change', (event) => {
                const [file] = event.target.files || [];
                if (!file) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = () => {
                    previewBackground.style.backgroundImage = `url('${reader.result}')`;
                    previewBackground.style.backgroundSize = 'cover';
                    previewBackground.style.backgroundPosition = 'center';
                };
                reader.readAsDataURL(file);
            });
        }

        if (previewBackground && removeInput) {
            removeInput.addEventListener('change', () => {
                if (!removeInput.checked) {
                    return;
                }

                previewBackground.style.backgroundImage = '';
                previewBackground.style.backgroundSize = '';
                previewBackground.style.backgroundPosition = '';
            });
        }
    </script>
@endpush
