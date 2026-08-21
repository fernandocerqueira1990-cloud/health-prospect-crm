# Security Policy

A segurança é uma prioridade do Health Prospect CRM.

## Versões suportadas

O projeto está em desenvolvimento ativo. Correções de segurança são aplicadas à branch principal e às branches de desenvolvimento em uso.

## Reportando uma vulnerabilidade

Se você identificar uma vulnerabilidade:

1. evite publicar detalhes de exploração em Issues, Discussions ou Pull Requests públicos;
2. utilize o canal privado de Security Advisories do GitHub, quando disponível para este repositório;
3. caso esse canal não esteja disponível, abra uma Issue sem detalhes técnicos sensíveis solicitando um canal privado de contato.

Ao reportar, inclua quando possível:

- descrição do problema;
- componente afetado;
- passos mínimos para reprodução;
- impacto esperado;
- sugestão de correção, se houver.

## Escopo de segurança

A arquitetura considera controles de autenticação, RBAC, Policies/Gates, proteção contra IDOR, rate limiting, sessões, CSRF, security headers, proxies confiáveis, sanitização de logs e segurança do fluxo CSV/XLSX.

A documentação técnica detalhada está em [`docs/architecture/SECURITY.md`](docs/architecture/SECURITY.md).

## Dados sensíveis

Nunca envie em reports públicos:

- credenciais reais;
- tokens;
- chaves privadas;
- cookies ou sessões;
- dumps de banco;
- dados pessoais/comerciais reais.

## Divulgação responsável

Solicitamos que detalhes exploráveis sejam mantidos privados até que o problema possa ser analisado e corrigido.
