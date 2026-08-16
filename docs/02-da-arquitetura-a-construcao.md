# 02 — Da Arquitetura à Construção

## 🎯 Objetivo da etapa

Depois de definir a arquitetura inicial do Health Prospect CRM, o próximo desafio foi transformar o planejamento em uma aplicação funcional.

Nesta etapa, a estrutura técnica começou a ganhar forma: frontend, autenticação, banco de dados, organização do código e integração entre os componentes.

---

## 🧱 Estrutura da aplicação

O frontend foi estruturado utilizando:

- React
- TypeScript
- Vite
- React Router
- Tailwind CSS

O código foi organizado dentro de `src/`, separando responsabilidades entre:

- `components/`
- `contexts/`
- `lib/`
- `pages/`
- `types/`

Essa organização facilita manutenção, evolução e escalabilidade do projeto.

---

## 🔐 Autenticação

A autenticação foi integrada ao Supabase Auth.

Foram implementados:

- criação de conta;
- login;
- logout;
- controle de sessão;
- rotas protegidas;
- identificação do usuário autenticado.

Com isso, o CRM passou a possuir uma camada real de controle de acesso.

---

## 🗄️ Integração com dados

A aplicação utiliza Supabase como camada de serviços conectada ao PostgreSQL.

A comunicação segue, de forma simplificada, o fluxo:

Usuário  
↓  
React  
↓  
Supabase Client  
↓  
Supabase API  
↓  
PostgreSQL

As credenciais públicas necessárias ao frontend são configuradas através de variáveis de ambiente, evitando armazenar configurações diretamente no código-fonte.

---

## 🖥️ Interface inicial

A primeira versão funcional passou a contar com:

- tela de criação de conta;
- tela de login;
- dashboard;
- navegação lateral;
- área de empresas;
- estrutura para contatos;
- estrutura para oportunidades;
- estrutura para tarefas.

O objetivo nesta fase não era finalizar todas as funcionalidades, mas estabelecer uma base consistente para a evolução do CRM.

---

## 🌿 Estratégia com Git

O desenvolvimento foi dividido em branches para representar diferentes etapas da construção.

Essa estratégia permite:

- desenvolver funcionalidades isoladamente;
- manter histórico das alterações;
- testar antes da integração;
- documentar a evolução do sistema;
- integrar mudanças posteriormente à branch principal.

O próprio histórico do Git passa, assim, a fazer parte da documentação técnica do projeto.

---

## 🧪 Validação

Durante a reorganização da estrutura do frontend, o processo de build foi utilizado para validar as alterações:

`npm run build`

A validação garante que mudanças estruturais não interrompam a compilação da aplicação antes de serem integradas.

---

## 📌 Resultado desta etapa

Ao final desta fase, o Health Prospect CRM deixou de existir apenas como arquitetura planejada e passou a possuir uma aplicação web funcional conectada aos serviços de backend.

A base estava preparada para iniciar a implementação das funcionalidades diretamente relacionadas ao processo comercial.

⬅️ [01 — Visão e Arquitetura](./01-visao-e-arquitetura.md)

➡️ Próxima etapa: [03 — Leads, Pipeline e Dados](./03-leads-pipeline-e-dados.md)
