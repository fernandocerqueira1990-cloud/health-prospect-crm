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

### Sprint 3.5 — Front-base / UI Shell
- Shell visual do CRM evoluído com sidebar responsiva, header autenticado e navegação comercial completa.
- Dashboard inicial passou a exibir indicadores reais de Companies e Contacts somente quando o usuário possui autorização correspondente.
- Indicadores adicionados para empresas cadastradas, contatos ativos, empresas de alta prioridade e decisores/champions.
- Atalhos para criação de empresa e contato e roadmap visual da Sprint 4 adicionados.
- Leads, Pipeline, Atividades, Tarefas, Campanhas e Relatórios receberam placeholders autenticados para validação antecipada da experiência de navegação.
- Navegação mobile reforçada com comportamento responsivo.
- Fluxo visual validado no navegador através de login, criação de Company, criação de Contact e atualização das métricas do Dashboard.
- Suíte completa validada com 129 testes e 595 assertions.
- Laravel Pint aprovado em 115 arquivos, PHPStan/Larastan sem erros, build Vite aprovado e `git diff --check` sem inconsistências.

### Sprint 4 — Leads
- Implementados `LeadSource`, `Channel`, `Lead` e `LeadSourceEvent`.
- Adicionadas migrations para origens, canais, leads, eventos e referências de First/Last Touch.
- Company passou a suportar `source_id` com vínculo a Lead Source.
- Adicionados seeders idempotentes para 8 origens comerciais e 11 canais.
- CRUD completo de Leads implementado com listagem, cadastro, edição, visualização e soft delete.
- Filtros de Leads implementados por busca, status, prioridade, temperatura, origem, canal e responsável.
- RBAC implementado com `leads.view`, `leads.create`, `leads.update` e `leads.delete`.
- Validação garante vínculo consistente entre Company e Contact.
- Normalização de e-mail, telefone e WhatsApp aplicada aos Leads.
- Lead Source Events implementados para rastreamento de aquisição.
- Cadastro de novo Lead cria automaticamente evento `lead_created`.
- First Touch e Last Touch são definidos automaticamente a partir do evento inicial.
- Visão 360 de Lead implementada com dados comerciais, origem, atribuição e histórico.
- Dashboard e navegação atualizados para substituir o placeholder de Leads pelo módulo funcional.
- Banco PostgreSQL real migrado com preservação dos registros existentes.
- Fluxo funcional validado no navegador com criação de Leads e atribuição automática.
- Suite completa aprovada com 138 testes e 632 assertions.
- Laravel Pint aprovado em 142 arquivos.
- PHPStan/Larastan aprovado sem erros.
- Build Vite de produção aprovado.

### Sprint 5 — Pipeline
- Implementados `Pipeline`, `Stage`, `Opportunity`, `OpportunityStageHistory` e `LossReason`.
- Adicionadas migrations, factories e seeders idempotentes para Pipeline, Stages e motivos de perda.
- Pipeline Comercial padrão criado com 8 etapas: Novo, Qualificação, Diagnóstico, Demonstração, Proposta, Negociação, Ganho e Perdido.
- CRUD completo de Opportunities implementado com listagem, filtros, criação, edição, visualização e soft delete.
- RBAC implementado com `opportunities.view`, `opportunities.create`, `opportunities.update` e `opportunities.delete`.
- Opportunities integradas com Leads, Companies, Contacts e responsável comercial.
- Histórico de movimentação entre etapas implementado com registro de origem, destino, usuário, data e observações.
- Criação de Opportunity gera automaticamente o histórico inicial da etapa.
- Movimentação entre etapas atualiza automaticamente a probabilidade comercial.
- Estados terminais Ganho e Perdido implementados com controle de `won_at` e `lost_at`.
- Loss Reasons implementados com 8 motivos comerciais e obrigatoriedade de motivo ao mover para Perdido.
- Reabertura de oportunidade perdida limpa automaticamente `lost_at` e `loss_reason_id`.
- Visão 360 da Opportunity implementada com dados comerciais, vínculos, etapa atual e histórico completo.
- Tela de Pipeline substituiu o placeholder da Sprint 5 por Kanban comercial funcional.
- Kanban implementado com contadores, valores por etapa, filtros e cards de oportunidades.
- Drag-and-drop implementado entre etapas utilizando a mesma regra transacional do backend.
- Movimentação para Perdido exibe modal obrigatório para seleção do motivo da perda.
- Endpoint de movimentação passou a suportar resposta JSON para atualização via Kanban.
- Stage History, auditoria, regras de integridade e constraints PostgreSQL preservados durante movimentações.
- Banco PostgreSQL real atualizado preservando Users, Companies, Contacts e Leads existentes.
- Fluxo visual validado no navegador com criação de Opportunity, movimentação Novo → Qualificação e movimentação para Perdido com Loss Reason.
- Testes de estrutura, CRUD, RBAC, histórico, Loss Reasons, Actions e Kanban aprovados.
- Laravel Pint aprovado sem inconsistências.
- PHPStan/Larastan aprovado sem erros.
- Build Vite de produção aprovado.
- `git diff --check` aprovado sem inconsistências.

