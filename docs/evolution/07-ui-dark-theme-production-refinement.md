# 07 — UI Dark Theme & Production Refinement

## Contexto

Esta etapa consolidou a identidade visual do CRM X em ambiente de produção, com foco em legibilidade, consistência visual, experiência de uso e refinamento da interface comercial.

O trabalho foi realizado após a estabilização da Sprint 11, sem alteração das regras principais de negócio.

## Objetivos

- Padronizar a interface interna em tema escuro.
- Melhorar contraste e legibilidade.
- Atualizar a identidade visual TechSallus.
- Refinar a tela de login.
- Tornar componentes de status mais claros e consistentes.
- Melhorar navegação e percepção visual da aplicação.
- Validar todas as alterações com suíte automatizada e análise estática.

## Alterações realizadas

### Tela de login

A tela de login foi redesenhada com:

- fundo azul escuro;
- identidade visual TechSallus;
- formulário centralizado;
- SVG animado;
- conexões visuais entre o login e os módulos:
  - Leads;
  - Oportunidades;
  - Campanhas;
  - Indicadores;
- animações de partículas;
- melhoria na distribuição visual;
- inputs e botão com maior contraste;
- layout responsivo.

### Identidade visual

Foram adicionados assets transparentes:

- `techsallus-logo-transparent.png`
- `techsallus-symbol-transparent.png`

A sidebar passou a utilizar o logo transparente, eliminando fundos brancos incompatíveis com o tema escuro.

### Tema interno

A aplicação passou a utilizar uma paleta baseada em:

- azul-marinho como cor principal;
- azul escuro para cards e tabelas;
- branco para conteúdo principal;
- azul claro para textos secundários e links;
- cores funcionais apenas quando agregam significado.

### Listagens

Foram revisadas telas como:

- Empresas;
- Leads;
- Atividades;
- Dashboard;
- Notificações.

Os principais ajustes foram:

- remoção de áreas excessivamente claras;
- melhoria de contraste;
- padronização das tabelas;
- melhoria na legibilidade dos títulos;
- ajuste de links e ações;
- harmonização visual entre sidebar, topbar e conteúdo.

### Badges e status

O componente `status-badge` foi refinado para melhorar contraste e leitura.

Variantes existentes:

- neutral;
- info;
- success;
- warning;
- danger.

Badges claros passaram a utilizar texto escuro, evitando problemas de contraste como:

- Média;
- Alta;
- Novo;
- Frio.

### Dashboard

O Dashboard recebeu ajustes visuais em:

- cards de métricas;
- central comercial;
- pendências;
- pipeline parado;
- textos secundários;
- contraste geral.

### Notificações

A interface de notificações foi refinada dentro da nova identidade visual.

O atalho global de notificações permanece integrado à topbar e ao contador de não lidas.

## Ambiente

Ambiente atual:

- Laravel 13
- PHP 8.4
- PostgreSQL
- Redis
- Nginx
- PHP-FPM
- Vite
- Blade
- Tailwind CSS

## Validação

Após as alterações visuais e integração com a versão atual da `main`, foram executados:

```text
PHPUnit
551 tests
2384 assertions
0 failures

