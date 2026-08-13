# TASKS — Health Prospect CRM

## Estado atual

O repositório anterior usa React + TypeScript + Vite + Supabase.
A arquitetura alvo mudou para Laravel + PostgreSQL + Redis + Apache.

Não misturar implementações antigas com o novo núcleo sem uma decisão explícita de migração.

## Sprint 0 — Preparação

- [ ] TASK-001 Criar branch `migration/laravel-core`
- [ ] TASK-002 Preservar snapshot da versão React/Supabase
- [ ] TASK-003 Inicializar Laravel
- [ ] TASK-004 Configurar PostgreSQL
- [ ] TASK-005 Configurar Redis
- [ ] TASK-006 Configurar Tailwind
- [ ] TASK-007 Configurar testes
- [ ] TASK-008 Configurar Pint
- [ ] TASK-009 Adicionar Larastan/PHPStan
- [ ] TASK-010 Criar `.env.example`
- [ ] TASK-011 Criar health check
- [ ] TASK-012 Criar CI inicial

## Sprint 1 — Identidade

- [ ] TASK-020 Autenticação
- [ ] TASK-021 Users
- [ ] TASK-022 Roles
- [ ] TASK-023 Permissions
- [ ] TASK-024 Policies
- [ ] TASK-025 Audit log básico

## Sprint 2 — Empresas

- [ ] TASK-030 Companies migration/model
- [ ] TASK-031 Companies CRUD
- [ ] TASK-032 Companies filters/search
- [ ] TASK-033 Companies tests

## Sprint 3 — Contatos

- [ ] TASK-040 Contacts model
- [ ] TASK-041 Contacts CRUD
- [ ] TASK-042 Social profiles
- [ ] TASK-043 Contacts tests

## Sprint 4 — Leads

- [ ] TASK-050 Lead Sources
- [ ] TASK-051 Channels
- [ ] TASK-052 Leads
- [ ] TASK-053 Lead source events
- [ ] TASK-054 First/Last Touch
- [ ] TASK-055 Lead filters
- [ ] TASK-056 Lead tests

## Sprint 5 — Pipeline

- [ ] TASK-060 Pipelines
- [ ] TASK-061 Stages
- [ ] TASK-062 Opportunities
- [ ] TASK-063 Stage history
- [ ] TASK-064 Kanban
- [ ] TASK-065 Loss reasons
- [ ] TASK-066 Pipeline tests

## Sprint 6 — Atividades

- [ ] TASK-070 Activities
- [ ] TASK-071 Tasks
- [ ] TASK-072 Follow-up
- [ ] TASK-073 Activity timeline

## Sprint 7 — Importação

- [ ] TASK-080 CSV
- [ ] TASK-081 XLSX
- [ ] TASK-082 Column mapper
- [ ] TASK-083 Preview
- [ ] TASK-084 Dedup
- [ ] TASK-085 Import report

## Definition of Done

Uma tarefa só pode ser marcada como concluída quando:

- implementação concluída;
- autorização aplicada;
- validação aplicada;
- testes criados;
- testes passando;
- documentação atualizada;
- lint/static analysis aprovado quando configurado.
