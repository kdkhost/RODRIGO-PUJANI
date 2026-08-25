# Central jurídica e de produtividade — auditoria e plano

Data da auditoria: 25/08/2026  
Branch de trabalho: `feat/central-juridica-produtividade`  
Baseline preservada: `ed6d6057b4e2362f621cf4b17e0a6207a96ed465`

## Estado encontrado

| Área | Classificação | Base que será reaproveitada | Lacuna principal |
| --- | --- | --- | --- |
| Clientes | Parcial e operacional | `Client`, portal e escopo `visibleTo` | Sem `Policy` própria e sem unicidade forte de CPF/CNPJ |
| Processos | Parcial e operacional | `LegalCase`, DataJud, DJEN, tarefas, documentos | Sem dossiê administrativo integrado |
| Documentos privados | Parcial, com núcleo forte | `LegalDocumentStorage`, `LegalDocumentPolicy`, SHA-256 | Sem modelos, versões e geração DOCX/PDF |
| DJEN | Parcial | Consulta por processo e deduplicação por identificador | Sem paginação, OAB, job, telemetria e revisão |
| DataJud | Parcial | Consulta oficial por alias e número CNJ | Marcador de sincronização não possui automação |
| Prazos | Parcial | `LegalTask` e campos de lembrete | Sem job, histórico, filtros jurídicos e resumo diário |
| Agenda | Completa como agenda interna | FullCalendar 6 e `CalendarEvent` | Sem vínculo com cliente, processo e prazo |
| Google Calendar | Ausente | Laravel HTTP e agenda existente | OAuth 2.0, tokens, vínculo remoto e sincronização |
| Resumo por IA | Ausente | Conteúdo original em `LegalCaseUpdate` | Provedor, estados, revisão e publicação humana |
| Transcrição de audiências | Ausente | Storage privado e filas em banco | Upload seguro, gravação, job, transcrição e ata |
| Portal do cliente | Parcial, núcleo forte | Sessão própria, isolamento e downloads privados | Integração gradual dos novos dados aprovados |
| Financeiro | Ausente | Valores do processo e minutos faturáveis | Lançamentos, parcelas, recebimentos e despesas |
| Multi-tenant por escritório | Ausente | Segregação atual por advogado e cliente | Não existe `tenant_id` ou escritório no schema |

O sistema atual atende um único escritório. As novas consultas administrativas devem usar as permissões Spatie e os escopos de carteira existentes. Não será introduzido um falso `tenant_id` apenas nos módulos novos, pois isso criaria uma fronteira incompleta e insegura.

## Decisões de arquitetura

1. Reutilizar `legal_case_updates` como fonte canônica de movimentações e publicações, adicionando revisão, resumo assistido, aprovação e publicação explícita.
2. Reutilizar `legal_tasks` como fonte canônica de prazos. Eventos de prazo na agenda manterão `legal_task_id`, sem cópia desconectada.
3. Estender `calendar_events` com vínculos jurídicos e identificador do Google Calendar.
4. Criar monitoramentos de OAB e execuções de sincronização DJEN com paginação completa, locks e deduplicação protegida por índice único.
5. Manter conteúdo bruto do DJEN e da IA imutável; alterações humanas serão registradas separadamente.
6. Guardar tokens OAuth e segredos de provedores em colunas com cast criptografado, nunca em `settings.value` nem em logs.
7. Criar modelos documentais versionados. Cada geração apontará para uma versão imutável do modelo e produzirá arquivo em storage privado.
8. Guardar áudio de audiência somente no disco privado, com MIME real, allowlist, hash e download autorizado.
9. Manter transcrição original separada do texto revisado e da ata estruturada.
10. Criar lançamentos financeiros vinculados a cliente e processo, preservando autorização de carteira e uma permissão financeira específica.
11. Toda integração externa será mockada na suíte normal; credenciais reais não serão exigidas para os testes.
12. Nenhum resultado de IA ou publicação bruta será exibido ao cliente sem aprovação humana explícita.

## Entregas temáticas previstas

1. Infraestrutura compartilhada, integridade dos vínculos, permissões e auditoria.
2. DJEN: paginação, OAB, monitoramento, job, scheduler, central e revisão.
3. Prazos: filtros, histórico, lembretes idempotentes e resumo diário.
4. Agenda jurídica e sincronização segura com Google Calendar via OAuth 2.0.
5. Modelos e geração de documentos privados, com DOCX/PDF quando disponíveis.
6. Resumo assistido por IA com estados e revisão humana.
7. Transcrição de audiências, gravação no navegador, processamento e ata revisável.
8. Portal, financeiro, dossiê de processo/cliente e navegação administrativa.
9. Validação completa, commits temáticos e publicação apenas da branch validada.

## Critérios de aceite transversais

- Nenhuma rota nova sem autenticação, permissão e verificação do registro solicitado.
- Vínculo cliente/processo sempre coerente e testado contra IDs manipulados.
- Jobs idempotentes e falhas externas isoladas do restante do scheduler.
- Arquivos privados sem URL pública previsível.
- Segredos criptografados e ausentes de logs, commits e respostas.
- UTF-8 sem BOM, acentuação válida e nenhum mojibake.
- `main` e produção permanecem intocadas até a validação integral.
