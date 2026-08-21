# DATABASE — Health Prospect CRM

## Banco principal

PostgreSQL.

## Princípios

- integridade referencial;
- foreign keys;
- índices;
- constraints;
- migrations versionadas;
- timestamps;
- auditoria;
- JSONB somente quando apropriado;
- evitar dados duplicados sem necessidade.

## Entidades

### users
Usuários da aplicação.

Campos principais:
- id
- name
- email
- password
- active
- last_login_at
- timestamps

### roles
- id
- name
- slug

### permissions
- id
- name
- slug

### companies
- id
- legal_name
- trade_name
- tax_id
- tax_id_country nullable, código ISO 3166-1 alpha-2
- segment
- category
- website
- phone
- email
- street
- number
- complement
- district
- city
- state
- postal_code
- employee_count_estimate
- assigned_user_id
- source_id nullable, reservado sem FK até a implementação de `lead_sources`
- priority: low, medium, high, critical
- notes
- timestamps
- deleted_at

Regras implementadas na Sprint 2:
- `assigned_user_id` referencia `users`, com `ON DELETE SET NULL`;
- `tax_id_country` identifica explicitamente o país, sem inferência pelo comprimento do documento; registros legados permanecem nulos até classificação explícita;
- `(tax_id_country, tax_id)` possui índice unique parcial quando ambos estão preenchidos;
- CNPJs com país `BR` são armazenados somente com 14 dígitos e validados antes da persistência; identificadores de outros países usam normalização conservadora;
- `source_id` permanece nullable e sem foreign key para não antecipar o módulo Lead Sources;
- índices operacionais cobrem `legal_name`, `trade_name` não nulo, identidade fiscal composta, `city`, `state`, `assigned_user_id` e `priority`.

### contacts
- id
- company_id
- name
- job_title
- department
- email
- phone
- whatsapp
- linkedin_url
- decision_role
- influence_level
- is_primary
- active
- notes
- timestamps
- deleted_at

Regras implementadas na Sprint 3:
- `company_id` é obrigatório, indexado pela foreign key e usa `ON DELETE RESTRICT`; soft delete de Company preserva integralmente seus Contacts;
- a relação histórica `Contact::company()` inclui Companies soft-deleted; novos vínculos aceitam somente Companies ativas, enquanto updates podem manter a Company arquivada atual ou mover para outra ativa;
- nome, e-mail, telefones e URLs são normalizados antes da persistência; e-mail não possui unicidade global;
- filtros telefônicos reutilizam a mesma normalização da persistência; a busca geral mantém o termo textual e deriva separadamente um candidato normalizado para `phone` e `whatsapp`;
- `decision_role` usa vocabulário evolutivo em `varchar`: `decision_maker`, `influencer`, `champion`, `user`, `technical`, `procurement`, `financial`, `gatekeeper`, `blocker` e `other`;
- `influence_level` aceita `low`, `medium`, `high` e `critical`;
- apenas um Contact ativo e não excluído pode ser principal por Company, garantido por Action transacional, lock da Company e índice unique parcial;
- mutações bloqueiam primeiro todas as Companies envolvidas em ordem crescente de ID e somente depois os Contacts, evitando inversão de locks em movimentações e exclusões concorrentes;
- desativar ou excluir o principal deixa a Company sem contato principal; não há promoção automática;
- índices operacionais cobrem `company_id`, `name`, e-mail não nulo, `decision_role`, `influence_level` e `active`.
- a seção Contacts da Company Show consulta no máximo 10 registros por página, ordenados por principal e nome, usando o parâmetro independente `contacts_page`.

### contact_social_profiles (futuro, fora da Sprint 3)
- id
- contact_id
- network
- profile_url
- username
- external_id
- verified
- metadata JSONB
- timestamps

### lead_sources
- id
- name
- slug
- active
- timestamps

### channels
- id
- name
- slug
- active
- timestamps

