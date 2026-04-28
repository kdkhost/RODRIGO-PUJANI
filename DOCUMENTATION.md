# Documentação do Sistema - Rodrigo Pujani Advogados

Este documento fornece as diretrizes para instalação, configuração e operação do sistema administrativo e do portal público.

## 1. Requisitos de Sistema

- **PHP**: 8.4 ou superior
- **Servidor Web**: Apache (com mod_rewrite) ou Nginx
- **Banco de Dados**: MySQL 8.0+ ou MariaDB
- **Dependências**: Node.js & NPM (para compilação de assets), Composer (para dependências PHP)

## 2. Instalação

1. Clone o repositório: `git clone https://github.com/kdkhost/RODRIGO-PUJANI.git`
2. Instale as dependências PHP: `composer install`
3. Instale as dependências JS: `npm install`
4. Compile os assets: `npm run build`
5. Configure o arquivo `.env` (Banco de dados, App URL, etc.)
6. Execute as migrações: `php artisan migrate --seed`

## 3. Configurações Administrativas

### 3.1 Marca e Identidade
No menu **Configurações do Sistema**, você pode:
- Alterar o logotipo (PNG/SVG recomendado).
- Configurar as cores principais para o PWA.
- Definir o título e a descrição do site para SEO.

### 3.2 Suporte via WhatsApp
O sistema possui dois modos de suporte:
1. **Simples**: O botão flutuante leva diretamente ao número principal cadastrado em "Contatos".
2. **Multinível**: Ativando em *Configurações > Suporte e Atendimento*, o sistema exibe uma caixa luxuosa onde o cliente pode escolher com qual advogado deseja falar. Esta lista é gerada automaticamente a partir dos membros da equipe marcados como **ativos**.

### 3.3 Progressive Web App (PWA)
O sistema é compatível com instalação mobile. Configure o nome do aplicativo e o ícone nas configurações para que os usuários possam "Instalar" o portal como um App em seus celulares.

## 4. Gestão de Conteúdo

### 4.1 Equipe (Advogados)
- Cadastre os advogados em **Equipe**.
- Informe o número do WhatsApp com DDD (apenas dígitos) para que eles apareçam na caixa de suporte.
- Defina a ordem de exibição através do campo `Ordem`.

### 4.2 Agenda e Eventos
O painel possui um calendário interativo (FullCalendar) para gestão de compromissos. É possível arrastar e soltar eventos para alterar datas e visualizar detalhes em um painel lateral rápido.

## 5. Upload de Mídia Premium

O sistema utiliza o motor **FilePond** integrado com **Axios** para uploads de alta performance:
- **Previsão**: Veja a mídia antes de enviar.
- **Progresso**: Acompanhe a velocidade, tempo restante (ETA) e status individual de cada arquivo.
- **Identificação**: O sistema identifica automaticamente extensões e tamanhos de arquivos.

## 6. Manutenção

### 6.1 Limpeza de Cache
Sempre que fizer alterações estruturais, o sistema limpa o cache automaticamente. Caso necessário manualmente:
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### 6.2 Backups
Recomenda-se a execução periódica de backups do banco de dados e da pasta `storage/app/public` onde ficam as mídias.

---
*Desenvolvido com foco em alta performance e design luxuoso.*