### Sprint 6 — Atividades

- Implementado módulo completo de Activities com CRUD, filtros, RBAC, auditoria e soft delete.
- Activities passaram a suportar Ligações, E-mails, WhatsApp, Reuniões, Notas e outras interações comerciais.
- Implementado vínculo de Activities com Companies, Contacts, Leads e Opportunities.
- Implementado módulo completo de Tasks com responsáveis, prioridades, prazos e estados Pending, In Progress, Completed e Cancelled.
- Transições de Tasks passaram a controlar automaticamente `started_at`, `completed_at` e `cancelled_at`.
- Implementado RBAC e auditoria para criação, edição e exclusão de Tasks.
- Implementado Follow-up como especialização comercial de Task, exigindo canal, prazo e vínculo comercial.
- Follow-ups suportam Ligação, E-mail, WhatsApp e Reunião.
- Conclusão de Follow-up passou a gerar automaticamente uma Activity correspondente e vinculada ao histórico comercial.
- Implementada proteção contra conclusão duplicada de Follow-ups e contra conclusão pelo fluxo comum de Tasks.
- Implementada Timeline Comercial unificando Activities, Follow-ups abertos e Tasks comerciais em ordem cronológica.
- Follow-ups concluídos são representados somente pela Activity gerada, evitando duplicidade no histórico.
- Tasks internas sem vínculo comercial são excluídas da Timeline Comercial.
- Timeline recebeu filtros por tipo de evento, canal, status, empresa, responsável e período.
- Interface autenticada atualizada com módulos Atividades, Tarefas e Timeline da Sprint 6.
- Banco PostgreSQL real atualizado preservando os registros existentes.
- Fluxos funcionais validados no navegador para Activities, Tasks, Follow-ups e Timeline.
- Suite completa aprovada com 240 testes e 932 assertions.
- Laravel Pint aprovado sem inconsistências.
- PHPStan/Larastan aprovado sem erros.
- Build Vite de produção aprovado.
- `git diff --check` aprovado sem inconsistências.

### Sprint 7 — Importação / TASK-080 CSV