### campaigns
- id
- name
- description nullable
- status
- channel_id nullable
- owner_user_id nullable
- start_date nullable
- end_date nullable
- budget nullable
- currency, código ISO 4217 com default BRL
- utm_source nullable
- utm_medium nullable
- utm_campaign nullable
- utm_content nullable
- utm_term nullable
- notes nullable
- timestamps
- deleted_at

Regras implementadas na TASK-090:
- status aceita `draft`, `planned`, `active`, `paused`, `completed` e `cancelled`;
- nome é obrigatório e não pode conter somente espaços;
- `end_date` não pode ser anterior a `start_date` e orçamento não pode ser negativo;
- moeda é armazenada em `char(3)`, limitada a três letras maiúsculas compatíveis com códigos ISO 4217, com `BRL` como default;
- `channel_id` e `owner_user_id` usam FKs nullable com `ON DELETE SET NULL` para preservar a campanha;
- Campaign usa soft delete; público, métricas, tracking e integrações externas permanecem fora desta fundação.

### leads
- id
- company_id nullable
- contact_id nullable
- assigned_user_id
- source_id
- channel_id
- campaign_id nullable
- status
- priority
- temperature
- score
- first_touch_source_event_id nullable
- last_touch_source_event_id nullable
- qualified_at nullable
- converted_at nullable
- lost_at nullable
- last_interaction_at nullable
- next_action_at nullable
- notes
- timestamps
- deleted_at

### lead_source_events
- id
- lead_id
- event_type
- source
- medium
- campaign
- channel
- referrer
- landing_page
- utm_source
- utm_medium
- utm_campaign
- utm_content
- utm_term
- social_network
- external_id
- occurred_at
- metadata JSONB
- timestamps

### pipelines
- id
- name
- active
- timestamps

### pipeline_stages
- id
- pipeline_id
- name
- slug
- position
- probability
- is_won
- is_lost
- active
- timestamps

### opportunities
- id
- company_id
- contact_id nullable
- lead_id nullable
- pipeline_id
- stage_id
- assigned_user_id
- title
- value
- probability
- expected_close_date
- status
- lost_reason_id nullable
- won_at nullable
- lost_at nullable
- timestamps
- deleted_at

### opportunity_stage_history
- id
- opportunity_id
- from_stage_id nullable
- to_stage_id
- changed_by
- entered_at
- exited_at nullable
- duration_seconds nullable
- notes nullable

### activities
- id
- company_id nullable
- contact_id nullable
- lead_id nullable
- opportunity_id nullable
- user_id
- type
- subject
- description
- occurred_at
- result
- next_action_at nullable
- timestamps

### tasks
- id
- assigned_user_id
- created_by
- company_id nullable
- contact_id nullable
- lead_id nullable
- opportunity_id nullable
- title
- description
- priority
- status
- due_at
- reminder_at nullable
- completed_at nullable
- timestamps

### loss_reasons
- id
- name
- active

### imports
- id
- user_id
- filename
- original_filename
- type
- status
- total_rows
- imported_rows
- duplicate_rows
- failed_rows
- started_at
- finished_at
- metadata JSONB
- timestamps

Regras implementadas na TASK-080:
- `user_id` referencia `users` com `ON DELETE RESTRICT`;
- `filename` guarda somente o nome interno UUID do arquivo no disco privado e `original_filename` é informação de exibição/auditoria;
- `type` aceita somente `csv` nesta tarefa;
- estados: `uploaded`, `processing`, `parsed`, `completed` e `failed`; `completed` é introduzido pela execução final da TASK-085;
- falhas de parsing persistem somente código técnico sanitizado em `metadata` (`invalid_header`, `column_count_mismatch`, `malformed_csv`, entre outros); detalhes de exceção permanecem nos logs;
- contadores usam zero como default seguro e `metadata` usa objeto JSONB vazio;
- o Column Mapper persiste em `metadata.mapping` a versão do contrato, data, usuário, mapa de coluna original para target permitido e lista de colunas ignoradas;
- índices cobrem usuário, tipo, status e datas operacionais.

### import_rows
- id
- import_id
- row_number
- status
- original_data JSONB
- normalized_data JSONB
- error_message nullable
- related_entity_type nullable
- related_entity_id nullable
- timestamps

