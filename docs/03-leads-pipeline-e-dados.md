# 03 — Leads, Pipeline e Dados

## 🎯 Objetivo da etapa

Com a arquitetura e a base da aplicação estruturadas, o Health Prospect CRM começou a avançar para o seu objetivo principal: transformar dados de prospecção em um processo comercial organizado.

Nesta etapa, o foco passa para leads, empresas, contatos e pipeline comercial.

---

## 📊 Da planilha para o CRM

O projeto começou com bases de potenciais clientes organizadas em planilhas.

Essas bases contêm informações comerciais de empresas privadas do setor de saúde, como:

- clínicas;
- hospitais;
- laboratórios;
- centros médicos;
- centros de diagnóstico;
- empresas de serviços de saúde.

A evolução natural foi transformar essas informações em dados estruturados dentro do CRM.

---

## 🏢 Empresas

As empresas representam uma das entidades centrais da aplicação.

A estrutura permite organizar informações como:

- nome da empresa;
- categoria;
- especialidade;
- localização;
- telefone;
- serviços;
- prioridade comercial;
- observações.

Isso cria uma visão centralizada de cada potencial cliente.

---

## 👥 Contatos

Uma empresa pode possuir diferentes profissionais envolvidos no processo de decisão.

Por isso, contatos são tratados separadamente e relacionados às respectivas empresas.

Entre os perfis relevantes para prospecção estão:

- gestores de TI;
- coordenadores de sistemas;
- diretores;
- responsáveis administrativos;
- gestores financeiros;
- responsáveis por operações.

Essa separação permite trabalhar estratégias de abordagem diferentes dentro da mesma organização.

---

## 🎯 Leads

O lead representa a oportunidade inicial de prospecção.

Além dos dados cadastrais, o CRM pode utilizar informações como:

- origem;
- prioridade;
- responsável;
- status;
- histórico de interação;
- próxima ação.

Dessa forma, uma lista estática de empresas passa a fazer parte de um processo comercial rastreável.

---

## 🔄 Pipeline comercial

O pipeline organiza visualmente a evolução de cada oportunidade.

Um fluxo comercial pode seguir etapas como:

Prospecção  
↓  
Contato inicial  
↓  
Qualificação  
↓  
Apresentação  
↓  
Negociação  
↓  
Fechamento

Cada mudança de etapa representa uma evolução real no processo comercial.

---

## 🗄️ Estrutura de dados

O PostgreSQL fornece a base relacional necessária para conectar diferentes entidades do CRM.

Exemplo conceitual:

Empresa  
↓  
Contatos  
↓  
Lead / Oportunidade  
↓  
Atividades  
↓  
Tarefas

Essa estrutura evita informações isoladas e permite construir uma visão histórica de cada relacionamento comercial.

---

## 📥 Importação de dados

Como parte da evolução do projeto, foi criada uma etapa específica para trabalhar a importação das bases existentes.

O objetivo é reduzir trabalho manual e permitir que informações inicialmente armazenadas em planilhas sejam incorporadas ao CRM de maneira estruturada.

Essa evolução também prepara o sistema para trabalhar com volumes maiores de leads.

---

## 📈 Visão orientada por dados

Com os dados centralizados, o CRM passa a criar possibilidades para indicadores como:

- quantidade de leads ativos;
- leads por prioridade;
- oportunidades abertas;
- evolução do pipeline;
- atividades realizadas;
- tarefas pendentes;
- taxa de conversão.

Assim, o projeto deixa de ser apenas um cadastro de empresas e começa a se transformar em uma ferramenta de gestão comercial.

---

## 📌 Resultado desta etapa

A estrutura de empresas, contatos, leads e oportunidades estabelece o núcleo comercial do Health Prospect CRM.

A partir dessa base, torna-se possível avançar para automações e integrações capazes de reduzir atividades manuais e melhorar o acompanhamento da prospecção.

⬅️ [02 — Da Arquitetura à Construção](./02-da-arquitetura-a-construcao.md)

➡️ Próxima etapa: [04 — Automações e Integrações](./04-automacoes-e-integracoes.md)