- Fundação do módulo de importações adicionada com tabelas `imports` e `import_rows`, models `DataImport` e `ImportRow`, relacionamentos, constraints PostgreSQL e índices operacionais.
- Fluxo autenticado de upload CSV implementado com Form Request, Policy, RBAC (`imports.view`, `imports.create`, `imports.delete`), controller fino, Actions, Query de listagem e auditoria sanitizada.
- Arquivos recebem nome interno UUID e são armazenados em disco Laravel privado dedicado, sem acesso público direto; nome original é preservado apenas para exibição e auditoria.
- Reader CSV nativo implementado com `SplFileObject`, UTF-8/BOM, cabeçalho, linhas vazias, aspas e detecção conservadora de vírgula, ponto e vírgula ou TAB.
- Parsing síncrono em streaming persiste `import_rows` em lotes configuráveis, preserva o número físico da linha, mantém `normalized_data` nulo e não cria entidades comerciais.
- Máquina de estados inicial limitada a `uploaded`, `processing`, `parsed` e `failed`, com falhas seguras e sem conteúdo integral do CSV no Audit Log.
- Telas responsivas de listagem, nova importação e confirmação de resultado adicionadas à navegação da Sprint 7, sem antecipar preview ou column mapper.
- Suíte completa aprovada com 277 testes e 1070 assertions no banco exclusivo `health_prospect_crm_test`; Pint, PHPStan/Larastan, instalação pnpm congelada, build Vite e `git diff --check` aprovados.
- Revisão pré-commit reforçou o parser com validação estrutural estrita, rejeição de aspas não fechadas, headers vazios/duplicados e divergência de colunas, usando códigos de falha sanitizados.
- Numeração de `import_rows` passou a preservar a linha física inicial do registro, inclusive com campos CSV multilinha e linhas vazias intermediárias.
- Nome original passou a remover caminhos Unix/Windows e caracteres de controle, além de ser limitado a 255 caracteres sem influenciar o caminho interno UUID.
- Exclusão passou a tratar arquivo ausente de forma idempotente e a preservar registros para retry quando o filesystem falha, documentando a ausência de atomicidade distribuída.
- Permissão legada `imports.execute` é removida de forma cirúrgica por migration e seeder idempotente, incluindo associações residuais, sem afetar permissões não relacionadas.
- Testes PostgreSQL foram ampliados para defaults, JSONB, constraints, índices, FKs e ações `ON DELETE`.

### Sprint 7 — Importação / TASK-081 XLSX

- Suporte a upload e interpretação de arquivos XLSX adicionado ao fluxo autenticado de importações, preservando armazenamento privado, nomes internos UUID, RBAC e auditoria sanitizada.
- PhpSpreadsheet adicionado como dependência com requisitos de plataforma validados, sem ignorar extensões PHP obrigatórias.
- Reader XLSX dedicado implementado em modo somente dados, persistindo tipos escalares em `import_rows` por lotes e preservando os números físicos das linhas sem normalização comercial.
- Somente a primeira worksheet é processada, inclusive quando oculta; worksheets posteriores são ignoradas e uma primeira worksheet vazia falha sem fallback automático.
- Cabeçalhos vazios ou duplicados, arquivos corrompidos e dimensões acima dos limites configuráveis são rejeitados com falhas transacionais e códigos sanitizados.
- Inspeção conservadora do ZIP e `listWorksheetInfo()` antecedem o carregamento restrito à primeira worksheet; limites e read filter reduzem, sem eliminar por completo, o maior risco de memória inerente ao XLSX.
- Fórmulas são preservadas como conteúdo sem cálculo durante a importação; column mapper, preview, deduplicação e relatório permanecem fora do escopo desta tarefa.
- Constraint PostgreSQL de tipos de importação ampliada de forma reversível para aceitar `csv` e `xlsx`.
- Interface e validação de upload atualizadas para aceitar somente CSV e XLSX, mantendo o limite configurável de tamanho.

### Sprint 7 — Importação / TASK-082 Column Mapper

- Column Mapper adicionado para importações interpretadas, com catálogo explícito (`ImportFieldCatalog`) e whitelist de targets de Company, Contact e Lead, sem expor campos internos de relacionamento.
- Auto-suggest conservador implementado somente para cabeçalhos de alta confiança, mantendo nomes ambíguos sem seleção automática; a tela apresenta até três amostras distintas por coluna.
- RBAC ampliado com `imports.update`, aplicado pela `ImportPolicy` e distribuído aos papéis operacionais definidos pelo seeder de menor privilégio.
- Mapping persistido em `imports.metadata.mapping`, incluindo versão, usuário, data, colunas mapeadas e ignoradas; remapping reconstrói os dados normalizados e remove targets anteriores.
- `import_rows.normalized_data` passou a ser gerado a partir do mapping, com normalização conservadora de strings, e-mails, telefones, websites, identificação fiscal brasileira com país explícito, prioridades, temperatura e inteiros não ambíguos.
- `import_rows.original_data` permanece preservado; o mapper não cria Company, Contact, Lead ou Opportunity e não antecipa Preview, deduplicação ou relatório de importação.
- Atualizações de mapping são transacionais, processadas em chunks e registradas em auditoria sem incluir os valores comerciais das linhas.
- Validação final aprovada com 76 testes focados e 331 assertions, suíte completa de 316 testes e 1.266 assertions, Pint, PHPStan/Larastan, Composer, auditoria de dependências, requisitos de plataforma, instalação pnpm congelada, build Vite e `git diff --check`.

