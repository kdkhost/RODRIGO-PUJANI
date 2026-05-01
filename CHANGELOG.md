# Changelog

Todas as mudancas relevantes deste projeto sao registradas aqui.

## [1.0.12] - 2026-05-01

### Adicionado
- Camada global de segurança para formulários (`ProtectAndAuditFormSubmissions`) com sanitização de entrada e bloqueio de padrões maliciosos (script/php tag, javascript:, event handlers e assinaturas de execução de código).
- Auditoria forense de envios em `form_security_logs` com IP, host, user-agent, origem, rota, fingerprint técnico, reverse DNS e geolocalização aproximada por IP (cidade/região/país/ISP/ASN).
- Nova tela administrativa `admin/form-security-logs` para investigação de tentativas, acessível somente ao Super Admin raiz (ID 1).

### Corrigido
- Regras de edição no portal do cliente ajustadas para respeitar os campos marcados individualmente em "Campos permitidos para edição no portal", sem bloqueio indevido.
- Revisão de texto/acentuação na tela `portal-cliente/perfil` para exibição correta em português brasileiro.
- Manifest do build frontend atualizado após auditoria para manter sincronismo dos assets CSS/JS em produção.
- Marca lateral do portal do cliente ajustada para exibir "Portal do cliente" em linha única e com tipografia alinhada ao padrão do painel administrativo.

### Auditoria técnica executada
- `php artisan test`: aprovado (64 testes / 278 assertions).
- `php artisan view:cache`: aprovado.
- `npm run build`: aprovado.
- `tools/check-no-bom.ps1`: sem arquivos UTF-8 com BOM.

## [1.0.11] - 2026-05-01

### Adicionado
- Novos templates operacionais de e-mail para fluxo real do escritorio: boas-vindas do portal, alerta de novo contato do site, alerta de mensagem interna, atualizacao de processo, documento compartilhado e lembrete de prazo.
- Migration dedicada para semear e manter esses templates sincronizados com o tema de e-mail configurado no administrativo.

### Corrigido
- Publicacao do build frontend no servidor com manifest e assets atualizados para restaurar o carregamento completo de CSS no site publico e nas telas de autenticacao.

## [1.0.10] - 2026-05-01

### Corrigido
- Manifest do frontend recompilado com os hashes atuais dos estilos publico e de autenticacao, eliminando quebra de carregamento CSS em paginas do site e areas de login.
- Suite de reset de senha alinhada ao fluxo real do sistema, passando a validar a notificacao personalizada usada pelo Laravel no projeto.

## [1.0.9] - 2026-05-01

### Corrigido
- Formulario de contato do site passou a registrar o assunto conforme a area selecionada pelo visitante, sem sobrescrita por assunto oculto fixo.
- Normalizacao do backend para gravar `area_interest` e `subject` de forma coerente no CRM administrativo.

### Adicionado
- Nova pagina administrativa de templates de e-mail em `/admin/mail-templates`, com CRUD dedicado, edicao rica (Summernote), variaveis clicaveis e controles visuais de tema.
- Integracao de permissao granular `mail-templates.manage` para controle de acesso ao modulo.
- Cadastro automatico de feriados nacionais no calendario administrativo como eventos editaveis individualmente.

### Alterado
- Pagina de SMTP (`/admin/system-settings/mail`) ganhou atalho direto para a nova gestao de templates.
- Sincronizacao bidirecional entre configuracao SMTP do sistema e os templates padrao de e-mail (redefinicao de senha e notificacao generica).

## [1.0.8] - 2026-05-01

### Alterado
- Configuracoes do sistema deixaram de ser concentradas em uma unica tela e passaram a operar por paginas individuais em `/admin/system-settings`.
- Nova central de configuracoes adicionada como hub de entrada, com atalhos separados para Marca, PWA, SMTP, Seguranca, SEO e Atendimento.
- Pagina de SMTP passou a ter formulario dedicado, preview proprio do template, teste SMTP isolado e tokens clicaveis sem disputar espaco com outras configuracoes.
- Pagina de Atendimento passou a concentrar tambem a acao de popular dados de demonstracao, mantendo o fluxo operacional separado do restante do sistema.

## [1.0.7] - 2026-04-30

### Corrigido
- Portal do cliente passou a respeitar corretamente os campos individuais marcados para edicao no cadastro do cliente (sem bloqueio global indevido).
- Retorno visual de salvamento no portal do cliente reforcado com notificacoes Toastr de sessao (sucesso e erro).
- Editor Summernote do portal do cliente passou a carregar com o CSS correto na tela de mensagens, evitando renderizacao quebrada do campo rico.

### Alterado
- Box flutuante de suporte no portal do cliente padronizado com acabamento visual premium coerente com o painel administrativo.
- Tela de mensagens do portal do cliente passou a contar com editor online (Summernote) no campo de composicao.
- Identificacao da sidebar do portal do cliente ajustada para exibir o texto completo abaixo da logo, sem truncamento.
- Gestao de usuarios passou a restringir cadastro aos IDs 1 e 4, bloqueando atribuicao da funcao Super Admin para qualquer usuario diferente do ID 1.
- Exclusao de usuarios ficou restrita aos IDs 1 e 4, enquanto a autoexclusao pelo perfil foi bloqueada no painel.
- Pagina administrativa de configuracoes do sistema foi reorganizada em guias tematicas para separar marca, PWA, SMTP, seguranca, SEO e atendimento, com salvamento unificado e memoria da ultima guia aberta.
- Sistema de e-mails passou a usar template visual proprio com layout responsivo, tema configuravel, editor rico, tokens clicaveis e teste SMTP disparando o mesmo modelo personalizado do ambiente real.

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
