@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Experiencia de carregamento</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Ative, desative e personalize o preloader do site e do painel com cores, logo, duracao, estilo e CSS proprio.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-hourglass-split"></i>
                    <span>{{ $config['enabled'] ? 'Ativo' : 'Desativado' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form action="{{ route('admin.preloader.update') }}" method="POST" data-ajax-form enctype="multipart/form-data" class="row g-4">
                @csrf
                @method('PUT')

                <div class="col-xl-7">
                    <div class="card admin-table-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Configuracao</div>
                                <h3 class="card-title">Personalizacao completa</h3>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3 admin-premium-form">
                                <div class="col-md-4 form-check ps-5 pt-4">
                                    <input type="checkbox" class="form-check-input" id="preloader_enabled" name="enabled" value="1" @checked($config['enabled'])>
                                    <label class="form-check-label" for="preloader_enabled">Ativar preloader</label>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Exibicao</label>
                                    <select name="scope" class="form-select">
                                        @foreach (['all' => 'Site e painel', 'site' => 'Somente site', 'admin' => 'Somente painel'] as $value => $label)
                                            <option value="{{ $value }}" @selected($config['scope'] === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Estilo</label>
                                    <select name="style" class="form-select">
                                        @foreach (['spinner' => 'Spinner premium', 'bar' => 'Barra progressiva', 'orbit' => 'Orbital', 'pulse' => 'Pulso'] as $value => $label)
                                            <option value="{{ $value }}" @selected($config['style'] === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Marca</label>
                                    <input type="text" name="brand" class="form-control" value="{{ $config['brand'] }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mensagem</label>
                                    <input type="text" name="message" class="form-control" value="{{ $config['message'] }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Fundo</label>
                                    <input type="color" name="background_color" class="form-control form-control-color w-100" value="{{ $config['background_color'] }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Destaque</label>
                                    <input type="color" name="accent_color" class="form-control form-control-color w-100" value="{{ $config['accent_color'] }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Texto</label>
                                    <input type="color" name="text_color" class="form-control form-control-color w-100" value="{{ $config['text_color'] }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Duracao minima (ms)</label>
                                    <input type="number" name="min_duration" class="form-control" min="0" max="6000" value="{{ $config['min_duration'] }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Logo</label>
                                    <input type="file" name="logo" class="form-control" data-filepond data-accepted="image/png,image/jpeg,image/webp,image/svg+xml">
                                    @if($config['logo_url'])
                                        <div class="form-check mt-2">
                                            <input type="checkbox" class="form-check-input" id="remove_logo" name="remove_logo" value="1">
                                            <label class="form-check-label" for="remove_logo">Remover logo atual</label>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-12">
                                    <label class="form-label">CSS personalizado</label>
                                    <textarea name="custom_css" class="form-control admin-code-editor" rows="8" placeholder=".system-preloader-brand { letter-spacing: .16em; }">{{ $config['custom_css'] }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Salvar preloader
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card admin-preloader-preview-card h-100">
                        <div class="card-header">
                            <div>
                                <div class="admin-card-kicker">Preview</div>
                                <h3 class="card-title">Aparencia atual</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-preloader-preview">
                                @include('shared.preloader', ['preloader' => $config + ['enabled' => true], 'preview' => true])
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
