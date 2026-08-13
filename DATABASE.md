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
- source_id
- notes
- timestamps
- deleted_at

### contacts
- id
- company_id
- name
- job_title
- department
- email
- phone
- whatsapp
- influence_level
- buying_role
- notes
- timestamps
- deleted_at

### contact_social_profiles
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
- code
- channel_id
- starts_at
- ends_at
- budget
- active
- metadata JSONB
- timestamps

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
- type
- status
- total_rows
- imported_rows
- duplicate_rows
- failed_rows
- started_at
- finished_at
- metadata JSONB

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
