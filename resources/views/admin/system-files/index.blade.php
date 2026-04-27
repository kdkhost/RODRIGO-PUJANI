@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Governança técnica</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Edição controlada de arquivos críticos com backup automático a cada alteração.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-file-earmark-lock"></i>
                    <span>Acesso restrito</span>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="alert alert-warning d-flex gap-3 align-items-start">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div>
                    <strong>Acesso restrito à administração avançada.</strong>
                    <div class="small mt-1">
                        Alterações no <code>.env</code> afetam conexões, filas, cache e ambiente. Alterações no <code>.htaccess</code>
                        podem indisponibilizar o site se as regras ficarem inválidas.
                    </div>
                </div>
            </div>

            <div class="row g-4">
                @foreach ($files as $file)
                    <div class="col-12" id="arquivo-{{ $file['key'] }}">
                        <div class="card admin-form-card">
                            <div class="card-header d-flex flex-wrap gap-3 justify-content-between align-items-center">
                                <div>
                                    <div class="admin-card-kicker">Arquivo monitorado</div>
                                    <h3 class="card-title mb-1">{{ $file['label'] }}</h3>
                                    <div class="small text-muted">{{ $file['description'] }}</div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 small text-muted text-end">
                                    <span><strong>Caminho:</strong> {{ $file['path'] }}</span>
                                    <span><strong>Status:</strong> {{ $file['exists'] ? 'Existente' : 'Não encontrado' }}</span>
                                    <span><strong>Permissão:</strong> {{ $file['writable'] ? 'Gravável' : 'Somente leitura' }}</span>
                                    <span><strong>Atualizado:</strong> {{ $file['updated_at'] ?? 'Ainda não criado' }}</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('admin.system-files.update', $file['key']) }}" method="POST" data-ajax-form>
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3">
                                        <label class="form-label">Conteúdo atual</label>
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
                                    <div class="text-muted small">Nenhum backup disponível para este arquivo.</div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                            <tr>
                                                <th>Backup</th>
                                                <th>Tamanho</th>
                                                <th>Data</th>
                                                <th class="text-end">Ação</th>
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
                                                                data-confirm-text="O conteúdo atual será substituído pelo backup selecionado.">
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
