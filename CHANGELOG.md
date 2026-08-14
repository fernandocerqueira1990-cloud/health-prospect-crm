# CHANGELOG

## Unreleased

### Architecture
- Arquitetura alvo definida como Laravel + PostgreSQL + Redis + Apache/Debian.
- Grafana definido para analytics e observabilidade.
- n8n definido como camada opcional de integração.
- Matomo definido como analytics web opcional.
- Prometheus/Loki/Alloy definidos para observabilidade.

### Migration
- A versão inicial React + Vite + Supabase deverá ser preservada em histórico/branch.
- O novo núcleo Laravel será desenvolvido de forma controlada.
- POC React/Vite/Supabase preservada em `legacy/react-supabase`, na `main` e no histórico Git.
- Núcleo Laravel 13 inicializado na branch `migration/laravel-core`, sem módulos de negócio.
- PostgreSQL definido como banco padrão e Redis como backend de cache, sessão e filas no `.env.example`.
- Blade e Tailwind CSS configurados com uma página inicial mínima e build validado.
- Health check inicial adicionado em `GET /health`.
- PHPUnit, Laravel Pint e Larastan/PHPStan configurados; execução PHP pendente por ausência do runtime local.
- CI inicial adicionada com PostgreSQL, Redis, testes, Pint, Larastan e build frontend.

### Sprint 1 — Identidade, autenticação e RBAC
- Autenticação web nativa adicionada com login, logout, remember me, proteção CSRF, rate limiting, renovação de sessão, bloqueio de usuários inativos e atualização de `last_login_at`.
- Estrutura nativa de recuperação de senha preservada e fluxo de autenticação isolado para futura inclusão de MFA.
- Gestão administrativa de usuários adicionada com criação, edição, ativação/desativação e atribuição autorizada de roles, sem exclusão definitiva.
- RBAC muitos-para-muitos adicionado com roles, permissions, pivôs protegidos contra duplicidade e matriz inicial de menor privilégio.
- Policies e Gates adicionados para Dashboard, Users, Roles, Permissions e Audit Logs, com acesso global do role Administrador e autorização obrigatória no backend.
- Auditoria centralizada adicionada para autenticação e mudanças administrativas, com sanitização recursiva de senhas, tokens, cookies e secrets.
- Seeders idempotentes adicionados para roles, permissions e associações padrão; Administrador recebe todas as permissions.
- Comando interativo `php artisan crm:create-admin` adicionado para bootstrap seguro do primeiro administrador, sem senha versionada.
- Layout administrativo responsivo Blade + Tailwind adicionado com Dashboard, Usuários, Roles, Permissões e Auditoria, respeitando permissões nos menus.
- Configuração de sessão de exemplo reforçada com criptografia, HttpOnly e SameSite; cookie Secure documentado como obrigatório em produção HTTPS.
- Suíte da Sprint 1 adicionada e validada no PostgreSQL dedicado `health_prospect_crm_test` com 68 testes e 224 assertions; Pint, PHPStan/Larastan e build Vite aprovados.
- Sincronização RBAC corrigida para permitir remover a última role de um usuário ou a última permission de uma role por meio dos formulários administrativos.
- Testes locais isolados obrigatoriamente no PostgreSQL `health_prospect_crm_test`, com bloqueio de bancos sem sufixo `_test` no ambiente `testing`.
- Role interna `admin` protegida contra alteração de slug, desativação e apropriação do slug reservado; suas permissions completas são preservadas.
- `crm:create-admin` passou a preparar somente a role Administrador e seu catálogo de permissions, sem restaurar customizações das demais roles.
- Alterações de usuário agora preservam obrigatoriamente ao menos um administrador ativo, com validação transacional e lock da role reservada para evitar demissões concorrentes.
- E-mails de usuários passaram a ser normalizados com trim e lowercase antes da validação, consulta e persistência em todos os fluxos atuais.
- Migration de dados adicionada para detectar colisões, normalizar e-mails preexistentes e aplicar uma CHECK constraint PostgreSQL que exige o formato canônico.

