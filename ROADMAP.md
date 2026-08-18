# ROADMAP — Health Prospect CRM

## Fase 0 — Reestruturação
- [x] congelar arquitetura antiga React/Supabase
- [x] criar branch de migração
- [x] inicializar Laravel
- [x] PostgreSQL
- [x] Redis
- [x] Apache/dev local
- [x] documentação base

## Fase 1 — Fundação
- [x] autenticação
- [x] usuários
- [x] roles
- [x] permissions
- [x] layout
- [x] auditoria básica

## Fase 2 — CRM Core
- [ ] companies
- [ ] contacts
- [ ] lead sources
- [ ] channels
- [ ] leads
- [ ] filtros
- [ ] busca
- [ ] paginação
- [ ] tags

## Fase 3 — Comercial
- [ ] pipelines
- [ ] stages
- [ ] opportunities
- [ ] Kanban
- [ ] stage history
- [ ] activities
- [ ] tasks
- [ ] loss reasons

## Fase 4 — Dados
- [x] importação CSV
- [x] importação XLSX
- [x] mapping de colunas
- [x] preview
- [x] deduplicação
- [ ] merge
- [x] execução final e relatório de importação

Sprint 7 de importação concluída; merge permanece como evolução posterior e não faz parte da execução final atual.

## Fase 5 — API
- [ ] Sanctum
- [ ] API v1
- [ ] webhooks
- [ ] rate limiting
- [ ] integration logs

## Fase 6 — Marketing/Tracking
- [ ] campaigns (fundação do domínio concluída na TASK-090; CRUD web pendente)
- [ ] tracking events
- [ ] UTMs
- [ ] first touch
- [ ] last touch
- [ ] Matomo

## Fase 7 — Analytics
- [ ] schema analytics
- [ ] views
- [ ] grafana_reader
- [ ] Grafana
- [ ] dashboard executivo
- [ ] dashboard por origem
- [ ] funil
- [ ] pipeline

## Fase 8 — Automação
- [ ] n8n
- [ ] webhook inbound
- [ ] notificações
- [ ] distribuição de leads
- [ ] follow-up
- [ ] integrações oficiais

## Fase 9 — Observabilidade
- [ ] Prometheus
- [ ] Node Exporter
- [ ] Loki
- [ ] Alloy
- [ ] métricas Apache/PHP
- [ ] métricas PostgreSQL
- [ ] health endpoint
- [ ] alertas

## Fase 10 — Produção
- [ ] TLS
- [ ] firewall
- [ ] hardening
- [ ] backup
- [ ] restore test
- [ ] APP_DEBUG=false
- [ ] workers
- [ ] scheduler
- [ ] logs
- [ ] runbook
