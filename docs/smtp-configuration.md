# Configuração SMTP em produção

A configuração administrativa salva no banco tem precedência sobre o `.env` quando `mail.enabled` está ativo. O `AppServiceProvider` aplica essa configuração durante a inicialização da aplicação.

A senha SMTP é armazenada criptografada com o `APP_KEY` pelo Laravel. O painel nunca devolve o segredo ao navegador: o campo permanece vazio e um envio vazio preserva a credencial existente. Uma nova senha somente é persistida quando informada explicitamente.

Regras operacionais:

- nunca usar `config:show mail`, dumps amplos ou logs que possam imprimir credenciais;
- validar apenas presença, estado criptografado e valores SMTP não secretos;
- manter `.env`, banco e backups com acesso restrito;
- rotacionar a senha se houver qualquer exposição;
- limpar os caches de configuração e de settings depois de alterações;
- testar conexão, autenticação, aceitação e chegada real em caixa controlada.
