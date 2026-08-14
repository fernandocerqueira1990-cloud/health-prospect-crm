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
