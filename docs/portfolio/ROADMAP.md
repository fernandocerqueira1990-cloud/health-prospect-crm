# ROADMAP — Health Prospect CRM

Este roadmap apresenta o estado atual do projeto e as próximas frentes de evolução. O histórico técnico detalhado de cada sprint permanece em `docs/internal/TASKS.md` e no `CHANGELOG.md`.

## Estado atual

| Frente | Status |
|---|---|
| Fundação Laravel / PostgreSQL / Redis | Concluída |
| Autenticação, usuários, RBAC e auditoria | Concluída |
| Empresas e contatos | Concluída |
| Leads, origens e canais | Concluída |
| Pipeline, stages e oportunidades | Concluída |
| Atividades, tarefas e follow-up | Concluída |
| Importação CSV/XLSX | Concluída |
| Campanhas e atribuição | Concluída |
| Relatórios comerciais | Concluída |
| Security & Production Hardening | Concluída |
| Automação comercial & follow-ups | Em desenvolvimento — Sprint 11 |
| API pública / integrações avançadas | Planejada |
| Automação com n8n | Planejada |
| Observabilidade completa | Planejada |
| Deploy de produção | Planejado |

## Sprints concluídas

### Sprint 0 — Fundação técnica
- [x] Laravel self-hosted
- [x] PostgreSQL
- [x] Redis
- [x] Tailwind / Vite
- [x] testes automatizados
- [x] Pint e PHPStan/Larastan
- [x] CI inicial
- [x] health check

### Sprint 1 — Identidade e segurança de acesso
- [x] autenticação
- [x] usuários
- [x] roles e permissions
- [x] policies
- [x] auditoria

### Sprint 2 — Empresas
- [x] CRUD completo
- [x] filtros e busca
- [x] paginação
- [x] validação de identificação fiscal

### Sprint 3 — Contatos
- [x] CRUD completo
- [x] vínculo com empresas
- [x] filtros, busca e paginação
- [x] regras de contato principal

### Sprint 3.5 — UI Shell e Dashboard
- [x] layout responsivo
- [x] navegação comercial
- [x] dashboard operacional
- [x] validação pelo navegador

### Sprint 4 — Leads
- [x] lead sources
- [x] channels
- [x] CRUD de leads
- [x] first/last touch
- [x] eventos de origem
- [x] filtros e visão 360

### Sprint 5 — Pipeline
- [x] pipelines
- [x] stages
- [x] opportunities
- [x] Kanban
- [x] histórico de etapas
- [x] motivos de perda

### Sprint 6 — Operação comercial
- [x] atividades
- [x] tarefas
- [x] follow-ups
- [x] timeline

### Sprint 7 — Importação
- [x] CSV
- [x] XLSX
- [x] mapeamento de colunas
- [x] preview
- [x] deduplicação
- [x] execução transacional
- [x] relatório de importação

> Merge automático de registros existentes permanece fora do escopo atual e pode ser avaliado em evolução futura.

### Sprint 8 — Campanhas
- [x] CRUD de campanhas
- [x] filtros e listagem
- [x] atribuição Campaign → Lead
- [x] métricas comerciais
- [x] first/last touch preservados

### Sprint 9 — Relatórios
- [x] visão executiva
- [x] funil e conversões
- [x] origem e canais
- [x] performance por campanha
- [x] pipeline e tempo por etapa
- [x] filtros por período

### Sprint 10 — Security & Production Hardening
- [x] baseline de ambiente e produção
- [x] proteção de cadastro público e usuários de teste
- [x] HTTPS, sessões, proxies e security headers
- [x] hardening de autenticação, rate limiting, RBAC e auditoria
- [x] segurança de upload/importação
- [x] proteção de secrets, logs e dependências
- [x] regressão final de segurança

## Sprint atual

### Sprint 11 — Commercial Automation & Follow-ups

Objetivo: tornar o CRM mais proativo no acompanhamento comercial, centralizando próximas ações, pendências, Leads sem interação e oportunidades estagnadas.

- [ ] TASK-120 próxima ação comercial
- [ ] TASK-121 central de pendências
- [ ] TASK-122 Leads sem interação
- [ ] TASK-123 Opportunities estagnadas
- [ ] TASK-124 notificações internas
- [ ] TASK-125 Scheduler / Queue / Redis
- [ ] TASK-126 dashboard operacional
- [ ] TASK-127 validação final da Sprint 11

Documento técnico: [`docs/internal/SPRINT_11_COMMERCIAL_AUTOMATION.md`](../internal/SPRINT_11_COMMERCIAL_AUTOMATION.md)

## Próximas frentes

### API e integrações
- [ ] definir superfície pública da API v1
- [ ] autenticação de integração
- [ ] webhooks inbound/outbound
- [ ] logs de integração
- [ ] políticas de rate limiting específicas para API

### Automação avançada
- [ ] integrar n8n Community
- [ ] distribuição automática de leads
- [ ] notificações externas
- [ ] workflows configuráveis
- [ ] integrações oficiais quando aplicável

### Observabilidade
- [ ] Prometheus
- [ ] Node Exporter
- [ ] Loki / Alloy
- [ ] métricas Apache / PHP
- [ ] métricas PostgreSQL
- [ ] dashboards Grafana de infraestrutura
- [ ] alertas operacionais

### Analytics e tracking
- [ ] tracking events avançados
- [ ] UTMs avançadas
- [ ] Matomo self-hosted
- [ ] views/schema analítico dedicado quando necessário

### Produção
- [ ] domínio e TLS definitivo
- [ ] firewall e regras de exposição
- [ ] política de backup
- [ ] teste de restore
- [ ] workers e scheduler
- [ ] monitoramento operacional
- [ ] runbook de produção

## Princípios de evolução

1. Integridade dos dados.
2. Segurança por padrão.
3. Auditabilidade.
4. Usabilidade.
5. Testes automatizados.
6. Observabilidade.
7. Evolução incremental e rastreável.