### Sprint 2 — Companies
- Gestão web completa de empresas adicionada com listagem, criação, visão 360 inicial, edição e soft delete, sem hard delete ou implementação antecipada de módulos futuros.
- Model, factory, Policy, Form Requests e Actions de Companies adicionados, mantendo controllers finos e autorização no backend.
- Responsável comercial opcional ligado a usuários, com atribuição restrita a contas ativas e eager loading nas consultas.
- CNPJ brasileiro validado e armazenado sem máscara, tax IDs internacionais preservados, e-mails normalizados e websites canonizados com esquema HTTPS quando ausente.
- Busca geral, filtros específicos, intervalo de criação, ordenação por whitelist e paginação com preservação da query string adicionados.
- Auditoria de criação, atualização e exclusão registra snapshots sanitizados, inclusive responsável e prioridade.
- Índice unique parcial de identidade fiscal e índices seletivos para as consultas previstas adicionados com foco em PostgreSQL.
- Campo `source_id` preparado sem FK até a implementação futura de Lead Sources.
- Atualização de Companies passou a permitir manter ou remover o responsável atual que tenha sido desativado, sem permitir atribuição a outro usuário inativo; a invariiante também é verificada transacionalmente nas Actions.
- Filtros, datas e ordenação da listagem passaram por `CompanyIndexRequest`, impedindo datas inválidas de chegarem ao PostgreSQL e validando intervalos cronológicos antes da consulta.
- Redirects após create, update e delete passaram a consultar a `CompanyPolicy`; usuários mutation-only são encaminhados a uma confirmação autenticada, evitando 403 após operações bem-sucedidas sem conceder `companies.view` implicitamente.
- País do identificador fiscal (`tax_id_country`) adicionado explicitamente com código ISO alpha-2, validação CNPJ restrita a `BR`, normalização conservadora internacional e unicidade parcial composta por país e documento.
- Migração de `tax_id_country` preserva registros legados sem classificá-los automaticamente; o par legado pode ser mantido em edições, mas alterações de documento exigem país explícito.
- Rollback da migração de país fiscal passou a detectar tax IDs duplicados antes de qualquer DDL, incluindo registros soft-deleted conforme a semântica do índice antigo, e aborta com orientação clara quando o schema anterior não pode representar os dados.

### Sprint 3 — Contacts
- Gestão completa de contatos adicionada com CRUD Blade, soft delete, status ativo/inativo, Company obrigatória, listagem global e integração na visão 360 da empresa.
- Busca geral, filtros específicos, ordenação por whitelist, eager loading e paginação com preservação da query string adicionados.
- E-mails são normalizados com trim/lowercase e telefones/WhatsApp usam normalização internacional conservadora que preserva o prefixo `+`.
- Papel na decisão e nível de influência usam vocabulários previsíveis em strings evolutivas; perfis sociais genéricos permanecem fora do escopo e LinkedIn é armazenado como URL validada.
- Invariável de contato principal implementada com transação, lock pessimista na Company e índice unique parcial para contatos ativos e não excluídos.
- RBAC via `ContactPolicy`, Actions de criação/atualização/exclusão e auditoria de create, update, delete, ativação, desativação e marcação como principal adicionados.
- Menu Comercial agora apresenta Empresas e Contatos conforme permissions independentes.
- Relação histórica de Contact passou a incluir a Company soft-deleted; listagem, busca, show e edit identificam a empresa arquivada sem criar link para uma rota indisponível.
- Validação impede criar ou mover Contacts para Companies arquivadas, mas permite manter o vínculo histórico atual e editar outros campos ou mover o contato para uma Company ativa.
- Lock ordering das Actions foi padronizado como Companies em ordem crescente de ID antes de Contacts, com revalidação transacional do vínculo para reduzir deadlocks e janelas TOCTOU.
- Filtro de telefone e busca geral passaram a reutilizar a normalização internacional de `phone`/`whatsapp`, encontrando valores persistidos mesmo quando a consulta contém espaços, parênteses ou hífens.
- Company Show deixou de eager-load todos os Contacts e passou a usar paginação própria de 10 itens, parâmetro `contacts_page`, total eficiente e ordenação por principal/nome.
