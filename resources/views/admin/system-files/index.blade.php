@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Cofre técnico</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Painel sensível para edição de ambiente e regras de publicação. Abertura liberada apenas após revalidação da senha do Super Admin.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-shield-lock"></i>
                    <div>
                        <strong>Super Admin</strong>
                        <small>Liberação ativa nesta sessão</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="admin-system-files-metrics mb-4">
                <div class="admin-system-files-metric">
                    <span>Arquivos monitorados</span>
                    <strong>{{ number_format($systemFileStats['total'], 0, ',', '.') }}</strong>
                    <small>Escopo sensível disponível</small>
                </div>
                <div class="admin-system-files-metric">
                    <span>Prontos para gravação</span>
                    <strong>{{ number_format($systemFileStats['writable'], 0, ',', '.') }}</strong>
                    <small>Com permissão de escrita</small>
                </div>
                <div class="admin-system-files-metric">
                    <span>Backups recentes</span>
                    <strong>{{ number_format($systemFileStats['backups'], 0, ',', '.') }}</strong>
                    <small>Histórico imediato por arquivo</small>
                </div>
                <div class="admin-system-files-metric">
                    <span>Rotinas críticas</span>
                    <strong>{{ number_format($systemFileStats['critical'], 0, ',', '.') }}</strong>
                    <small>Exigem revisão antes de salvar</small>
                </div>
            </div>

            <div class="row g-4 admin-system-files-shell">
                <div class="col-xl-4">
                    <div class="admin-system-files-sticky">
                        <div class="card admin-system-guard-card">
                            <div class="card-header">
                                <div>
                                    <div class="admin-card-kicker">Blindagem ativa</div>
                                    <h3 class="card-title">Camada de proteção</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="admin-system-guard-points">
                                    <div>
                                        <strong>Visibilidade restrita</strong>
                                        <span>Administradores comuns não visualizam este módulo no menu nem conseguem abrir a rota.</span>
                                    </div>
                                    <div>
                                        <strong>Senha revalidada</strong>
                                        <span>O acesso à página foi liberado apenas após confirmação da senha atual do Super Admin.</span>
                                    </div>
                                    <div>
                                        <strong>Token de edição</strong>
                                        <span>Os formulários desta página usam uma liberação temporária vinculada à sessão atual.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card admin-system-nav-card">
                            <div class="card-header">
                                <div>
                                    <div class="admin-card-kicker">Mapa sensível</div>
                                    <h3 class="card-title">Arquivos desta área</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="admin-system-nav-list">
                                    @foreach ($files as $file)
                                        <a href="#arquivo-{{ $file['key'] }}" class="admin-system-nav-item">
                                            <div class="admin-system-nav-icon">
                                                <i class="bi {{ $file['icon'] }}"></i>
                                            </div>
                                            <div class="admin-system-nav-copy">
                                                <strong>{{ $file['label'] }}</strong>
                                                <span>{{ $file['summary'] }}</span>
                                                <div class="admin-permission-badges">
                                                    <span class="admin-permission-badge">{{ $file['size_human'] }}</span>
                                                    <span class="badge {{ $file['risk_badge'] }}">{{ $file['risk_level'] }}</span>
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="card admin-system-alert-card">
                            <div class="card-body">
                                <div class="d-flex gap-3">
                                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                                    <div>
                                        <strong>Alteração com impacto imediato</strong>
                                        <p class="mb-0">Sempre valide sintaxe, credenciais e regras antes de salvar. Cada modificação cria backup, mas a falha entra em produção na hora.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <div class="admin-system-editor-stack">
                        @foreach ($files as $file)
                            <section class="card admin-system-editor-card" id="arquivo-{{ $file['key'] }}">
                                <div class="card-header">
                                    <div class="admin-system-editor-title">
                                        <span class="admin-system-editor-mark">
                                            <i class="bi {{ $file['icon'] }}"></i>
                                        </span>
                                        <div>
                                            <div class="admin-card-kicker">Arquivo monitorado</div>
                                            <h3 class="card-title">{{ $file['label'] }}</h3>
                                            <p>{{ $file['description'] }}</p>
                                        </div>
                                    </div>
                                    <div class="admin-permission-badges">
                                        <span class="badge {{ $file['risk_badge'] }}">{{ $file['risk_level'] }}</span>
                                        <span class="badge {{ $file['writable'] ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                            {{ $file['writable'] ? 'Gravável' : 'Somente leitura' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <div class="admin-system-meta-grid">
                                        <div class="admin-system-meta-card">
                                            <span>Caminho</span>
                                            <strong>{{ $file['path'] }}</strong>
                                        </div>
                                        <div class="admin-system-meta-card">
                                            <span>Tamanho atual</span>
                                            <strong>{{ $file['size_human'] }}</strong>
                                        </div>
                                        <div class="admin-system-meta-card">
                                            <span>Última alteração</span>
                                            <strong>{{ $file['updated_at'] ?? 'Ainda não criado' }}</strong>
                                        </div>
                                        <div class="admin-system-meta-card">
                                            <span>Backups</span>
                                            <strong>{{ number_format($file['backup_count'], 0, ',', '.') }}</strong>
                                        </div>
                                    </div>

                                    <div class="row g-4">
                                        <div class="col-lg-8">
                                            <form action="{{ route('admin.system-files.update', $file['key']) }}" method="POST" data-ajax-form class="admin-system-editor-form">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="access_token" value="{{ $accessToken }}">

                                                <label class="form-label">Conteúdo atual</label>
                                                <textarea
                                                    name="content"
                                                    rows="{{ $file['rows'] }}"
                                                    class="form-control admin-code-editor admin-system-editor-textarea"
                                                    spellcheck="false">{{ old('content', $file['content']) }}</textarea>
                                                <div class="invalid-feedback d-block" data-error-for="content"></div>

                                                <div class="admin-form-actions">
                                                    <button type="submit" class="btn btn-primary" @disabled(! $file['writable'])>
                                                        <i class="bi bi-save me-1"></i>Salvar {{ $file['label'] }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="admin-system-rules-card">
                                                <strong>Checklist antes de salvar</strong>
                                                <ul>
                                                    @foreach ($file['checklist'] as $rule)
                                                        <li>{{ $rule }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>

                                            <div class="admin-system-rules-card admin-system-rules-card-danger">
                                                <strong>Pontos de atenção</strong>
                                                <ul>
                                                    @foreach ($file['warnings'] as $warning)
                                                        <li>{{ $warning }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer bg-transparent">
                                    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3">
                                        <div>
                                            <div class="admin-card-kicker">Restauração rápida</div>
                                            <strong>Backups recentes</strong>
                                        </div>
                                        <span class="small text-muted">Cada salvamento registra uma cópia em <code>storage/app/system-file-backups</code>.</span>
                                    </div>

                                    @if ($file['backups'] === [])
                                        <div class="admin-system-backup-empty">
                                            <i class="bi bi-clock-history"></i>
                                            <span>Nenhum backup disponível para este arquivo.</span>
                                        </div>
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
                                                        <td>{{ $backup['size_human'] }}</td>
                                                        <td>{{ $backup['updated_at'] }}</td>
                                                        <td class="text-end">
                                                            <form action="{{ route('admin.system-files.restore', $file['key']) }}" method="POST" class="d-inline" data-ajax-form>
                                                                @csrf
                                                                <input type="hidden" name="access_token" value="{{ $accessToken }}">
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
                            </section>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
