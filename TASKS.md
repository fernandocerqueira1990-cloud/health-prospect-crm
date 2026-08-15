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

- [x] TASK-040 Contacts model
- [x] TASK-041 Contacts CRUD
- [ ] TASK-042 Social profiles (adiado; Sprint 3 armazena somente `linkedin_url`, conforme escopo aprovado)
- [x] TASK-043 Contacts tests

### Validação da Sprint 3

- Gestão web completa de Contacts vinculados obrigatoriamente a Companies, com soft delete, status ativo/inativo, RBAC, auditoria e links seguros de contato.
- Contato principal protegido transacionalmente por lock da Company e índice unique parcial PostgreSQL; desativação ou exclusão não promove outro contato automaticamente.
- Listagem global e seção funcional na Company Show incluem busca, filtros, ordenação por whitelist e paginação de 15 registros com query string preservada.
- E-mail, telefone e WhatsApp normalizados conservadoramente; LinkedIn restrito a URLs HTTP(S) de `linkedin.com` sem integração externa.
- Suíte completa validada com 126 testes e 579 assertions exclusivamente em `health_prospect_crm_test`; Pint, PHPStan/Larastan, build Vite e `git diff --check` aprovados em 2026-08-14.
- Achados P1/P2 do review corrigidos: Contacts preservam acesso à Company arquivada com indicação histórica, validações bloqueiam novos vínculos arquivados e todas as mutações seguem lock ordering determinístico `Companies por ID → Contacts`.
- Achados P2 adicionais corrigidos: buscas telefônicas formatadas são normalizadas sem afetar buscas textuais, e a Company Show pagina Contacts em blocos de 10 com `contacts_page`, sem carregar a relação completa.

## Sprint 3.5 — Front-base / UI Shell

- [x] TASK-045 Evoluir shell visual principal e navegação responsiva
- [x] TASK-046 Criar dashboard comercial com métricas autorizadas
- [x] TASK-047 Adicionar navegação antecipada para módulos futuros com placeholders
- [x] TASK-048 Validar acesso pelo navegador no Debian/WSL
- [x] TASK-049 Criar e validar testes do Dashboard/UI Shell

### Validação da Sprint 3.5

- Shell principal evoluído com sidebar responsiva, header autenticado e navegação comercial.
- Dashboard passou a consumir dados reais de Companies e Contacts respeitando RBAC.
- Empresas e Contatos validados manualmente pelo navegador com criação de registros reais.
- Leads, Pipeline, Atividades, Tarefas, Campanhas e Relatórios possuem placeholders autenticados.
- Navegação mobile preparada com abertura/fechamento do menu e comportamento responsivo.
- Validação funcional realizada via `http://localhost:8000` no Debian/WSL.
- Suíte completa aprovada com 129 testes e 595 assertions.
- Laravel Pint aprovado em 115 arquivos.
- PHPStan/Larastan aprovado sem erros.
- Build Vite de produção aprovado.
- `git diff --check` aprovado.

## Sprint 4 — Leads

- [x] TASK-050 Lead Sources
- [x] TASK-051 Channels
- [x] TASK-052 Leads
- [x] TASK-053 Lead source events
- [x] TASK-054 First/Last Touch
- [x] TASK-055 Lead filters
- [x] TASK-056 Lead tests

### Validação da Sprint 4

- Lead Sources e Channels implementados e populados no PostgreSQL.
- Cadastro, edição, listagem, filtros, visualização e soft delete de Leads implementados.
- RBAC de Leads validado com permissões `leads.view`, `leads.create`, `leads.update` e `leads.delete`.
- Lead Source Events implementados com rastreamento de origem e canal.
- Novo Lead gera automaticamente evento `lead_created`.
- First Touch e Last Touch são definidos automaticamente no cadastro.
- Visão 360 do Lead implementada com histórico de atribuição.
- Integração com Companies e Contacts validada.
- Banco real migrado preservando Users, Companies e Contacts existentes.
- Fluxo visual validado no navegador com Leads reais de teste.
- Suite completa aprovada com 138 testes e 632 assertions.
- Laravel Pint, PHPStan/Larastan, Vite build e `git diff --check` aprovados.

## Sprint 5 — Pipeline

- [x] TASK-060 Pipelines
- [x] TASK-061 Stages
- [x] TASK-062 Opportunities
- [x] TASK-063 Stage history
- [x] TASK-064 Kanban
- [x] TASK-065 Loss reasons
- [x] TASK-066 Pipeline tests

## Sprint 6 — Atividades

- [x] TASK-070 Activities
- [x] TASK-071 Tasks
- [x] TASK-072 Follow-up
- [x] TASK-073 Activity timeline

## Sprint 7 — Importação

- [x] TASK-080 CSV
- [x] TASK-081 XLSX
- [x] TASK-082 Column mapper
- [x] TASK-083 Preview
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