Regras implementadas na TASK-080:
- `import_id` referencia `imports` com `ON DELETE CASCADE`;
- `(import_id, row_number)` é único e preserva a linha física do arquivo, inclusive quando linhas vazias são ignoradas;
- para registros com campos multilinha válidos, `row_number` representa a linha física em que o registro começou;
- `original_data` guarda o par cabeçalho/valor interpretado e permanece imutável durante mapping e remapping;
- `normalized_data` permanece nulo até o primeiro mapping e depois é integralmente reconstruído a partir de `original_data` e `imports.metadata.mapping`, sem criar entidades comerciais;
- `dedup_data` JSONB nullable guarda o contrato versionado da análise, separado de `original_data`, `normalized_data` e `error_message`, com status, candidatos mínimos por grupo e decisões para a etapa final;
- `execution_data` JSONB nullable guarda o resultado final versionado por grupo (`created`, `reused` ou `skipped`) e o estado sanitizado da linha, preservando decisões em `dedup_data` e sem copiar snapshots comerciais;
- candidatos podem apontar por ID para Company, Contact ou Lead no CRM, inclusive soft-deleted, ou para uma `ImportRow` anterior da mesma importação; não são copiados snapshots comerciais completos;
- `duplicate_rows` representa a quantidade de linhas com ao menos um match forte/exato; matches possíveis ficam somente no resumo `imports.metadata.dedup.summary`;
- remapping limpa `dedup_data`, remove `imports.metadata.dedup` e zera `duplicate_rows`; reanálises substituem integralmente o resultado anterior;
- `related_entity_type` e `related_entity_id` são campos simples e nulos, sem relação polimórfica prematura;
- status de linha inicial limitado a `parsed` e `failed`.

Contrato da execução final:
- `imports.metadata.execution_config` versão 1 registra `lead_source_slug = importacao` e a FK de Channel previamente validada quando novos Leads serão criados; o backend revalida existência e atividade na execução;
- `imports.metadata.execution` registra início, término, usuário executor, estado e resumo por linhas/entidades, sem `original_data` ou `normalized_data` completos;
- `imported_rows` conta linhas com ao menos uma criação ou reutilização comercial bem-sucedida; `failed_rows` soma linhas com falha ou bloqueadas; `duplicate_rows` continua contando matches fortes/exatos da deduplicação;
- `original_data`, `normalized_data`, `ImportRow.status` e `related_entity_*` não são alterados pela execução; múltiplas entidades por linha são representadas somente em `execution_data`;
- remapping anterior à execução limpa `dedup_data`, `execution_data`, metadata de dedup/execução, counters e timestamps para impedir resultados stale;
- não existe retry parcial automático, merge ou atualização de entidades reutilizadas no MVP.

### tracking_events
- id
- lead_id nullable
- anonymous_id nullable
- event
- page_url
- referrer
- utm_source
- utm_medium
- utm_campaign
- utm_content
- utm_term
- occurred_at
- metadata JSONB

### webhooks
- id
- name
- event
- url
- secret_encrypted
- active
- timestamps

### webhook_events
- id
- webhook_id nullable
- direction
- event
- payload JSONB
- status
- response_code nullable
- attempts
- processed_at nullable
- timestamps

### audit_logs
- id
- user_id nullable
- action
- auditable_type
- auditable_id
- before JSONB nullable
- after JSONB nullable
- ip_address
- user_agent
- created_at

## Analytics schema

Criar schema:

`analytics`

Views planejadas:

- vw_leads_daily
- vw_leads_by_source
- vw_leads_by_channel
- vw_leads_by_campaign
- vw_conversion_funnel
- vw_pipeline
- vw_sales_performance
- vw_campaign_performance
- vw_lead_response_time
- vw_lead_loss_reasons
- vw_leads_by_location

## Grafana

Usuário:

`grafana_reader`

Somente:
- CONNECT;
- USAGE no schema analytics;
- SELECT nas views autorizadas.

Nunca utilizar usuário administrador.
