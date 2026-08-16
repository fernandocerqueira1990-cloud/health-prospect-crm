# 🏥 Health Prospect CRM

CRM para gestão de prospecção comercial B2B no setor de saúde.

O **Health Prospect CRM** é um projeto desenvolvido para centralizar e organizar o processo comercial de empresas que atuam com clínicas, hospitais, laboratórios, centros médicos e outros estabelecimentos privados de saúde.

O projeto nasceu a partir de uma necessidade prática: transformar uma base de leads inicialmente organizada em planilhas em uma aplicação capaz de estruturar empresas, contatos, oportunidades e atividades comerciais.

---

## 🎯 Objetivo do projeto

Construir um CRM comercial voltado para prospecção B2B em saúde, permitindo evoluir um processo inicialmente manual para uma operação estruturada e orientada por dados.

O projeto está sendo desenvolvido de forma incremental, utilizando branches e etapas de implementação para documentar sua evolução técnica.

---

## 🧩 Funcionalidades

### Implementadas

- Autenticação de usuários
- Integração com Supabase Auth
- Controle de sessão
- Rotas protegidas
- Perfil e papel do usuário
- Dashboard inicial
- Estrutura de empresas
- Estrutura de contatos
- Estrutura de oportunidades
- Estrutura de tarefas
- Interface web responsiva

### Em evolução

- Gestão completa de leads
- Pipeline comercial
- Registro de atividades
- Importação de leads
- Indicadores comerciais
- Automação de processos
- Evolução dos dashboards

---

## 🏗️ Arquitetura

A aplicação utiliza uma arquitetura web moderna baseada em:

```text
Usuário
   │
   ▼
React + TypeScript
   │
   ▼
Supabase Client
   │
   ├── Authentication
   │
   └── PostgreSQL
```

O frontend é responsável pela experiência do usuário e pela interação com os serviços disponibilizados pelo Supabase.

---

## 🛠️ Tecnologias

### Frontend

- React
- TypeScript
- Vite
- Tailwind CSS
- React Router
- Lucide React

### Backend / Dados

- Supabase
- PostgreSQL
- Supabase Authentication

### Engenharia e versionamento

- Git
- GitHub
- Feature branches
- Pull Requests
- Versionamento incremental

---

## 📂 Estrutura do projeto

```text
health-prospect-crm/
│
├── src/
│   ├── components/
│   │   ├── Layout.tsx
│   │   └── ProtectedRoute.tsx
│   │
│   ├── contexts/
│   │   └── AuthContext.tsx
│   │
│   ├── lib/
│   │   └── supabase.ts
│   │
│   ├── pages/
│   │   ├── Companies.tsx
│   │   ├── Dashboard.tsx
│   │   ├── Login.tsx
│   │   └── Placeholder.tsx
│   │
│   ├── types/
│   │   └── crm.ts
│   │
│   ├── App.tsx
│   ├── index.css
│   ├── main.tsx
│   └── vite-env.d.ts
│
├── .env.example
├── .gitignore
├── index.html
├── package.json
├── package-lock.json
├── tailwind.config.js
├── tsconfig.json
└── vite.config.ts
```

---

## 🔐 Autenticação

A autenticação é realizada através do **Supabase Auth**.

O fluxo implementado atualmente contempla:

```text
Cadastro
   ↓
Supabase Auth
   ↓
Autenticação
   ↓
Sessão
   ↓
Protected Route
   ↓
Dashboard
```

As credenciais e configurações sensíveis são mantidas em variáveis de ambiente e não são versionadas no repositório.

---

## ⚙️ Configuração local

Clone o repositório:

```bash
git clone <URL_DO_REPOSITORIO>
```

Entre no diretório:

```bash
cd health-prospect-crm
```

Instale as dependências:

```bash
npm install
```

Crie o arquivo `.env` utilizando `.env.example` como referência:

```env
VITE_SUPABASE_URL=SUA_URL_DO_SUPABASE
VITE_SUPABASE_PUBLISHABLE_KEY=SUA_PUBLISHABLE_KEY
```

Execute o ambiente de desenvolvimento:

```bash
npm run dev
```

Para validar o build de produção:

```bash
npm run build
```

---

## 🌿 Estratégia de desenvolvimento

O projeto utiliza branches para separar a evolução das funcionalidades.

Entre as etapas desenvolvidas estão:

```text
main
│
├── migration/laravel-core
├── feature/sprint-4-leads
├── feature/sprint-5-pipeline
├── feature/sprint-6-activities
├── feature/sprint-7-import
├── refactor/project-structure
└── docs/github-portfolio
```

Essa estratégia permite manter um histórico claro da evolução do projeto e facilita revisão, testes e integração das alterações.

---

## 🗺️ Roadmap

O desenvolvimento está organizado em etapas.

- [x] Estrutura inicial do projeto
- [x] Configuração do frontend
- [x] Integração com Supabase
- [x] Autenticação
- [x] Rotas protegidas
- [x] Dashboard inicial
- [x] Estrutura de empresas
- [ ] Gestão completa de leads
- [ ] Pipeline comercial
- [ ] Registro de atividades
- [ ] Importação estruturada de leads
- [ ] Indicadores comerciais
- [ ] Automação de prospecção
- [ ] Dashboards avançados
- [ ] Deploy da aplicação

---

## 📖 Evolução do projeto

Além do código, este repositório será utilizado para documentar as principais decisões e etapas da construção do CRM.

A evolução envolve temas como:

**Arquitetura → Construção → Leads e Dados → Automação → Evolução da Plataforma**

A proposta é demonstrar não apenas o resultado final, mas também o raciocínio técnico, as decisões de arquitetura, os problemas encontrados e as soluções aplicadas durante o desenvolvimento.

---

## 💡 Contexto

O projeto combina conhecimentos de:

- desenvolvimento de sistemas;
- bancos de dados;
- infraestrutura;
- integração de sistemas;
- processos comerciais;
- CRM;
- análise de dados;
- tecnologia aplicada ao setor de saúde.

O objetivo é construir uma solução funcional enquanto documenta, na prática, a evolução de um projeto de software do planejamento à implementação.

---

## 👨‍💻 Autor

**Fernando Cerqueira**

Analista de Sistemas | Infraestrutura | Dados | Healthcare IT

Projeto desenvolvido como estudo prático e portfólio profissional.

---

## 📌 Status

🚧 **Em desenvolvimento**

Novas funcionalidades e melhorias serão adicionadas progressivamente.
