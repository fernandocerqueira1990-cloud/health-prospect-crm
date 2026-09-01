# Sprint 11 — Commercial Automation & Follow-ups

## Objetivo

Evoluir o Health Prospect CRM de um sistema orientado a registro e acompanhamento para uma operação comercial mais proativa, com centralização de próximas ações, follow-ups, pendências e sinais de estagnação no pipeline.

A Sprint 11 deve aproveitar os módulos já existentes de Leads, Opportunities, Activities, Tasks, Timeline, Dashboard, Redis e Laravel Queue/Scheduler sem duplicar conceitos de domínio.

## Status

| Task | Entrega | Status |
|---|---|---|
| TASK-120 | Próxima ação comercial | Implementada — validação consolidada na TASK-127 |
| TASK-121 | Central de pendências | Implementada — validação consolidada na TASK-127 |
| TASK-122 | Leads sem interação | Implementada — validação consolidada na TASK-127 |
| TASK-123 | Opportunities estagnadas | Implementada — validação consolidada na TASK-127 |
| TASK-124 | Notificações internas | Implementada — validação consolidada na TASK-127 |
| TASK-125 | Scheduler / Queue / Redis | Implementada — validação consolidada na TASK-127 |
| TASK-126 | Dashboard operacional | Implementada — validação consolidada na TASK-127 |
| TASK-127 | Validação final | Concluída em 21/08/2026 |

## Princípios

1. Reutilizar entidades existentes antes de criar novas tabelas.
2. Não automatizar ações irreversíveis sem validação explícita.
3. Toda regra automática relevante deve ser auditável.
4. Alertas devem ser úteis e evitar ruído operacional.
5. Respeitar RBAC, Policies e escopo de dados do usuário.
6. Processamentos recorrentes devem ser idempotentes.
7. Toda mudança deve possuir cobertura de testes e documentação.

## Escopo funcional

### TASK-120 — Próxima ação comercial

Objetivo: garantir que Leads e Opportunities possam expor claramente a próxima ação planejada sem criar uma segunda fonte de verdade paralela a Tasks/Activities.

Implementação:
- `tasks` permanece como fonte de verdade;
- `leads.next_action_at` é sincronizado com o menor `due_at` entre tarefas abertas (`pending`/`in_progress`) vinculadas ao Lead;
- criação, atualização, conclusão, cancelamento, troca de Lead e exclusão de Task recalculam a próxima ação;
- a sincronização ocorre dentro das transações já utilizadas pelos Actions de Task;
- testes focados cobrem criação, avanço para a próxima tarefa, transferência entre Leads e exclusão.

### TASK-121 — Central de pendências

Objetivo: criar uma visão operacional consolidada das ações que exigem atenção.

Implementação:
- seção `Minhas pendências comerciais` adicionada ao Dashboard;
- contadores de tarefas atrasadas, para hoje e próximas;
- escopo restrito ao usuário autenticado e às permissões de cada módulo;
- listagem das próximas cinco tarefas abertas com prazo e links para o registro;
- cards de Leads sem interação e Opportunities estagnadas incorporados conforme TASK-122 e TASK-123.

### TASK-122 — Leads sem interação

Objetivo: identificar Leads que estão sem atividade comercial por período relevante.

Implementação:
- limiar configurável por `LEAD_INACTIVITY_DAYS`, com default de 7 dias;
- regra baseada em `last_interaction_at`, com proteção para leads recém-criados;
- leads convertidos ou desqualificados não entram no alerta;
- filtro `inactive=1` na listagem;
- indicador visual e contador na Central Comercial;
- escopo do Dashboard limitado ao responsável autenticado;
- cobertura de testes focada na regra de inatividade.

### TASK-123 — Opportunities estagnadas no pipeline

Objetivo: sinalizar oportunidades que permanecem tempo excessivo na mesma etapa.

Implementação:
- limiar configurável por `OPPORTUNITY_STAGNATION_DAYS`, com default de 14 dias;
- cálculo baseado em `OpportunityStageHistory.changed_at`, usando `created_at` apenas quando ainda não houve mudança registrada;
- apenas etapas abertas são elegíveis;
- oportunidades recém-criadas ou movimentadas recentemente não são sinalizadas;
- filtro `stagnant=1` disponível na listagem e suportado pelo Kanban;
- badge `Parada há N dias` na listagem;
- contador `Pipeline parado` na Central Comercial, limitado ao usuário responsável;
- testes focados cobrem oportunidade estagnada, movimentação recente, etapa terminal e criação recente.

