# Pujani Advogados

Sistema institucional e administrativo desenvolvido em Laravel 13 para operacao em hospedagem compartilhada, com publicacao sem `/public`, painel administrativo customizado, PWA e instalador web.

## Requisitos

- PHP 8.4 ou superior
- Composer 2
- Node.js 20 ou superior
- MariaDB 10.6+ ou MySQL 8+ para producao
- Extensoes PHP usuais do Laravel (`pdo`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `intl`, `zip`)

## Principais recursos

- Instalador web em `/instalar`
- Painel administrativo com tema claro/escuro
- CRUD AJAX para paginas, secoes, areas, equipe, depoimentos, usuarios, funcoes e permissoes
- Permissoes granulares por modulo com Spatie Permission
- Editor rich text com Summernote
- Upload com arrastar e soltar via FilePond
- PWA com manifest e service worker
- Pagina de manutencao com liberacao programada
- Paginas de erro personalizadas
- Gerenciamento administrativo de `.env` e `.htaccess`

## Instalacao local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

## Instalacao pela interface

1. Publique os arquivos no servidor.
2. Aponte o dominio para a raiz do projeto com o `.htaccess` principal ativo.
3. Acesse `/instalar`.
4. Informe URL, banco de dados e dados do administrador.
5. Conclua a instalacao e entre no painel.

## Rotas importantes

- Site: `/`
- Instalador: `/instalar`
- Painel administrativo: `/admin`
- Login: `/login`

## Auditoria de entrega

Antes da publicacao desta versao foram validados:

- `php artisan test`
- `npm run build`
- `php artisan storage:link`
- `php artisan db:seed --force`

## Documentacao de mudancas

O historico desta entrega esta em [CHANGELOG.md](CHANGELOG.md).
