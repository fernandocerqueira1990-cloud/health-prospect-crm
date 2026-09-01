# API — CRM X

## Base

`/api/v1`

## Autenticação

Laravel Sanctum é a opção inicial preferencial, salvo decisão posterior documentada.

## Endpoints planejados

### Companies
- GET /api/v1/companies
- POST /api/v1/companies
- GET /api/v1/companies/{id}
- PATCH /api/v1/companies/{id}
- DELETE /api/v1/companies/{id}

### Contacts
- GET /api/v1/contacts
- POST /api/v1/contacts
- GET /api/v1/contacts/{id}
- PATCH /api/v1/contacts/{id}
- DELETE /api/v1/contacts/{id}

### Leads
- GET /api/v1/leads
- POST /api/v1/leads
- GET /api/v1/leads/{id}
- PATCH /api/v1/leads/{id}
- DELETE /api/v1/leads/{id}
- POST /api/v1/leads/{id}/qualify

### Opportunities
- GET /api/v1/opportunities
- POST /api/v1/opportunities
- GET /api/v1/opportunities/{id}
- PATCH /api/v1/opportunities/{id}
- POST /api/v1/opportunities/{id}/stage

### Activities
- GET /api/v1/activities
- POST /api/v1/activities

### Tasks
- GET /api/v1/tasks
- POST /api/v1/tasks
- PATCH /api/v1/tasks/{id}

### Campaigns
- GET /api/v1/campaigns
- POST /api/v1/campaigns

### Imports
- POST /api/v1/imports
- GET /api/v1/imports/{id}

### Tracking
- POST /api/v1/tracking/events

### Webhooks
- POST /api/v1/webhooks
- GET /api/v1/webhooks

## Padrão de resposta

Sucesso:

```json
{
  "data": {},
  "meta": {}
}
```

Erro de validação:

```json
{
  "message": "Validation failed.",
  "errors": {
    "field": ["Mensagem"]
  }
}
```

## Integrações externas

n8n deve consumir a API.

Não conceder acesso direto do n8n às tabelas operacionais.

## Segurança

- autenticação;
- autorização;
- rate limit;
- idempotência quando apropriado;
- logs;
- request IDs;
- validação.
