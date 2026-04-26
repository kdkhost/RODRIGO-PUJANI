@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header">
        <div class="container-fluid d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <div>
                <h1 class="mb-1">{{ $pageTitle }}</h1>
                <div class="text-muted">Edicao controlada de arquivos criticos com backup automatico a cada alteracao.</div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="alert alert-warning d-flex gap-3 align-items-start">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div>
                    <strong>Acesso restrito a administracao avancada.</strong>
                    <div class="small mt-1">
                        Alteracoes no <code>.env</code> afetam conexoes, filas, cache e ambiente. Alteracoes no <code>.htaccess</code>
                        podem indisponibilizar o site se as regras ficarem invalidas.
                    </div>
                </div>
            </div>

            <div class="row g-4">
                @foreach ($files as $file)
                    <div class="col-12" id="arquivo-{{ $file['key'] }}">
                        <div class="card">
                            <div class="card-header d-flex flex-wrap gap-3 justify-content-between align-items-center">
                                <div>
                                    <h3 class="card-title mb-1">{{ $file['label'] }}</h3>
                                    <div class="small text-muted">{{ $file['description'] }}</div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 small text-muted text-end">
                                    <span><strong>Caminho:</strong> {{ $file['path'] }}</span>
                                    <span><strong>Status:</strong> {{ $file['exists'] ? 'Existente' : 'Nao encontrado' }}</span>
                                    <span><strong>Permissao:</strong> {{ $file['writable'] ? 'Gravavel' : 'Somente leitura' }}</span>
                                    <span><strong>Atualizado:</strong> {{ $file['updated_at'] ?? 'Ainda nao criado' }}</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('admin.system-files.update', $file['key']) }}" method="POST" data-ajax-form>
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3">
                                        <label class="form-label">Conteudo atual</label>
                                        <textarea
                                            name="content"
                                            rows="{{ $file['rows'] }}"
                                            class="form-control admin-code-editor"
                                            spellcheck="false">{{ old('content', $file['content']) }}</textarea>
                                        <div class="invalid-feedback d-block" data-error-for="content"></div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        <button type="submit" class="btn btn-primary" @disabled(! $file['writable'])>
                                            <i class="bi bi-save me-1"></i>Salvar {{ $file['label'] }}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="card-footer bg-transparent">
                                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-2">
                                    <strong>Backups recentes</strong>
                                    <span class="small text-muted">Cada salvamento gera um backup local em <code>storage/app/system-file-backups</code>.</span>
                                </div>

                                @if ($file['backups'] === [])
                                    <div class="text-muted small">Nenhum backup disponivel para este arquivo.</div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                            <tr>
                                                <th>Backup</th>
                                                <th>Tamanho</th>
                                                <th>Data</th>
                                                <th class="text-end">Acao</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ($file['backups'] as $backup)
                                                <tr>
                                                    <td><code>{{ $backup['name'] }}</code></td>
                                                    <td>{{ number_format($backup['size'] / 1024, 2, ',', '.') }} KB</td>
                                                    <td>{{ $backup['updated_at'] }}</td>
                                                    <td class="text-end">
                                                        <form action="{{ route('admin.system-files.restore', $file['key']) }}" method="POST" class="d-inline" data-ajax-form>
                                                            @csrf
                                                            <input type="hidden" name="backup_name" value="{{ $backup['name'] }}">
                                                            <button
                                                                type="submit"
                                                                class="btn btn-sm btn-outline-secondary"
                                                                data-confirm-submit="true"
                                                                data-confirm-title="Restaurar backup?"
                                                                data-confirm-text="O conteudo atual sera substituido pelo backup selecionado.">
                                                                Restaurar
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
