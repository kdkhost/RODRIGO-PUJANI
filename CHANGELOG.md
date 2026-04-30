# Changelog

Todas as mudancas relevantes deste projeto sao registradas aqui.

## [1.0.6] - 2026-04-30

### Corrigido
- Padronizacao de textos em PT-BR nos formulários administrativos e do portal do cliente, com acentuação/pontuação revisadas.
- Revisão de encoding no repositório para garantir arquivos em UTF-8 sem BOM.

### Adicionado
- Hook de commit versionado em `.githooks/pre-commit` para bloquear qualquer arquivo com UTF-8 BOM.
- Script utilitário `tools/check-no-bom.ps1` para validação manual (repositório completo ou apenas arquivos staged).
- Documentação no `README.md` com regra de encoding e ativação do hook local.

## [1.0.5] - 2026-04-27

### Adicionado
- Portal do cliente em `/portal-cliente` com login por documento e codigo de acesso, painel de acompanhamento, visao individual de processos e download de documentos compartilhados.
- Modulo administrativo de andamentos processuais com permissao granular propria e exibicao opcional no portal do cliente.
- Integracao opcional com a API publica do DataJud/CNJ para importar movimentacoes oficiais por numero CNJ e alias do tribunal.
- Pagina administrativa premium para personalizacao do portal do cliente e configuracao da chave publica do DataJud.

### Alterado
- Cadastros de clientes agora permitem ativar ou bloquear o portal individualmente e redefinir o codigo de acesso.
- Cadastros de processos passaram a controlar resumo do portal, visibilidade ao cliente, alias do tribunal e monitoramento CNJ.
- Sidebar administrativa e dicionario de permissoes foram ampliados para refletir os novos modulos juridicos.
- Formularios e listagens juridicas tocadas nesta entrega foram padronizados em PT-BR com acentuacao corrigida.

### Corrigido
- Frontend do portal do cliente saiu do estado inexistente para um fluxo funcional e responsivo no padrao premium do sistema.
- Mascaras em tempo real no frontend agora cobrem CPF/CNPJ alem do telefone para acessos publicos como o portal do cliente.

## [1.0.4] - 2026-04-26

### Adicionado
- Seeder premium para popular paginas, secoes, areas de atuacao, equipe, depoimentos e biblioteca de midias com conteudo editavel pelo painel.
- Pacote local de 20 imagens premium para hero, capas, areas juridicas, profissionais e depoimentos.

### Alterado
- Site institucional passou a consumir capas, imagens e blocos estruturados do banco de dados, mantendo fallback seguro quando o conteudo ainda nao existir.
- Secoes publicas de sobre, areas, resultados, equipe, depoimentos e contato receberam composicao visual mais premium preservando a paleta escura/dourada.
- Formularios administrativos de paginas, areas, equipe e depoimentos agora mostram referencia da imagem atual para facilitar edicao do conteudo populado.

### Corrigido
- Validacao de JSON nas secoes administrativas agora bloqueia dados invalidos antes da gravacao.
- Views Blade foram ajustadas para evitar compilacao invalida da diretiva `@php` em hospedagens compartilhadas.

## [1.0.3] - 2026-04-26

### Adicionado
- Agenda administrativa com FullCalendar 6, visualizacoes mensal/semanal/diaria/lista, filtros, CRUD em modal, selecao, arrastar, redimensionar, eventos de dia inteiro, responsavel, status, cores, URL, local e propriedades extras em JSON.
- Summernote em portugues com toolbar completa, imagens, videos, tabelas, cores, fullscreen e codeview nos campos ricos do painel.
- Impersonate administrativo para acessar usuarios ativos sem senha, com permissao granular e barra de encerramento de sessao impersonada.
- Preloader configuravel pelo painel com ativar/desativar, escopo site/painel, estilos, cores, marca, mensagem, logo, duracao minima e CSS personalizado.

### Corrigido
- Graficos do Dashboard e Analytics passaram a ser inicializados pelo JS global do painel, com dimensoes estaveis e dados normalizados.
- Seed de configuracoes agora preserva valores ja personalizados no administrativo ao atualizar metadados e novas chaves.

## [1.0.2] - 2026-04-26

### Alterado
- Layout administrativo recebeu acabamento premium mantendo AdminLTE 4, Bootstrap e a paleta escura/dourada do painel.
- Dashboard, Analytics, listagens compartilhadas e arquivos do sistema passaram a usar cabecalhos, cards, tabelas e controles mais consistentes.
- Pagina de perfil foi reconstruida no padrao do painel administrativo, com resumo da conta, formularios Bootstrap e zona critica padronizada.

## [1.0.1] - 2026-04-26

### Corrigido
- Cache publico de configuracoes e paginas agora grava apenas arrays simples, evitando erro 500 em hospedagens com bloqueio de desserializacao de objetos PHP.
- Chaves antigas de cache foram versionadas para ignorar registros serializados quebrados ja existentes no servidor.

## [1.0.0] - 2026-04-26

### Adicionado
- Instalador web com configuracao inicial, escrita de `.env`, migracoes, seeders e criacao do administrador.
- Pagina administrativa para gerenciamento seguro de `.env` e `.htaccess`, com backup automatico antes de cada alteracao.
- Paginas de erro personalizadas para `401`, `403`, `404`, `419`, `429`, `500` e `503`.
- Pagina de manutencao com contagem regressiva para liberacao automatica.
- Testes de autorizacao administrativa para validar permissoes modulares.

### Alterado
- Exigencia minima do projeto ajustada para `PHP ^8.4`.
- Rotas administrativas passaram a respeitar permissoes especificas por modulo.
- Sidebar administrativa agora oculta itens sem permissao para o usuario autenticado.
- Build do Vite configurado com divisao manual de chunks para reduzir o peso do bundle administrativo.
- Cache de paginas publicas e configuracoes passou a ser reutilizado de forma consistente.

### Corrigido
- Tratamento de erros de validacao em formularios AJAX do administrativo foi padronizado, incluindo campos dinamicos, arrays, Summernote e FilePond.
- Feedback visual de status no administrativo foi unificado com toasts de sessao e erros por campo.
- Validacao de JSON nas configuracoes administrativas para evitar gravacao silenciosa de conteudo invalido.
- Limpeza de cache e atualizacao de ambiente apos alteracoes no `.env`.
- Controle de permissao granular, que existia no banco mas nao protegia as rotas, foi efetivamente ativado.

### Auditoria tecnica antes da publicacao
- `php artisan test`: aprovado.
- `npm run build`: aprovado.
- Instalador acessivel localmente pela rota `/instalar`.
- Revisao de risco concluida nos fluxos de instalacao, manutencao, erros personalizados, admin AJAX e controle de acesso.

### Observacoes
- Integracoes de pagamento, WhatsApp/Evolution API e automacoes externas ainda precisam de implementacao dedicada antes de entrar em producao.
