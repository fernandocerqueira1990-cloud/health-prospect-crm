# Health Prospect CRM

CRM B2B para prospecção comercial de empresas privadas do setor de saúde.

## Stack
- React + TypeScript + Vite
- Tailwind CSS
- Supabase Auth + PostgreSQL + RLS + Storage

## Configuração
1. Copie `.env.example` para `.env`.
2. Preencha `VITE_SUPABASE_PUBLISHABLE_KEY` com a chave publicável do projeto Supabase.
3. Execute:

```bash
npm install
npm run dev
```

## Supabase
Projeto esperado: `safvlogjfmdnjvlmprfw`.

A primeira versão inclui:
- login com Supabase Auth;
- sessão persistente;
- layout responsivo;
- dashboard com métricas reais;
- listagem/pesquisa de empresas;
- cadastro de lead diretamente no Supabase;
- respeito ao RLS já configurado no banco.

Próximos módulos: contatos, oportunidades, Kanban, tarefas, importação XLSX/CSV e página detalhada da empresa.
