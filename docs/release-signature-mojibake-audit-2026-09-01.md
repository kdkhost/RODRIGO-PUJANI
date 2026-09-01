# Auditoria de codificação da release de assinatura

Escopo: branch `release/assinatura-eletronica-segura-2026-09-01`, criada a partir de `45d2576` e acrescida apenas das correções selecionadas de storage privado e SMTP.

## Reclassificação dos 144 candidatos anteriores

- 143 arquivos: falsos positivos produzidos pela expressão anterior ao ser interpretada pelo PowerShell com caracteres literais dependentes do console.
- 1 arquivo, `.githooks/pre-commit`: sequência técnica legítima usada justamente pela regra que detecta mojibake.
- Mojibake real confirmado: 0.
- UTF-8 inválido: 0.
- Arquivos com BOM: 0.

A nova varredura usa pontos de código Unicode e procura apenas sequências compatíveis com UTF-8 interpretado incorretamente como Latin-1/Windows-1252, além do caractere de substituição Unicode.

## Revisão prioritária

Foram revisados código PHP, Blade, JavaScript, validações, notificações, convites, consentimento, comprovantes, evidências e painel SMTP. Não foi encontrada corrupção textual real nesses fluxos.

O painel SMTP continha palavras portuguesas sem acentuação. Elas eram texto ASCII válido, e não mojibake. Os rótulos e instruções foram corrigidos individualmente para português brasileiro, sem substituição global.

## Classificação final

| Categoria | Quantidade |
| --- | ---: |
| Mojibake real | 0 |
| Texto legítimo | 0 entre as sequências precisas encontradas |
| Sequência técnica, regex ou fixture | 1 arquivo |
| Arquivo histórico ou documentação com mojibake real | 0 |
| Falso positivo da varredura anterior | 143 arquivos |

Não existem registros de arquivo, texto incorreto, texto correto e origem provável para mojibake real porque nenhuma ocorrência real foi confirmada. As correções ortográficas do painel SMTP estão protegidas pelo teste `SignatureContentEncodingTest`.
