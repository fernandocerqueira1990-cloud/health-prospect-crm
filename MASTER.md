# MASTER — Health Prospect CRM

## 1. Visão do produto

O Health Prospect CRM será um CRM B2B self-hosted para gestão comercial completa.

Objetivos:

- cadastrar e organizar empresas e contatos;
- captar leads de múltiplos canais;
- rastrear origem e campanhas;
- qualificar leads;
- administrar oportunidades;
- operar pipeline Kanban;
- registrar atividades e follow-ups;
- importar bases XLSX/CSV;
- criar automações;
- fornecer métricas comerciais;
- integrar dashboards Grafana;
- manter observabilidade da infraestrutura.

## 2. Público inicial

Empresas privadas do setor de saúde:

- clínicas;
- hospitais;
- laboratórios;
- centros médicos;
- diagnóstico;
- radiologia;
- fisioterapia;
- medicina ocupacional;
- operadoras;
- outros estabelecimentos privados.

A arquitetura não deve limitar o produto ao setor de saúde.

## 3. MVP

O MVP precisa possuir:

- autenticação;
- usuários;
- perfis e permissões;
- empresas;
- contatos;
- leads;
- origem/canal;
- oportunidades;
- pipeline;
- histórico de etapas;
- atividades;
- tarefas;
- importação CSV/XLSX;
- pesquisa e filtros;
- dashboard interno básico;
- API inicial;
- auditoria essencial.

## 4. Pós-MVP

- n8n;
- Matomo;
- UTMs avançadas;
- Grafana comercial;
- Prometheus;
- Loki;
- Alloy;
- Node Exporter;
- campanhas;
- lead scoring;
- automações;
- integrações oficiais WhatsApp/Meta/LinkedIn quando aplicável.

## 5. Regras de negócio essenciais

### Empresa
Uma empresa pode possuir muitos contatos, leads, oportunidades, atividades e arquivos.

### Contato
Um contato pertence normalmente a uma empresa e pode ter múltiplos canais de contato/redes sociais.

### Lead
Representa interesse/prospecção ainda em processo de qualificação.

### Oportunidade
Representa potencial comercial qualificado, com valor, estágio e probabilidade.

### Pipeline
Deve ser configurável.

Pipeline inicial:

1. Novo Lead
2. Em Pesquisa
3. Decisor Identificado
4. Contato Pendente
5. Contato Realizado
6. Respondeu
7. Qualificado
8. Reunião Agendada
9. Demonstração
10. Proposta
11. Negociação
12. Ganho
13. Perdido
14. Não Qualificado
15. Sem Resposta
16. Adiado

Cada mudança precisa gerar histórico.

## 6. Origem de leads

Origens iniciais:

- LinkedIn
- Instagram
- Facebook
- Google
- Google Ads
- WhatsApp
- Website
- Landing Page
- Telefone
- E-mail
- Evento
- Indicação
- Parceiro
- Prospecção ativa
- Lista comercial
- Importação Excel
- Importação CSV
- API
- Outro

## 7. Métricas

- leads/dia;
- leads/semana;
- leads/mês;
- leads por origem;
- leads por canal;
- leads por campanha;
- leads por cidade/bairro;
- conversão;
- tempo de primeira resposta;
- tempo por estágio;
- reuniões;
- propostas;
- ganhos;
- perdas;
- motivos de perda;
- ticket médio;
- valor do pipeline;
- receita ganha;
- performance por vendedor.

## 8. Prioridades

1. Integridade dos dados.
2. Segurança.
3. Auditabilidade.
4. Usabilidade.
5. Performance.
6. Automação.
7. Analytics.
