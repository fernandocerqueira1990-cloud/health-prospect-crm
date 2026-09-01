# 01 — Visão e Arquitetura

## 🎯 Contexto

O CRM X nasceu de uma necessidade prática: organizar e transformar um processo de prospecção comercial inicialmente baseado em planilhas em uma aplicação estruturada.

O projeto é direcionado à prospecção B2B no setor privado de saúde, incluindo clínicas, hospitais, laboratórios, centros médicos e outras organizações do segmento.

---

## 💡 Problema

O processo inicial de prospecção envolvia diferentes fontes de informação e atividades manuais:

- levantamento de potenciais clientes;
- organização das empresas em planilhas;
- identificação de contatos estratégicos;
- classificação de leads;
- acompanhamento das abordagens;
- controle das oportunidades comerciais.

À medida que a quantidade de informações aumenta, planilhas deixam de oferecer uma visão adequada de todo o processo comercial.

A proposta foi transformar esse fluxo em uma aplicação própria.

---

## 🏗️ Arquitetura inicial

A solução foi planejada separando as principais responsabilidades da aplicação:

### Frontend

Responsável pela interface e interação com o usuário.

Tecnologias:

- React
- TypeScript
- Vite

### Backend e serviços

O Supabase foi utilizado como plataforma de serviços para:

- autenticação;
- APIs;
- integração com a aplicação;
- gerenciamento dos dados.

### Banco de dados

PostgreSQL utilizado para estruturar os dados relacionados a:

- usuários;
- empresas;
- contatos;
- leads;
- oportunidades;
- atividades;
- tarefas.

### Controle de versão

Git e GitHub são utilizados para versionamento, organização das etapas de desenvolvimento e documentação da evolução do projeto.

---

## 🔄 Fluxo conceitual

O fluxo principal da solução pode ser representado como:

Usuário  
↓  
Interface Web  
↓  
Aplicação React  
↓  
Supabase / APIs  
↓  
PostgreSQL  
↓  
Dados comerciais

---

## 🚀 Estratégia de desenvolvimento

O projeto está sendo desenvolvido de maneira incremental.

Cada etapa adiciona novas capacidades ao CRM enquanto mantém o histórico técnico através do Git.

Entre as etapas planejadas estão:

1. arquitetura inicial;
2. estrutura da aplicação;
3. autenticação;
4. empresas e contatos;
5. leads;
6. pipeline comercial;
7. atividades;
8. importação de dados;
9. automações;
10. indicadores e evolução da plataforma.

---

## 📌 Resultado desta etapa

A primeira etapa estabeleceu a base técnica e conceitual necessária para transformar uma necessidade comercial real em um projeto de software estruturado.

A partir dessa arquitetura, o próximo passo foi iniciar a construção efetiva da aplicação.

➡️ Próxima etapa: [02 — Da Arquitetura à Construção](./02-da-arquitetura-a-construcao.md)
