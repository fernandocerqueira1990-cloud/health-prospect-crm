# Health Prospect CRM

CRM B2B self-hosted para gestão comercial, prospecção, captação e análise de leads.

## Arquitetura alvo

- Debian Linux
- Apache 2
- PHP-FPM
- Laravel
- PostgreSQL
- Redis
- Blade + Tailwind + Alpine/Livewire
- Grafana
- Prometheus
- Loki + Alloy
- n8n opcional
- Matomo opcional

## Documentação obrigatória

Antes de contribuir:

1. `MASTER.md`
2. `ARCHITECTURE.md`
3. `DATABASE.md`
4. `SECURITY.md`
5. `API.md`
6. `ROADMAP.md`
7. `TASKS.md`
8. `PROMPT_MESTRE_V2.md`

## Estado

Projeto em fase de migração da prova de conceito React/Supabase para o núcleo Laravel self-hosted.

## Estratégia

O desenvolvimento será incremental e orientado por tarefas.

Cada módulo deve possuir:
- modelagem;
- migrations;
- autorização;
- validação;
- testes;
- documentação.
