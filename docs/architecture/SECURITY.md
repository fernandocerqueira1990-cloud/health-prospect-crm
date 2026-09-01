# Segurança — CRM X

## Objetivo

Aplicar **segurança por padrão** em todas as camadas da aplicação, preservando confidencialidade, integridade, auditabilidade e disponibilidade.

## Estado atual

A Sprint 10 consolidou o trabalho de **Security & Production Hardening** com validações de autenticação, autorização, sessões, proxies, HTTPS, headers, imports, logs, dependências e CI.

Os controles descritos abaixo representam o estado atual da arquitetura, com itens de produção dependentes da configuração real do ambiente.

## Autenticação e sessão

- autenticação Laravel;
- senhas com algoritmo seguro suportado pelo framework;
- mensagens uniformes de falha de login;
- rate limiting por identidade normalizada e IP confiável;
- renovação de sessão após autenticação;
- invalidação de sessão e CSRF no logout;
- bloqueio de usuários inativos;
- controle de acesso de usuários de teste por feature flag;
- sessões criptografadas, HttpOnly e SameSite `lax`;
- cookie `Secure` recomendado/obrigatório em produção HTTPS.

## Autorização

O sistema utiliza **RBAC + Policies/Gates no backend**.

Papéis e permissões são tratados de forma explícita e a interface não é considerada mecanismo de segurança. A autorização é aplicada nas rotas e ações do domínio.

Controles adicionais incluem:

- proteção contra IDOR;
- restrições de privilege escalation;
- proteção do último administrador ativo;
- prevenção de self-lockout administrativo crítico;
- bloqueio de atribuições administrativas indevidas.

## Dados e banco

- PostgreSQL deve permanecer em rede privada/local;
- Redis deve permanecer privado/local;
- Eloquent/Query Builder e prepared statements são preferidos;
- entrada do usuário nunca deve ser concatenada em SQL;
- constraints e integridade são aplicadas também no banco;
- princípio de least privilege para usuários de banco;
- backups devem ser protegidos e testados por restore antes de produção definitiva.

## Web e transporte

- HTTPS é obrigatório em produção;
- proxies confiáveis são configurados explicitamente;
- forwarded headers só são aceitos de proxies confiáveis;
- HSTS é configurável e somente emitido sobre HTTPS reconhecido;
- headers de segurança incluem `nosniff`, `SAMEORIGIN`, Referrer Policy e Permissions Policy;
- CSP conservadora aplicada no middleware web;
- CSRF ativo;
- validação de input e escaping de output;
- sessões e cookies configurados conforme o ambiente.

## Uploads e importações

O fluxo CSV/XLSX possui validações específicas para reduzir riscos de arquivos maliciosos e corrupção de dados:

- whitelist de formatos suportados;
- validação de extensão, MIME e tamanho;
- rejeição de extensões duplas perigosas;
- armazenamento privado com nomes internos UUID;
- limites de linhas, colunas, entradas e descompressão;
- proteção contra ZIP bomb e archive traversal;
- bloqueio de macros, conteúdo ativo e links externos no XLSX;
- XML sem DTD/entidades externas;
- fórmulas não são avaliadas;
- estado de execução assinado e revalidado;
- idempotência e proteção contra replay/concorrência;
- payloads comerciais não são gravados integralmente em logs/auditoria.

## Logs, auditoria e secrets

Nunca versionar ou registrar:

- senhas;
- tokens;
- Authorization headers;
- cookies e sessions;
- chaves privadas;
- `.env` real;
- credenciais de banco ou serviços externos;
- payloads integrais de importação.

A sanitização centralizada remove ou redige valores sensíveis em logs e auditoria e neutraliza caracteres de controle utilizados em log injection.

Em produção, a recomendação é:

```text
APP_ENV=production
APP_DEBUG=false
LOG_STACK=daily
LOG_LEVEL=warning
```

A retenção deve ser definida conforme a política operacional.

## Dependências e CI

- `composer.lock` e `pnpm-lock.yaml` são versionados;
- Composer e pnpm devem ser auditados periodicamente;
- GitHub Actions utiliza permissões restritas de `GITHUB_TOKEN`;
- Actions críticas são fixadas em commits imutáveis;
- execução de CI possui timeout;
- testes, análise estática e build fazem parte do fluxo de qualidade.

## Infraestrutura

Exposição pública deve ser mínima.

Permitido conforme arquitetura de deploy:

- 80 somente para redirect quando necessário;
- 443 para HTTPS;
- SSH somente com política restritiva.

Não expor publicamente:

- PostgreSQL;
- Redis;
- Prometheus;
- Loki;
- exporters;
- interfaces administrativas internas.

Controles recomendados:

- firewall;
- usuário não-root;
- chaves SSH;
- atualizações regulares;
- backups offsite;
- monitoramento e alertas;
- Fail2ban quando adequado ao cenário de acesso.

## API e integrações

A superfície pública da API ainda está em evolução. Quando exposta, deve incluir:

- autenticação explícita;
- tokens revogáveis ou mecanismo equivalente;
- scopes/permissions quando necessário;
- rate limiting dedicado;
- validação de payload;
- logs sem secrets;
- idempotency keys em endpoints críticos quando aplicável;
- webhooks autenticados e validados.

## LGPD e privacidade

A evolução do produto deve considerar:

- origem e finalidade dos dados;
- base legal/consentimento quando aplicável;
- retenção;
- anonimização;
- exclusão;
- exportação;
- controle de acesso;
- trilha de auditoria;
- minimização de dados.

## Configuração de produção

O hardening de código não substitui configuração segura de infraestrutura. Antes de um deploy definitivo, validar obrigatoriamente HTTPS real, cookies Secure, HSTS, proxies confiáveis, firewall, backups, restore, workers, scheduler, logs e observabilidade.
