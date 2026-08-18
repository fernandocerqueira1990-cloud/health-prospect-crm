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

## Sprint 7 — Importação — CONCLUÍDA

- [x] TASK-080 CSV
- [x] TASK-081 XLSX
- [x] TASK-082 Column mapper
- [x] TASK-083 Preview
- [x] TASK-084 Dedup
- [x] TASK-085 Import report

### Validação da Sprint 7

- Importação CSV e XLSX concluída, incluindo upload, leitura e preservação segura do nome original.
- Mapeamento manual e auto-mapeamento dos 40 cabeçalhos do template oficial validados.
- Normalização e validação de dados concluídas, com classificação de registros válidos, avisos e erros no Preview.
- Deduplicação de Empresas, Contatos e Leads concluída, com decisões entre criar novo, reutilizar existente ou ignorar.
- Execução transacional concluída com prevenção de duplicidades, sem atualização ou merge automático de registros existentes.
- Seleção de canal para novos Leads e execução sem exigência de canal quando não há novos Leads validadas.
- Relatório final concluído com contabilização de registros criados, reutilizados, ignorados, bloqueados e falhos.
- Importação em lote validada com 10 registros: nove novos e um reutilizado para Empresa, Contato e Lead.
- Preview, confirmação de execução e relatório final ajustados responsivamente para nomes longos de arquivos, sem truncar o conteúdo.
- Testes automatizados focados da Sprint 7 e suíte completa do projeto aprovados no fechamento técnico.

## Sprint 8 — Campanhas — CONCLUÍDA

- [x] TASK-090 Campaign foundation
- [x] TASK-091 CRUD web completo de Campanhas
- [x] TASK-092 Campaign → Lead attribution/tracking
- [x] TASK-093 Campaign metrics
- [x] TASK-094 Campaign filters and listing refinement
- [x] TASK-095 Sprint 8 final validation

### Validação final da Sprint 8 — TASK-095

- Sprint 8 concluída com CRUD completo de Campanhas, atribuição Campaign → Lead via `LeadSourceEvent`, preservação de First/Last Touch, métricas comerciais e valores separados por moeda.
- Busca, filtros e ordenação em whitelist, paginação com preservação de query strings, RBAC por Policy, auditoria e soft delete revisados no conjunto.
- Revisão final sem pendências funcionais ou de segurança; textos técnicos remanescentes na interface foram traduzidos e o roadmap foi atualizado para remover a indicação antiga de Campanhas como pendente.
- Suíte completa aprovada com 444 testes e 1.941 assertions; Pint, PHPStan/Larastan, build Vite, `git diff --check`, rotas de Campaign e ausência de placeholder de Campanhas validados em 2026-08-18.

### Decisão arquitetural da TASK-094

- A listagem de Campanhas usa `CampaignIndexRequest` para autorização e validação da query string e `CampaignIndexQuery` para busca PostgreSQL case-insensitive, filtros, ordenação em whitelist, eager loading e paginação com preservação dos parâmetros.
- Os filtros mais frequentes permanecem na barra compacta; datas complementares e ordenação ficam na seção de filtros avançados, seguindo o padrão visual dos demais módulos.

### Validação da TASK-094

- Suíte focada de Campaign aprovada com 54 testes e 246 assertions, incluindo 11 testes novos da listagem.
- Suíte completa aprovada com 444 testes e 1.941 assertions; Pint, PHPStan/Larastan, build Vite e `git diff --check` aprovados em 2026-08-18.

### Decisão arquitetural da TASK-092

- A atribuição Campaign → Lead usa `LeadSourceEvent` com snapshot de canal e UTMs, sem `campaign_id` em `leads` e sem relacionamento Eloquent que oculte Leads duplicados.
- A associação manual é feita na Campaign Show, com busca server-side limitada. Campaign opcional na criação de Lead ficou fora desta task para preservar o único evento `lead_created` existente e evitar semântica ambígua entre criação e touch de campanha.

### Validação da TASK-092

- Atribuição Campaign → Lead concluída via `LeadSourceEvent`, com snapshot de canal/UTMs, atualização transacional de First/Last Touch, idempotência manual, auditoria e respeito a Policies e soft deletes.
- Campaign Show concluída com busca server-side limitada, associação protegida, listagem deduplicada de Leads e paginação server-side.
- Suítes focadas aprovadas com 39 testes e 144 assertions em Campaign e 8 testes e 43 assertions em Lead; Pint, PHPStan/Larastan, build Vite e `git diff --check` aprovados em 2026-08-18.

### Decisão arquitetural da TASK-093

- Lead atribuído é o Lead ativo distinto com ao menos um `LeadSourceEvent.campaign_id` da Campaign; touches repetidos não duplicam a contagem e Leads em soft delete são excluídos das métricas operacionais.
- Opportunity atribuída é a Opportunity ativa cujo `lead_id` pertence aos Leads atribuídos; vínculos somente por Company ou Contact não atribuem a oportunidade, e Opportunities em soft delete são excluídas.
- A situação comercial usa os timestamps do domínio: aberta sem `won_at` e `lost_at`, ganha com `won_at`, e perdida com `lost_at`.
- Valores financeiros são agregados por `Opportunity.currency` e exibidos separadamente, sem soma entre moedas e sem conversão cambial.

### Validação da TASK-093

- Métricas de Campaign validadas com 43 testes e 176 assertions; a suíte de Opportunity existente em `tests/Feature/Pipeline` passou com 44 testes e 120 assertions (não existe o diretório `tests/Feature/Opportunity`).
- Pint, PHPStan/Larastan, build Vite e `git diff --check` aprovados em 2026-08-18. A suíte completa foi tentada, mas o executor não produziu resultado verificável; por isso não foi registrada como aprovada.

### Fundação da Sprint 8

- Model, migration, factory, relacionamentos básicos e CampaignPolicy implementados.
- Integridade estrutural de status, datas, orçamento, moeda e referências protegida no PostgreSQL.
- Permissão `campaigns.delete` adicionada de forma idempotente para Administrador, Marketing e Usuário de Teste.
- CRUD web, associação de público, métricas e tracking permaneceram fora do escopo da TASK-090; naquele fechamento, `/campaigns` ainda apontava para o placeholder existente.

### Validação da TASK-091

- CRUD web de Campanhas concluído com RBAC por Policy, Form Requests, Actions transacionais, auditoria, paginação, soft delete e interface Blade responsiva integrada ao design system existente.
- Canais e responsáveis inativos são bloqueados em novos vínculos e preservados na edição quando já associados; regras de status, datas, orçamento e moeda são validadas na aplicação.
- Suíte focada aprovada com 29 testes e 100 assertions; Pint, PHPStan/Larastan, build Vite e `git diff --check` aprovados em 2026-08-18.

## Definition of Done

Uma tarefa só pode ser marcada como concluída quando:

- implementação concluída;
- autorização aplicada;
- validação aplicada;
- testes criados;
- testes passando;
- documentação atualizada;
- lint/static analysis aprovado quando configurado.
