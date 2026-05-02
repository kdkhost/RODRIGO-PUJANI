@extends('admin.layouts.app')

@php
    $backgroundPath = (string) ($config['auth.panel_background_path'] ?? '');
    $backgroundUrl = $backgroundPath !== '' ? site_asset_url($backgroundPath) : '';
@endphp

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Acesso administrativo</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Personalize os textos e a imagem de fundo exibidos nas telas de login, recuperacao e redefinicao de senha.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-window-sidebar"></i>
                    <span>Editavel</span>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form action="{{ route('admin.auth-appearance.update') }}" method="POST" enctype="multipart/form-data" data-ajax-form class="row g-4">
                @csrf
                @method('PUT')

                <div class="col-xl-7">
                    <div class="card admin-table-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Conteudo</div>
                                <h3 class="card-title">Textos da coluna premium</h3>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3 admin-premium-form">
                                <div class="col-md-6">
                                    <label class="form-label">Chamada curta</label>
                                    <input type="text" name="panel_eyebrow" class="form-control" maxlength="80" value="{{ old('panel_eyebrow', $config['auth.panel_eyebrow']) }}" data-auth-preview-source="panel_eyebrow">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Titulo principal</label>
                                    <input type="text" name="panel_title" class="form-control" maxlength="120" value="{{ old('panel_title', $config['auth.panel_title']) }}" data-auth-preview-source="panel_title">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Descricao</label>
                                    <textarea name="panel_description" class="form-control" rows="3" maxlength="220" data-auth-preview-source="panel_description">{{ old('panel_description', $config['auth.panel_description']) }}</textarea>
                                </div>

                                <div class="col-12">
                                    <div class="border rounded-4 p-3 bg-body-tertiary">
                                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                                            <div>
                                                <label class="form-label mb-1">Imagem de fundo da coluna visual</label>
                                                <div class="small text-muted">Arquivo usado nas telas de login, recuperacao e redefinicao de senha.</div>
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
                                                    name="panel_background"
                                                    class="form-control"
                                                    data-filepond
                                                    data-accepted="image/png,image/jpeg,image/webp,image/svg+xml"
                                                    data-current-url="{{ $backgroundUrl }}"
                                                    data-current-name="{{ $backgroundPath !== '' ? basename($backgroundPath) : '' }}"
                                                >
                                                <div class="form-check mt-3">
                                                    <input type="checkbox" id="remove_panel_background" name="remove_panel_background" value="1" class="form-check-input">
                                                    <label class="form-check-label" for="remove_panel_background">Remover imagem personalizada e voltar ao padrao</label>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="small text-uppercase fw-semibold text-muted mb-2">Previa atual</div>
                                                <div class="rounded-4 border overflow-hidden bg-dark-subtle" style="min-height: 168px;">
                                                    @if($backgroundUrl !== '')
                                                        <img src="{{ $backgroundUrl }}" alt="Fundo atual da tela de login" class="w-100 h-100 object-fit-cover" style="min-height: 168px;">
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
                                        <input type="text" name="metric_{{ $index }}_title" class="form-control" maxlength="40" value="{{ old("metric_{$index}_title", $config["auth.metric_{$index}_title"]) }}" data-auth-preview-source="metric_{{ $index }}_title">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Bloco {{ $index }} - subtitulo</label>
                                        <input type="text" name="metric_{{ $index }}_subtitle" class="form-control" maxlength="60" value="{{ old("metric_{$index}_subtitle", $config["auth.metric_{$index}_subtitle"]) }}" data-auth-preview-source="metric_{{ $index }}_subtitle">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Salvar configuracoes
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card admin-table-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Previa</div>
                                <h3 class="card-title">Como aparece no login</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-auth-preview" data-auth-preview-bg style="{{ $backgroundUrl !== '' ? 'background-image:url(\''.$backgroundUrl.'\');background-size:cover;background-position:center;' : '' }}">
                                <div class="admin-auth-preview-brand">
                                    <span>P</span>
                                    <div>
                                        <strong>Pujani</strong>
                                        <small>Advogados</small>
                                    </div>
                                </div>

                                <div class="admin-auth-preview-copy">
                                    <span data-auth-preview-target="panel_eyebrow">{{ $config['auth.panel_eyebrow'] }}</span>
                                    <h2 data-auth-preview-target="panel_title">{{ $config['auth.panel_title'] }}</h2>
                                    <p data-auth-preview-target="panel_description">{{ $config['auth.panel_description'] }}</p>
                                </div>

                                <div class="admin-auth-preview-metrics">
                                    @foreach ([1, 2, 3] as $index)
                                        <div>
                                            <strong data-auth-preview-target="metric_{{ $index }}_title">{{ $config["auth.metric_{$index}_title"] }}</strong>
                                            <span data-auth-preview-target="metric_{{ $index }}_subtitle">{{ $config["auth.metric_{$index}_subtitle"] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @if($backgroundPath !== '')
                                <div class="mt-3 small text-muted">
                                    Arquivo atual: <a href="{{ $backgroundUrl }}" target="_blank" rel="noopener">{{ $backgroundPath }}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-auth-preview-source]').forEach((input) => {
            const target = document.querySelector(`[data-auth-preview-target="${input.dataset.authPreviewSource}"]`);
            if (!target) return;

            input.addEventListener('input', () => {
                target.textContent = input.value;
            });
        });

        const previewBackground = document.querySelector('[data-auth-preview-bg]');
        const backgroundInput = document.querySelector('input[name="panel_background"]');
        const removeInput = document.querySelector('#remove_panel_background');

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
