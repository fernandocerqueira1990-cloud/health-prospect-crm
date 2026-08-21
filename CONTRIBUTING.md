# Contribuindo com o Health Prospect CRM

Obrigado pelo interesse no projeto.

O Health Prospect CRM é desenvolvido de forma incremental, com foco em qualidade, segurança, rastreabilidade e documentação.

## Antes de começar

Leia:

1. [`README.md`](README.md)
2. [`docs/architecture/`](docs/architecture/README.md)
3. [`docs/development/`](docs/development/README.md)
4. [`docs/portfolio/ROADMAP.md`](docs/portfolio/ROADMAP.md)

## Fluxo recomendado

1. crie uma branch com escopo claro;
2. implemente apenas o necessário para a mudança proposta;
3. mantenha autorização e validação no backend;
4. adicione ou atualize testes;
5. execute as validações de qualidade;
6. atualize a documentação quando necessário;
7. abra um Pull Request objetivo e descritivo.

## Qualidade mínima

Antes do Pull Request, execute:

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
pnpm run build
```

Também é recomendado:

```bash
composer validate
composer audit
pnpm audit
```

## Segurança

Nunca inclua no repositório:

- `.env` real;
- senhas ou tokens;
- chaves privadas;
- dumps de banco;
- backups;
- credenciais de serviços externos;
- dados comerciais ou pessoais reais usados apenas para testes.

Consulte também [`SECURITY.md`](SECURITY.md).

## Commits

Prefira mensagens curtas e descritivas, por exemplo:

```text
feat(leads): add advanced filters
fix(imports): preserve international phone numbers
docs(portfolio): update project showcase
```

## Pull Requests

Um PR deve informar, quando aplicável:

- objetivo da mudança;
- principais alterações;
- riscos e decisões técnicas;
- testes executados;
- impacto em banco, segurança ou APIs;
- screenshots para alterações visuais.

Evite misturar refactors grandes, novas features e correções não relacionadas no mesmo PR.
