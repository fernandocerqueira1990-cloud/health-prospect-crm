# Desenvolvimento — CRM X

Esta seção reúne instruções de apoio ao desenvolvimento local, testes e qualidade do projeto.

## Requisitos

- PHP 8.4.1+
- Composer
- Node.js 22+
- pnpm
- PostgreSQL
- Redis

## Setup local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
pnpm install
pnpm run build
```

Para desenvolvimento com o servidor embutido do Laravel:

```bash
php artisan serve
```

A aplicação ficará disponível por padrão em `http://127.0.0.1:8000`.

## Banco de testes

Os testes não devem utilizar o banco principal da aplicação. Utilize um banco PostgreSQL dedicado com sufixo `_test`.

Exemplo:

```bash
createdb -O health_prospect_crm health_prospect_crm_test
php artisan test
```

O projeto possui proteção para impedir a inicialização do ambiente de testes quando o banco configurado não termina em `_test`.

## Qualidade

Antes de considerar uma alteração concluída, execute:

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
pnpm run build
```

Também é recomendado validar:

```bash
composer validate
composer audit
pnpm audit
```

## CI

O GitHub Actions executa verificações automatizadas de qualidade e testes. O workflow utiliza dependências reproduzíveis pelos lockfiles e permissões restritas do `GITHUB_TOKEN`.

## Convenções

- não versionar `.env`, credenciais, dumps, backups ou chaves privadas;
- aplicar autorização no backend, não apenas na interface;
- manter validações em Form Requests ou camada equivalente;
- adicionar testes para novas regras e correções relevantes;
- preservar documentação e `CHANGELOG.md` quando houver mudança de comportamento;
- manter commits objetivos e Pull Requests com escopo claro.

## Documentação relacionada

- [Arquitetura](../architecture/README.md)
- [Roadmap](../portfolio/ROADMAP.md)
- [Histórico de evolução](../evolution/README.md)
- [Documentação interna](../internal/README.md)
