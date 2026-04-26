# Changelog

Todas as mudancas relevantes deste projeto sao registradas aqui.

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
