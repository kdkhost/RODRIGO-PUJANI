# Central jur├¡dica e de produtividade ÔÇö auditoria e plano

Data da auditoria: 25/08/2026
Branch de trabalho: `feat/central-juridica-produtividade`  
Baseline preservada: `ed6d6057b4e2362f621cf4b17e0a6207a96ed465`

## Estado encontrado

| ├ürea | Classifica├º├úo | Base que ser├í reaproveitada | Lacuna principal |
| --- | --- | --- | --- |
| Clientes | Parcial e operacional | `Client`, portal e escopo `visibleTo` | Sem `Policy` pr├│pria e sem unicidade forte de CPF/CNPJ |
| Processos | Parcial e operacional | `LegalCase`, DataJud, DJEN, tarefas, documentos | Sem dossi├¬ administrativo integrado |
| Documentos privados | Parcial, com n├║cleo forte | `LegalDocumentStorage`, `LegalDocumentPolicy`, SHA-256 | Sem modelos, vers├Áes e gera├º├úo DOCX/PDF |
| DJEN | Parcial | Consulta por processo e deduplica├º├úo por identificador | Sem pagina├º├úo, OAB, job, telemetria e revis├úo |
| DataJud | Parcial | Consulta oficial por alias e n├║mero CNJ | Marcador de sincroniza├º├úo n├úo possui automa├º├úo |
| Prazos | Parcial | `LegalTask` e campos de lembrete | Sem job, hist├│rico, filtros jur├¡dicos e resumo di├írio |
| Agenda | Completa como agenda interna | FullCalendar 6 e `CalendarEvent` | Sem v├¡nculo com cliente, processo e prazo |
| Google Calendar | Ausente | Laravel HTTP e agenda existente | OAuth 2.0, tokens, v├¡nculo remoto e sincroniza├º├úo |
| Resumo por IA | Ausente | Conte├║do original em `LegalCaseUpdate` | Provedor, estados, revis├úo e publica├º├úo humana |
| Transcri├º├úo de audi├¬ncias | Ausente | Storage privado e filas em banco | Upload seguro, grava├º├úo, job, transcri├º├úo e ata |
| Portal do cliente | Parcial, n├║cleo forte | Sess├úo pr├│pria, isolamento e downloads privados | Integra├º├úo gradual dos novos dados aprovados |
| Financeiro | Ausente | Valores do processo e minutos fatur├íveis | Lan├ºamentos, parcelas, recebimentos e despesas |
| Multi-tenant por escrit├│rio | Ausente | Segrega├º├úo atual por advogado e cliente | N├úo existe `tenant_id` ou escrit├│rio no schema |

O sistema atual atende um ├║nico escrit├│rio. As novas consultas administrativas devem usar as permiss├Áes Spatie e os escopos de carteira existentes. N├úo ser├í introduzido um falso `tenant_id` apenas nos m├│dulos novos, pois isso criaria uma fronteira incompleta e insegura.

## Decis├Áes de arquitetura

1. Reutilizar `legal_case_updates` como fonte can├┤nica de movimenta├º├Áes e publica├º├Áes, adicionando revis├úo, resumo assistido, aprova├º├úo e publica├º├úo expl├¡cita.
2. Reutilizar `legal_tasks` como fonte can├┤nica de prazos. Eventos de prazo na agenda manter├úo `legal_task_id`, sem c├│pia desconectada.
3. Estender `calendar_events` com v├¡nculos jur├¡dicos e identificador do Google Calendar.
4. Criar monitoramentos de OAB e execu├º├Áes de sincroniza├º├úo DJEN com pagina├º├úo completa, locks e deduplica├º├úo protegida por ├¡ndice ├║nico.
5. Manter conte├║do bruto do DJEN e da IA imut├ível; altera├º├Áes humanas ser├úo registradas separadamente.
6. Guardar tokens OAuth e segredos de provedores em colunas com cast criptografado, nunca em `settings.value` nem em logs.
7. Criar modelos documentais versionados. Cada gera├º├úo apontar├í para uma vers├úo imut├ível do modelo e produzir├í arquivo em storage privado.
8. Guardar ├íudio de audi├¬ncia somente no disco privado, com MIME real, allowlist, hash e download autorizado.
9. Manter transcri├º├úo original separada do texto revisado e da ata estruturada.
10. Criar lan├ºamentos financeiros vinculados a cliente e processo, preservando autoriza├º├úo de carteira e uma permiss├úo financeira espec├¡fica.
11. Toda integra├º├úo externa ser├í mockada na su├¡te normal; credenciais reais n├úo ser├úo exigidas para os testes.
12. Nenhum resultado de IA ou publica├º├úo bruta ser├í exibido ao cliente sem aprova├º├úo humana expl├¡cita.

## Entregas tem├íticas previstas

1. Infraestrutura compartilhada, integridade dos v├¡nculos, permiss├Áes e auditoria.
2. DJEN: pagina├º├úo, OAB, monitoramento, job, scheduler, central e revis├úo.
3. Prazos: filtros, hist├│rico, lembretes idempotentes e resumo di├írio.
4. Agenda jur├¡dica e sincroniza├º├úo segura com Google Calendar via OAuth 2.0.
5. Modelos e gera├º├úo de documentos privados, com DOCX/PDF quando dispon├¡veis.
6. Resumo assistido por IA com estados e revis├úo humana.
7. Transcri├º├úo de audi├¬ncias, grava├º├úo no navegador, processamento e ata revis├ível.
8. Portal, financeiro, dossi├¬ de processo/cliente e navega├º├úo administrativa.
9. Valida├º├úo completa, commits tem├íticos e publica├º├úo apenas da branch validada.

## Crit├®rios de aceite transversais

- Nenhuma rota nova sem autentica├º├úo, permiss├úo e verifica├º├úo do registro solicitado.
- V├¡nculo cliente/processo sempre coerente e testado contra IDs manipulados.
- Jobs idempotentes e falhas externas isoladas do restante do scheduler.
- Arquivos privados sem URL p├║blica previs├¡vel.
- Segredos criptografados e ausentes de logs, commits e respostas.
- UTF-8 sem BOM, acentua├º├úo v├ílida e nenhum mojibake.
- `main` e produ├º├úo permanecem intocadas at├® a valida├º├úo integral.
