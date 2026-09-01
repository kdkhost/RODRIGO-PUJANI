# Auditoria de codificação da release cumulativa

Escopo: branch `release/central-juridica-assinatura-segura-2026-09-01`, criada a partir de `31159b2` e acrescida apenas da reconciliação histórica da migration `modulos`, das proteções já validadas de assinatura, storage privado e SMTP e dos testes de regressão correspondentes.

## Reclassificação dos 144 candidatos anteriores

- 143 arquivos: falsos positivos produzidos pela expressão anterior ao ser interpretada pelo PowerShell com caracteres literais dependentes do console.
- 1 arquivo, `.githooks/pre-commit`: sequência técnica legítima usada justamente pela regra que detecta mojibake.
- Mojibake real confirmado naquela auditoria: 0.
- UTF-8 inválido: 0.
- Arquivos com BOM: 0.

A nova varredura usa pontos de código Unicode e procura apenas sequências compatíveis com UTF-8 interpretado incorretamente como Latin-1/Windows-1252, além do caractere de substituição Unicode.

## Revisão prioritária

Foram priorizados código PHP, Blade, JavaScript, validações, notificações, convites, consentimento, comprovantes, evidências e painel SMTP. A validação desta release deve ser executada novamente após a montagem cumulativa.

O painel SMTP continha palavras portuguesas sem acentuação. Elas eram texto ASCII válido, e não mojibake. Os rótulos e instruções foram corrigidos individualmente para português brasileiro, sem substituição global. A proteção do segredo foi preservada: o valor armazenado não retorna ao HTML e o campo vazio mantém a credencial existente.

## Classificação da auditoria anterior

| Categoria | Quantidade |
| --- | ---: |
| Mojibake real | 0 |
| Texto legítimo | 0 entre as sequências precisas encontradas |
| Sequência técnica, regex ou fixture | 1 arquivo |
| Arquivo histórico ou documentação com mojibake real | 0 |
| Falso positivo da varredura anterior | 143 arquivos |

Não existiam registros de texto incorreto, texto correto e origem provável para mojibake real porque nenhuma ocorrência real havia sido confirmada. As correções ortográficas do painel SMTP e os textos críticos da assinatura são protegidos por `SignatureContentEncodingTest`.
