# Política do artefato de produção

O único formato autorizado para a entrega do código é TAR. Arquivos ZIP não devem ser gerados.

O payload deve ser criado a partir de uma arvore Git limpa com:

```bash
npm ci
npm run build
./tools/build-production-payload.sh /caminho/fora-do-repositorio/payload.tar
```

O empacotador usa uma allowlist dos diretórios Laravel e dos manifests do Composer e do Vite. O `git archive` respeita também os atributos `export-ignore` definidos em `.gitattributes`. O build Vite corrente é incorporado somente depois da exportação da fonte.

Nunca copie o checkout completo, use `rsync` sobre a raiz do repositório nem envie arquivos não rastreados para produção. `vendor` deve ser instalado no staging ou no servidor a partir de `composer.lock`, com os parâmetros seguros de produção. `node_modules` nunca faz parte do payload.

Antes de publicar, liste e valide o TAR. A validação deve falhar se encontrar testes, documentação Markdown sem uso em runtime, ferramentas de desenvolvimento, bancos SQLite, logs, backups, caches de teste, arquivos temporários, credenciais ou scripts auxiliares.

O deploy deve preservar `.env`, uploads e dados existentes no servidor. Nenhum comando de migration integra o empacotamento e migrations somente podem ser executadas quando houver autorização específica.

## Credencial administrativa inicial

`APP_ADMIN_PASSWORD` deve ser definida explicitamente apenas durante a instalação. O instalador recusa valor vazio, cria a conta administrativa e remove o valor do `.env` ao concluir. O seeder de permissões nunca troca a senha de uma conta administrativa já existente.

## Contenção no servidor

Artefatos legados devem ser copiados, conferidos por SHA-256 e movidos para uma quarentena fora do document root. A quarentena usa modo `0700`, arquivos sensíveis usam `0600` e a retenção mínima é de 30 dias. O rollback repõe somente caminhos explícitos após conferir proprietário, modo e hash; uma credencial revogada nunca deve ser restaurada.

Defina `LOG_PATH` com um caminho absoluto gravável fora do document root antes de retirar o log Laravel existente. O canal `single`, o canal `daily` e o fallback de emergência usam esse mesmo destino. Se `LOG_PATH` estiver vazio, o comportamento padrão em `storage/logs/laravel.log` é preservado.
