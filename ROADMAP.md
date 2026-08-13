# ROADMAP — Health Prospect CRM

## Fase 0 — Reestruturação
- [ ] congelar arquitetura antiga React/Supabase
- [ ] criar branch de migração
- [ ] inicializar Laravel
- [ ] PostgreSQL
- [ ] Redis
- [ ] Apache/dev local
- [ ] documentação base

## Fase 1 — Fundação
- [ ] autenticação
- [ ] usuários
- [ ] roles
- [ ] permissions
- [ ] layout
- [ ] auditoria básica

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
- [ ] importação CSV
- [ ] importação XLSX
- [ ] mapping de colunas
- [ ] preview
- [ ] deduplicação
- [ ] merge
- [ ] relatório de importação

## Fase 5 — API
- [ ] Sanctum
- [ ] API v1
- [ ] webhooks
- [ ] rate limiting
- [ ] integration logs

## Fase 6 — Marketing/Tracking
- [ ] campaigns
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