### TASK-124 — Notificações internas

Objetivo: oferecer alertas internos para eventos comerciais relevantes sem depender inicialmente de e-mail, WhatsApp ou serviços externos.

Implementação:
- uso do canal `database` nativo do Laravel;
- nova tabela `notifications`, associada ao usuário via `Notifiable`;
- payload padronizado com `key`, `type`, `title`, `message`, `severity`, `url`, `subject_type` e `subject_id`;
- geração de alertas para tarefa vencida, follow-up do dia, Lead sem interação e Opportunity estagnada;
- chaves estáveis para impedir duplicidade do mesmo alerta;
- tela `/notifications` com leitura individual, marcação de todas como lidas e acesso ao item de origem;
- consulta sempre limitada às notificações do usuário autenticado;
- testes focados cobrem escopo por responsável, idempotência e geração de alerta de Lead inativo.

### TASK-125 — Scheduler / Queue / Redis

Objetivo: estruturar processamento recorrente e assíncrono para as regras da Sprint 11.

Implementação:
- comando `commercial:notifications` percorre somente usuários ativos em lotes;
- por padrão o comando despacha um job por usuário para a fila `commercial`;
- opção `--sync` permite execução determinística em desenvolvimento e troubleshooting;
- job `GenerateCommercialNotificationsForUser` implementa `ShouldQueue` e `ShouldBeUnique`, com chave única por usuário, três tentativas e timeout controlado;
- o job revalida se o usuário continua ativo antes de processar;
- Laravel Scheduler executa `commercial:notifications` a cada hora;
- `withoutOverlapping`, `onOneServer` e unicidade do job reduzem processamento duplicado em ambientes escalados;
- saída do Scheduler é registrada em `storage/logs/commercial-scheduler.log` sem payloads sensíveis;
- Redis permanece como backend de queue/cache conforme configuração da aplicação;
- testes focados cobrem despacho apenas para usuários ativos e chave estável de unicidade.

Operação esperada em produção:
- manter `php artisan schedule:run` acionado pelo cron a cada minuto ou utilizar `php artisan schedule:work` sob supervisor;
- manter worker da fila `commercial` supervisionado, por exemplo `php artisan queue:work redis --queue=commercial,default`;
- monitorar falhas pela tabela/infraestrutura padrão de jobs e pelos logs da aplicação.

### TASK-126 — Dashboard operacional

Objetivo: incorporar os novos sinais da Sprint 11 ao Dashboard e à navegação global sem prejudicar a leitura atual.

Implementação:
- Central Comercial consolida ações vencidas, ações para hoje, próximas ações, Leads sem interação e Opportunities estagnadas;
- cards são acionáveis e abrem as listagens já filtradas;
- contador de notificações não lidas aparece na navegação lateral e no topbar;
- acesso rápido à tela de notificações fica disponível em qualquer tela autenticada;
- contagem respeita o usuário autenticado e usa a relação nativa `unreadNotifications`;
- teste de interface cobre a exibição do contador de não lidas.

### TASK-127 — Validação final da Sprint 11

Concluída no Pull Request #11, integrado a `main` em 21/08/2026.

- suíte completa: 551 testes e 2.383 assertions aprovados;
- `./vendor/bin/pint --test`: aprovado em 350 arquivos;
- `./vendor/bin/phpstan analyse`: sem erros;
- `pnpm run build`: concluído com sucesso;
- Pull Request com escopo, testes e evidências: concluído;
- CI mais recente de `main`: aprovado após as alterações posteriores de nomenclatura.

As capturas existentes em `assets/screenshots/` representam a interface base. Uma captura específica da Central Comercial e das notificações deve ser incluída junto da próxima validação visual em navegador, sem substituir a evidência funcional já registrada no PR.

## Fora do escopo da Sprint 11

Para manter o sprint controlado, ficam fora neste momento:
- integração completa com n8n;
- WhatsApp, SMS, push ou e-mail transacional;
- lead scoring por IA;
- criação de API pública;
- automações que alterem automaticamente etapa do pipeline;
- distribuição automática de Leads entre vendedores;
- workflows configuráveis pelo usuário final.

## Resultado esperado

Ao final da Sprint 11, o CRM deve ajudar o usuário a responder rapidamente quatro perguntas operacionais:

1. O que preciso fazer hoje?
2. O que já está atrasado?
3. Quais Leads estão ficando esquecidos?
4. Quais oportunidades estão paradas no pipeline?

A aplicação passa, assim, de um repositório de informações comerciais para uma ferramenta ativa de acompanhamento da operação.
