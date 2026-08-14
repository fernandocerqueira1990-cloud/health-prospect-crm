# TASKS — Health Prospect CRM

## Estado atual

O repositório anterior usa React + TypeScript + Vite + Supabase.
A arquitetura alvo mudou para Laravel + PostgreSQL + Redis + Apache.

Não misturar implementações antigas com o novo núcleo sem uma decisão explícita de migração.

## Sprint 0 — Preparação

- [x] TASK-001 Criar branch `migration/laravel-core`
- [x] TASK-002 Preservar snapshot da versão React/Supabase (`legacy/react-supabase`, `main` e histórico Git)
- [x] TASK-003 Inicializar Laravel
- [x] TASK-004 Configurar PostgreSQL
- [x] TASK-005 Configurar Redis
- [x] TASK-006 Configurar Tailwind
- [x] TASK-007 Configurar testes
- [x] TASK-008 Configurar Pint
- [x] TASK-009 Adicionar Larastan/PHPStan
- [x] TASK-010 Criar `.env.example`
- [x] TASK-011 Criar health check
- [x] TASK-012 Criar CI inicial

### Validação da Sprint 0

- Frontend Blade + Tailwind configurado; instalação com pnpm e build de produção validados em 2026-08-13.
- Laravel 13, PHP 8.4, PostgreSQL 17, Redis, Node.js 22, pnpm 11, Vite, testes, Pint, Larastan e CI validados no Debian 13 em 2026-08-13.

## Sprint 1 — Identidade

- [x] TASK-020 Autenticação
- [x] TASK-021 Users
- [x] TASK-022 Roles
- [x] TASK-023 Permissions
- [x] TASK-024 Policies
- [x] TASK-025 Audit log básico

### Validação da Sprint 1

- Autenticação web nativa com remember me, rate limiting, bloqueio de inativos, renovação de sessão e atualização de último login.
- RBAC muitos-para-muitos, Gates/Policies, seeders idempotentes, comando seguro para primeiro administrador e auditoria centralizada.
- Interface administrativa Blade + Tailwind e suíte de 68 testes / 224 assertions validadas no PostgreSQL dedicado `health_prospect_crm_test` em 2026-08-13.
- Pint, PHPStan/Larastan, instalação pnpm congelada, build Vite e `git diff --check` aprovados.

## Sprint 2 — Empresas

- [x] TASK-030 Companies migration/model
- [x] TASK-031 Companies CRUD
- [x] TASK-032 Companies filters/search
- [x] TASK-033 Companies tests

### Validação da Sprint 2

- CRUD web completo de Companies com soft delete, responsável comercial ativo, RBAC por Policy e auditoria via `AuditService`.
- CNPJ brasileiro validado, normalizado e apresentado com máscara; identificadores fiscais internacionais continuam suportados.
- Busca, filtros, ordenação por whitelist, eager loading e paginação com query string implementados.
- `source_id` preparado como campo nullable, sem FK, até a Sprint de Lead Sources.
- Suíte completa validada com 109 testes e 426 assertions exclusivamente em `health_prospect_crm_test`; Pint, PHPStan/Larastan, build Vite e `git diff --check` aprovados em 2026-08-14.
- Achados P2 do review corrigidos: updates preservam o responsável atual inativo sem permitir novas atribuições inativas, e filtros de data são validados antes da construção da consulta.
- Redirects pós-mutation respeitam permissions independentes: usuários sem `companies.view` recebem confirmação autenticada sem exposição de dados, enquanto show e index permanecem protegidos.
- Identidade fiscal passou a exigir país explícito para novos documentos, validando CNPJ somente quando `tax_id_country=BR` e preservando IDs internacionais e registros legados.
- Rollback de `tax_id_country` valida previamente colisões incompatíveis com o índice antigo e aborta de forma explícita e transacional sem modificar schema ou dados.

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
