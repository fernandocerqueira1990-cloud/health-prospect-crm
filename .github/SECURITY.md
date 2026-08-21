# Security Policy

## Supported version

O projeto está em desenvolvimento ativo. Correções de segurança são aplicadas sobre a branch principal (`main`) e sobre a versão mais recente do código.

## Reportando uma vulnerabilidade

Não publique detalhes de vulnerabilidades, credenciais ou dados sensíveis em Issues públicas.

Se identificar um problema de segurança:

1. prefira o recurso **GitHub Security Advisories / Report a vulnerability**, quando disponível neste repositório;
2. caso esse recurso não esteja disponível, entre em contato de forma privada com o mantenedor pelo perfil do GitHub antes de divulgar detalhes técnicos;
3. informe, quando possível, o componente afetado, impacto, passos de reprodução e evidências sem incluir dados reais de terceiros.

## Escopo de segurança

O projeto aplica controles de segurança em autenticação, autorização/RBAC, sessões, CSRF, rate limiting, headers HTTP, proxies/HTTPS, auditoria, uploads/importações, logs e dependências.

A documentação técnica de arquitetura e hardening está em [`docs/architecture/SECURITY.md`](../docs/architecture/SECURITY.md).

## Secrets e dados

Credenciais reais, tokens, chaves privadas, dumps de banco e arquivos `.env` não devem ser versionados. Dados utilizados em demonstrações públicas devem ser fictícios ou anonimizados.