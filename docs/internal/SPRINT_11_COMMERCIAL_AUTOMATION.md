# Sprint 11 — Commercial Automation & Follow-ups

## Objetivo

Evoluir o Health Prospect CRM de um sistema orientado a registro e acompanhamento para uma operação comercial mais proativa, com centralização de próximas ações, follow-ups, pendências e sinais de estagnação no pipeline.

A Sprint 11 deve aproveitar os módulos já existentes de Leads, Opportunities, Activities, Tasks, Timeline, Dashboard, Redis e Laravel Queue/Scheduler sem duplicar conceitos de domínio.

## Status

| Task | Entrega | Status |
|---|---|---|
| TASK-120 | Próxima ação comercial | Implementada — validação consolidada na TASK-127 |
| TASK-121 | Central de pendências | Implementada — validação consolidada na TASK-127 |
| TASK-122 | Leads sem interação | Próxima |
| TASK-123 | Opportunities estagnadas | Pendente |
| TASK-124 | Notificações internas | Pendente |
| TASK-125 | Scheduler / Queue / Redis | Pendente |
| TASK-126 | Dashboard operacional | Pendente |
| TASK-127 | Validação final | Pendente |

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

Entregáveis:
- definir como a próxima ação será derivada das tarefas/atividades existentes;
- exibir próxima ação, data/hora, responsável e status;
- destacar próxima ação vencida;
- permitir acesso rápido à criação/edição da tarefa relacionada;
- respeitar permissões existentes.

Critérios de aceite:
- não duplicar informação já existente em `tasks`;
- próxima ação deve ser obtida de forma determinística;
- registros concluídos não podem aparecer como próxima ação;
- timezone e datas devem seguir a configuração da aplicação.

Implementação:
- `tasks` permanece como fonte de verdade;
- `leads.next_action_at` é sincronizado com o menor `due_at` entre tarefas abertas (`pending`/`in_progress`) vinculadas ao Lead;
- criação, atualização, conclusão, cancelamento, troca de Lead e exclusão de Task recalculam a próxima ação;
- a sincronização ocorre dentro das transações já utilizadas pelos Actions de Task;
- testes focados cobrem criação, avanço para a próxima tarefa, transferência entre Leads e exclusão.

### TASK-121 — Central de pendências

Objetivo: criar uma visão operacional consolidada das ações que exigem atenção.

Categorias iniciais:
- tarefas vencidas;
- tarefas para hoje;
- próximas tarefas;
- Leads sem próxima ação;
- Opportunities sem próxima ação.

Entregáveis:
- seção no Dashboard ou tela dedicada conforme avaliação de UX;
- contadores resumidos;
- listagem paginada/limitada com links para o registro de origem;
- filtros por responsável quando aplicável.

Implementação inicial:
- seção `Minhas pendências comerciais` adicionada ao Dashboard;
- contadores de tarefas atrasadas, para hoje e próximas;
- escopo restrito ao usuário autenticado e às permissões de `tasks.view`;
- listagem das próximas cinco tarefas abertas com prazo e links para o registro;
- testes focados cobrem agrupamento por prazo, isolamento por responsável e ocultação sem permissão.

Leads sem próxima ação e Opportunities sem próxima ação serão incorporados após as regras das TASK-122 e TASK-123 para evitar lógica duplicada.

### TASK-122 — Leads sem interação

Objetivo: identificar Leads que estão sem atividade comercial por período relevante.

Entregáveis:
- cálculo do último contato/interação a partir do histórico existente;
- regra configurável de dias sem interação;
- indicador visual na listagem/show do Lead;
- filtro para Leads sem interação;
- inclusão na central de pendências.

Observação: a regra deve diferenciar criação recente de abandono real para reduzir falsos positivos.

### TASK-123 — Opportunities estagnadas no pipeline

Objetivo: sinalizar oportunidades que permanecem tempo excessivo na mesma etapa.

Entregáveis:
- cálculo de tempo na etapa com base no stage history;
- limite configurável por etapa ou regra global inicial;
- indicador visual no Kanban/lista;
- filtro de oportunidades estagnadas;
- inclusão na central de pendências.

### TASK-124 — Notificações internas

Objetivo: oferecer alertas internos para eventos comerciais relevantes sem depender inicialmente de e-mail, WhatsApp ou serviços externos.

Eventos candidatos:
- tarefa vencida;
- follow-up para hoje;
- Lead sem interação;
- Opportunity estagnada.

Entregáveis:
- definir mecanismo interno de notificação;
- evitar notificações duplicadas;
- marcação como lida quando aplicável;
- autorização e escopo por usuário.

### TASK-125 — Scheduler / Queue / Redis

Objetivo: estruturar processamento recorrente e assíncrono para as regras da Sprint 11.

Entregáveis:
- jobs/commands idempotentes;
- agendamento via Laravel Scheduler;
- execução em Queue/Redis apenas quando agregar valor;
- logs técnicos adequados sem exposição de dados sensíveis;
- estratégia segura para desenvolvimento e produção.

### TASK-126 — Dashboard operacional

Objetivo: incorporar os novos sinais da Sprint 11 ao Dashboard sem prejudicar a leitura atual.

Indicadores candidatos:
- ações vencidas;
- ações para hoje;
- Leads sem interação;
- Opportunities estagnadas.

Entregáveis:
- cards compactos;
- links para listas filtradas;
- métricas respeitando RBAC;
- layout responsivo consistente com o design atual.

### TASK-127 — Validação final da Sprint 11

Checklist mínimo:
- suíte focada da Sprint 11;
- suíte completa;
- `./vendor/bin/pint --test`;
- `./vendor/bin/phpstan analyse`;
- `pnpm run build`;
- `git diff --check`;
- revisão manual pelo navegador;
- revisão de autorização/RBAC;
- revisão de queries e N+1;
- atualização de `docs/portfolio/ROADMAP.md`;
- atualização de `CHANGELOG.md`;
- screenshot de alterações relevantes para `assets/screenshots/` quando aplicável;
- Pull Request com escopo, testes e evidências.

## Fora do escopo da Sprint 11

Para manter o sprint controlado, ficam fora neste momento:
- integração completa com n8n;
- WhatsApp, SMS, push ou e-mail transacional;
- lead scoring por IA;
- criação de API pública;
- automações que alterem automaticamente etapa do pipeline;
- distribuição automática de Leads entre vendedores;
- workflows configuráveis pelo usuário final.

Esses itens podem ser tratados em sprints posteriores após estabilização da automação interna.

## Resultado esperado

Ao final da Sprint 11, o CRM deve ajudar o usuário a responder rapidamente quatro perguntas operacionais:

1. O que preciso fazer hoje?
2. O que já está atrasado?
3. Quais Leads estão ficando esquecidos?
4. Quais oportunidades estão paradas no pipeline?

A aplicação passa, assim, de um repositório de informações comerciais para uma ferramenta ativa de acompanhamento da operação.
