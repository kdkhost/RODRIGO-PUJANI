@extends('admin.layouts.app')

@section('content')
<div class="admin-documentation-center bg-zinc-950 text-zinc-200 min-h-screen">
    <!-- Tailwind v4 Play CDN -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
          --color-gold: #c49a3c;
          --color-gold-pale: #f7ecd8;
          --color-ink: #090d12;
          --color-ink-2: #111318;
          --shadow-gold: 0 10px 30px -10px rgba(196, 154, 60, 0.3);
        }
        
        .glass-panel {
            @apply bg-zinc-900/50 backdrop-blur-xl border border-white/5;
        }
        
        .doc-section {
            @apply mb-12 scroll-mt-24;
        }
        
        .doc-card {
            @apply p-6 rounded-2xl glass-panel hover:border-gold/30 transition-all duration-300;
        }
        
        .doc-badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-gold/10 text-gold border border-gold/20;
        }
    </style>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-col lg:flex-row gap-10">
            <!-- Sidebar Navigation -->
            <aside class="lg:w-64 flex-shrink-0">
                <div class="sticky top-24 space-y-1">
                    <h2 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4 px-3">Navegação</h2>
                    <a href="#geral" class="flex items-center gap-3 px-3 py-2 rounded-lg text-zinc-400 hover:bg-zinc-900 hover:text-gold transition-colors">
                        <i class="bi bi-info-circle"></i> Guia Geral
                    </a>
                    
                    @if($isSuperAdmin || $isAdministrator)
                        <div class="pt-6 pb-2 px-3">
                            <span class="text-xs font-black uppercase tracking-widest text-gold/60">Estratégico</span>
                        </div>
                        <a href="#marca" class="flex items-center gap-3 px-3 py-2 rounded-lg text-zinc-400 hover:bg-zinc-900 hover:text-gold transition-colors">
                            <i class="bi bi-brush"></i> Identidade Visual
                        </a>
                        <a href="#pwa" class="flex items-center gap-3 px-3 py-2 rounded-lg text-zinc-400 hover:bg-zinc-900 hover:text-gold transition-colors">
                            <i class="bi bi-phone"></i> Configuração PWA
                        </a>
                        <a href="#seguranca" class="flex items-center gap-3 px-3 py-2 rounded-lg text-zinc-400 hover:bg-zinc-900 hover:text-gold transition-colors">
                            <i class="bi bi-shield-lock"></i> Segurança e Logs
                        </a>
                    @endif

                    @if($isSuperAdmin || $isAdministrator || $isLawyer)
                        <div class="pt-6 pb-2 px-3">
                            <span class="text-xs font-black uppercase tracking-widest text-zinc-500">Operacional</span>
                        </div>
                        <a href="#processos" class="flex items-center gap-3 px-3 py-2 rounded-lg text-zinc-400 hover:bg-zinc-900 hover:text-gold transition-colors">
                            <i class="bi bi-briefcase"></i> Gestão de Processos
                        </a>
                        <a href="#agenda" class="flex items-center gap-3 px-3 py-2 rounded-lg text-zinc-400 hover:bg-zinc-900 hover:text-gold transition-colors">
                            <i class="bi bi-calendar3"></i> Agenda e Prazos
                        </a>
                    @endif
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 min-w-0">
                <header class="mb-12">
                    <div class="flex items-center gap-4 mb-4">
                        <span class="doc-badge">Documentação Oficial</span>
                        <span class="text-zinc-500 text-xs">v2.4.0</span>
                    </div>
                    <h1 class="text-4xl sm:text-5xl font-black text-white tracking-tight leading-none mb-6">
                        Como podemos <span class="text-gold">ajudar</span> hoje?
                    </h1>
                    <p class="text-xl text-zinc-400 max-w-2xl leading-relaxed">
                        Bem-vindo ao centro de conhecimento do Sistema Rodrigo Pujani. Encontre guias detalhados e tutoriais para otimizar sua rotina jurídica.
                    </p>
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-gold text-ink font-black shadow-gold hover:opacity-95 transition"
                            data-start-tour
                        >
                            <i class="bi bi-signpost-split-fill"></i>
                            Iniciar tour guiado
                        </button>
                        <span class="text-sm text-zinc-500">
                            Use este botão sempre que quiser rever o fluxo guiado do sistema.
                        </span>
                    </div>
                </header>

                <div class="space-y-16">
                    <!-- Section: Geral -->
                    <section id="geral" class="doc-section">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-zinc-900 flex items-center justify-center text-gold shadow-gold">
                                <i class="bi bi-grid-1x2-fill"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-white">Guia Geral do Sistema</h2>
                        </div>
                        <div class="grid sm:grid-cols-2 gap-6">
                            <div class="doc-card">
                                <h3 class="text-lg font-bold text-white mb-2">Interface Premium</h3>
                                <p class="text-sm text-zinc-400 leading-relaxed">
                                    O sistema utiliza uma interface otimizada para produtividade, com suporte a modo escuro automático e navegação simplificada.
                                </p>
                            </div>
                            <div class="doc-card">
                                <h3 class="text-lg font-bold text-white mb-2">Seu Perfil</h3>
                                <p class="text-sm text-zinc-400 leading-relaxed">
                                    Mantenha seus dados atualizados em "Meu Perfil" para garantir que as notificações e documentos gerados contenham as informações corretas.
                                </p>
                            </div>
                        </div>
                    </section>

                    @if($isSuperAdmin || $isAdministrator)
                        <!-- Section: Marca -->
                        <section id="marca" class="doc-section">
                            <div class="flex items-center gap-3 mb-6 border-t border-zinc-900 pt-16">
                                <div class="w-10 h-10 rounded-xl bg-gold/10 flex items-center justify-center text-gold">
                                    <i class="bi bi-brush-fill"></i>
                                </div>
                                <h2 class="text-2xl font-bold text-white">Identidade Visual e Branding</h2>
                            </div>
                            <div class="doc-card mb-6">
                                <h3 class="text-lg font-bold text-white mb-3">Customização da Logo</h3>
                                <div class="prose prose-invert prose-sm max-w-none text-zinc-400">
                                    <p>Para alterar a identidade do sistema:</p>
                                    <ol class="list-decimal list-inside space-y-2">
                                        <li>Acesse <strong>Operação > Sistema</strong>.</li>
                                        <li>No campo de logotipo, selecione um arquivo SVG (preferencial) ou PNG transparente.</li>
                                        <li>A logo será atualizada automaticamente no painel e na tela de login.</li>
                                    </ol>
                                </div>
                            </div>
                        </section>

                        <!-- Section: PWA -->
                        <section id="pwa" class="doc-section">
                            <div class="flex items-center gap-3 mb-6 border-t border-zinc-900 pt-16">
                                <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-400">
                                    <i class="bi bi-phone-fill"></i>
                                </div>
                                <h2 class="text-2xl font-bold text-white">Configuração do Aplicativo (PWA)</h2>
                            </div>
                            <div class="doc-card">
                                <p class="text-sm text-zinc-400 mb-4">
                                    O sistema pode ser instalado como um aplicativo nativo no celular. Configure as cores de fundo e tema para que o app tenha a cara do seu escritório.
                                </p>
                                <div class="bg-zinc-950 p-4 rounded-xl border border-zinc-800 text-xs font-mono text-gold">
                                    Dica: Use cores que contrastem bem com o ícone para uma melhor experiência visual no celular.
                                </div>
                            </div>
                        </section>
                    @endif

                    @if($isSuperAdmin || $isAdministrator || $isLawyer)
                        <!-- Section: Processos -->
                        <section id="processos" class="doc-section">
                            <div class="flex items-center gap-3 mb-6 border-t border-zinc-900 pt-16">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                                    <i class="bi bi-briefcase-fill"></i>
                                </div>
                                <h2 class="text-2xl font-bold text-white">Gestão de Processos</h2>
                            </div>
                            <div class="grid gap-6">
                                <div class="doc-card">
                                    <h3 class="text-lg font-bold text-white mb-2">Sincronização DataJud</h3>
                                    <p class="text-sm text-zinc-400">
                                        Utilize o botão de sincronização para buscar dados atualizados diretamente do CNJ. O sistema preencherá as movimentações e partes envolvidas automaticamente.
                                    </p>
                                </div>
                                <div class="doc-card">
                                    <h3 class="text-lg font-bold text-white mb-2">Andamentos e Prazos</h3>
                                    <p class="text-sm text-zinc-400">
                                        Cada andamento pode ter um prazo vinculado. Ao cadastrar uma movimentação, o sistema sugerirá a criação de uma tarefa correspondente na sua agenda.
                                    </p>
                                </div>
                            </div>
                        </section>
                    @endif
                </div>

                <footer class="mt-24 pt-8 border-t border-zinc-900 text-center">
                    <p class="text-zinc-600 text-xs">
                        &copy; {{ now()->year }} Rodrigo Pujani Advogados. Todos os direitos reservados.
                    </p>
                </footer>
            </main>
        </div>
    </div>
</div>
@endsection
