# SECURITY — Health Prospect CRM

## Objetivo

Aplicar segurança por padrão.

## Autenticação

- Laravel authentication;
- passwords com algoritmo seguro suportado pelo Laravel;
- proteção contra brute force;
- rate limiting;
- sessão segura;
- logout;
- possibilidade futura de MFA.

## Autorização

RBAC.

Papéis iniciais:

- Administrador
- Gestor Comercial
- Supervisor
- Vendedor
- Marketing
- Analista
- Somente Leitura

Toda autorização deve ocorrer no backend por Policies/Gates.

Nunca confiar somente em esconder botões no frontend.

## Dados

- PostgreSQL em rede privada/local;
- Redis privado/local;
- backups protegidos;
- secrets em `.env`/secret manager;
- `.env` nunca no Git;
- criptografar valores sensíveis quando apropriado.

## Web

- HTTPS obrigatório;
- HTTP -> HTTPS;
- Secure cookies;
- HttpOnly cookies;
- SameSite adequado;
- CSRF;
- CSP a ser definida;
- validação de input;
- escaping de output;
- upload validado.

## API

- autenticação;
- rate limit;
- tokens revogáveis;
- escopos quando necessário;
- validação;
- logs sem segredos;
- idempotency keys em endpoints críticos quando necessário.

## SQL

- Eloquent/Query Builder;
- prepared statements;
- nunca concatenar entrada de usuário em SQL;
- least privilege.

## Auditoria

Registrar:
- criação;
- atualização;
- exclusão;
- mudanças de estágio;
- mudanças de permissão;
- operações administrativas;
- importações;
- integrações críticas.

## Infraestrutura

Expor:
- 80;
- 443;
- SSH somente com política restritiva.

Bloquear acesso público:
- PostgreSQL;
- Redis;
- Prometheus;
- Loki;
- exporters.

Utilizar:
- firewall;
- Fail2ban quando adequado;
- atualizações;
- usuário não-root;
- chaves SSH;
- backups offsite.

## LGPD

Preparar:
- origem do dado;
- finalidade;
- consentimento quando aplicável;
- retenção;
- anonimização;
- exclusão;
- exportação;
- controle de acesso;
- trilha de auditoria.

## Logs

Nunca registrar:
- senha;
- token completo;
- secret;
- session cookie;
- dados excessivamente sensíveis.

Produção:
- `APP_DEBUG=false`;
- stack traces não devem ser retornados ao cliente.

## Dependências

Executar periodicamente:
- composer audit;
- npm audit;
- análise de dependências;
- atualização planejada.