### Sprint 7 — Importação / TASK-083 Preview

- Preview read-only adicionado em `GET /imports/{dataImport}/preview`, protegido por `imports.view` e disponível somente para importações interpretadas com mapping válido e `normalized_data` gerado.
- Validador transitório classifica cada linha como válida, com aviso ou com erro usando apenas targets mapeados e regras derivadas dos schemas e Models de Company, Contact e Lead.
- Validações cobrem e-mail, URLs HTTP(S), LinkedIn, telefones ambíguos, CNPJ brasileiro com país explícito, enums, inteiros, limites numéricos, comprimentos e identificadores mínimos por grupo, com códigos internos estáveis e mensagens em português.
- Filtros por classificação, contadores completos e paginação de 25, 50 ou 100 linhas são calculados em uma varredura ordenada por lotes, sem persistir a classificação e sem carregar todas as linhas em memória.
- Interface Blade apresenta resumo, dados compactos de empresa/contato/lead e detalhes de `original_data`, `normalized_data` e issues; todo conteúdo importado usa escaping, inclusive HTML e fórmulas exibidas apenas como texto.
- A visualização não altera imports, import_rows, counters, status, vínculos ou dados JSON, não gera audit log e não cria Company, Contact, Lead ou Opportunity; deduplicação, merge e relatório permanecem pendentes.
- Validação funcional aprovada com 99 testes focados e 411 assertions e suíte completa de 339 testes e 1.346 assertions no banco exclusivo `health_prospect_crm_test`.

### Sprint 7 — Importação / TASK-084 Deduplicação

- Etapa de Deduplicação adicionada com tela protegida por `imports.view`, análise e decisões protegidas por `imports.update`, sem criar, atualizar, mesclar ou excluir entidades comerciais.
- Migration nova adiciona `import_rows.dedup_data` JSONB nullable; o contrato versionado separa Company, Contact e Lead, registra candidatos mínimos do CRM ou de uma linha anterior da mesma importação, razões determinísticas, status e decisão.
- Company usa identidade fiscal composta por país e documento como match forte, sem cruzar países; nomes, e-mail, telefone e website são sinais possíveis conservadores, com inclusão de registros soft-deleted.
- Contact considera LinkedIn e sinais de e-mail/telefone com contexto de Company, sem transformar nome comum isolado em match forte; Lead usa e-mail/telefone/WhatsApp fortes e nome com empresa como possível.
- Duplicidades internas usam mapas de chaves normalizadas e preservam a primeira linha física como candidato, sem comparação O(N²); consultas ao CRM são executadas em lote por chunk e os candidatos ficam limitados a cinco por grupo.
- Decisões `create_new`, `use_existing`, `reuse_import_row` e `skip` são revalidadas contra o resultado persistido, tipo, origem, mesma importação e existência atual; identidade fiscal exata de Company bloqueia `create_new`.
- Usuários sem permissão de leitura da entidade recebem somente indicação genérica do candidato; a interface usa escaping Blade e não interpreta HTML ou fórmulas.
- `imports.duplicate_rows` conta linhas com match forte/exato e `imports.metadata.dedup.summary` mantém contadores detalhados; reanálise substitui resultados e remapping invalida `dedup_data`, metadata e contador.
- A análise registra auditoria sanitizada sem dados comerciais completos e persiste `analyzed_at`; a etapa final deverá revalidar constraints fortes devido a staleness e race conditions possíveis.
- Regressão focada aprovada com 110 testes e 479 assertions e suíte completa com 350 testes e 1.414 assertions no banco exclusivo `health_prospect_crm_test`.
