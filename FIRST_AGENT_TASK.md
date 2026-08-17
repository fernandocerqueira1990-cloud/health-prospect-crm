# PRIMEIRA TAREFA PARA O CODEX

Leia integralmente:

- PROMPT_MESTRE_V2.md
- MASTER.md
- ARCHITECTURE.md
- DATABASE.md
- SECURITY.md
- API.md
- ROADMAP.md
- TASKS.md

## Contexto

O repositório atualmente contém uma POC em React + TypeScript + Vite + Supabase.

A arquitetura oficial mudou para Laravel/PHP + PostgreSQL + Redis + Apache em Debian.

Não apague nem sobrescreva a versão antiga sem preservar seu histórico.

## Objetivo

Preparar a migração para o novo núcleo Laravel.

## Tarefa

1. Analise todo o repositório atual.
2. Informe quais arquivos pertencem à POC antiga.
3. Proponha a estratégia mais segura de migração.
4. Inicialize um projeto Laravel compatível com a arquitetura definida.
5. Configure PostgreSQL via `.env.example`.
6. Configure Redis.
7. Configure frontend Blade + Tailwind.
8. Configure testes.
9. Configure Laravel Pint.
10. Adicione health check inicial.
11. Não implemente ainda Companies/Contacts/Leads.
12. Execute todos os testes.
13. Corrija falhas.
14. Atualize TASKS.md e CHANGELOG.md.

## Restrições

- Não adicionar Supabase ao novo núcleo.
- Não utilizar SQLite como banco de produção.
- Não criar regras de negócio ainda.
- Não alterar decisões do DATABASE.md.
- Não adicionar dependências sem explicar a necessidade.
- Não incluir credenciais reais.
- Não fazer deploy de produção.
- Não apagar a POC antiga silenciosamente.

## Entrega

Apresente:
- estratégia adotada;
- arquivos criados;
- arquivos removidos/movidos;
- dependências;
- comandos executados;
- testes;
- riscos;
- próxima tarefa.
