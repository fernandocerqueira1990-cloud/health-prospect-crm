# ARCHITECTURE — Health Prospect CRM

## Arquitetura alvo

```text
Internet
   |
HTTPS
   |
Apache 2
   |
   +----------------------+--------------------+
   |                      |                    |
CRM / Laravel        Grafana              n8n
   |                      |                    |
PHP-FPM                  |                 API/Webhook
   |                      |                    |
   +---------- PostgreSQL +--------------------+
   |
Redis
```

## Aplicação

- Apache 2 como web server/reverse proxy;
- PHP-FPM;
- Laravel;
- Blade/Tailwind/Alpine;
- Livewire para interações ricas;
- PostgreSQL;
- Redis;
- Queue workers;
- Scheduler.

## Serviços auxiliares

Preferencialmente containers para:

- Grafana;
- Prometheus;
- Loki;
- Alloy;
- n8n;
- Matomo.

## Rede

Expostos publicamente:

- 80/TCP: somente redirect para HTTPS;
- 443/TCP: aplicação e proxies autorizados.

Não expor publicamente:

- 5432 PostgreSQL;
- 6379 Redis;
- 9090 Prometheus;
- portas internas Loki;
- Node Exporter;
- serviços administrativos.

## Subdomínios futuros

- `crm.dominio.com`
- `grafana.dominio.com`
- `automation.dominio.com`
- `analytics.dominio.com`

## Observabilidade

```text
Debian -> Node Exporter -> Prometheus -> Grafana
Apache/Laravel -> Alloy -> Loki -> Grafana
PostgreSQL exporters -> Prometheus -> Grafana
```

## Analytics comercial

```text
CRM
 |
PostgreSQL
 |
schema analytics
 |
views materializadas/SQL views
 |
grafana_reader
 |
Grafana
```

## Integrações

```text
Fonte externa
  |
Webhook/API
  |
n8n (opcional)
  |
Laravel API
  |
Services/Actions
  |
PostgreSQL
```

n8n nunca é fonte de verdade.

## Deploy inicial

Um servidor Debian pode executar todo o MVP.

Referência inicial:

- 4 vCPU;
- 8 GB RAM;
- 80–120 GB SSD;
- swap 2–4 GB.

Dimensionamento precisa ser revisto com carga real.

## Crescimento

Futuro:

- App server separado;
- DB server separado;
- Observabilidade separada;
- Redis dedicado;
- object storage;
- load balancer;
- replicas;
- backups offsite.

## Importação de dados

Fundação implementada na TASK-080:

```text
Upload autenticado
  |
Disco Laravel privado (nome interno UUID)
  |
  Action -> CSV Reader / SplFileObject ou XLSX Reader / PhpSpreadsheet
  |
PostgreSQL: imports -> import_rows JSONB
```

- parsing CSV síncrono e em streaming, com escrita em lotes configuráveis;
- parsing XLSX síncrono com PhpSpreadsheet em modo somente dados, escrita em lotes e processamento determinístico apenas da worksheet de índice 0, mesmo quando oculta;
- antes do carregamento do workbook, o contêiner ZIP e as dimensões informadas pelo PhpSpreadsheet são inspecionados; somente a primeira worksheet é carregada e um read filter limita defensivamente linhas e colunas;
- limites configuráveis de entradas, bytes descompactados, razão de compressão, linhas e colunas reduzem o risco de consumo excessivo de recursos, mas XLSX continua possuindo maior custo de memória que CSV e a mitigação não constitui proteção absoluta contra todo DoS ou ZIP bomb;
- valores de staging preservam tipos escalares do XLSX sem normalização comercial; formatos visuais do Excel não são interpretados nesta etapa, portanto datas e números formatados permanecem com o valor subjacente fornecido pelo reader;
- fórmulas são preservadas como texto e nunca são calculadas durante a importação;
- o reader valida registros CSV de forma conservadora, rejeita estrutura malformada e preserva em `row_number` a linha física inicial de cada registro, inclusive quando campos válidos ocupam múltiplas linhas;
- estados mínimos `uploaded`, `processing`, `parsed` e `failed` permitem mover o processamento para queue futuramente sem alterar o contrato persistido;
- arquivos não são servidos diretamente e o conteúdo integral não é enviado à auditoria;
- os parsers atuais exigem um disco Laravel com driver `local`; discos remotos não são suportados nesta versão;
- exclusões removem primeiro o arquivo privado e depois os registros em transação PostgreSQL; arquivo já ausente é tratado de forma idempotente e falha de filesystem preserva o banco para nova tentativa, sem alegar atomicidade distribuída;
- o Column Mapper autorizado por `imports.update` aplica uma whitelist de targets, persiste o contrato em `imports.metadata.mapping` e reconstrói `import_rows.normalized_data` em chunks a cada mapping ou remapping;
- `original_data` permanece imutável durante o mapping, que não cria entidades comerciais; vínculos com entidades continuam reservados para Preview, deduplicação, merge e relatório.
- o Preview autorizado por `imports.view` é estritamente read-only: valida somente targets mapeados, calcula `valid`, `warning` ou `error` sem alterar `import_rows.status`, exibe original e normalizado com escaping e não registra auditoria de leitura;
- filtros, contadores e paginação do Preview são derivados em uma única varredura ordenada de `import_rows` com `lazyById` e lotes de 500; a memória fica limitada à página solicitada, enquanto o custo por requisição é linear no total de linhas no MVP, sem persistência transitória, Redis ou queue;
- Company exige `legal_name`, Contact exige `name` e Lead exige ao menos um identificador entre nome, empresa, e-mail ou telefone; vínculos, deduplicação, merge e criação definitiva de entidades continuam fora do Preview.
- a Deduplicação persiste um resultado versionado e independente em `import_rows.dedup_data`, detectando candidatos do CRM e da própria importação para Company, Contact e Lead sem preencher vínculos ou modificar entidades comerciais;
- o matching é determinístico e conservador: identidade fiscal composta é forte para Company, sinais contextuais são possíveis, e Contacts/Leads respeitam suas constraints reais; soft-deleted participam da detecção e dados de candidatos são ocultados quando a Policy correspondente não permite visualização;
- a análise percorre `import_rows` em chunks de 500, consulta cada tipo de entidade em lote e usa mapas de chaves para duplicidades internas em O(N), limitando a cinco candidatos ordenados por grupo; resultados anteriores são substituídos em reanálises;
- decisões `create_new`, `use_existing`, `reuse_import_row` e `skip` registram apenas intenção para a TASK-085, com IDs revalidados no backend; identidade fiscal forte impede `create_new`, enquanto não há merge, create/update/delete no CRM ou preenchimento de `related_entity_*`;
- `imports.duplicate_rows` conta linhas com ao menos um match forte/exato, enquanto `imports.metadata.dedup.summary` mantém os contadores transitórios detalhados; remapping invalida ambos os resultados;
- `analyzed_at` explicita staleness: a TASK-085 deverá revalidar constraints fortes imediatamente antes da persistência definitiva, pois a análise não elimina race conditions futuras.
- a execução final exige dedup versionado e decisões resolvidas, adquire lock pessimista no import, marca `processing` e processa linhas por `row_number` em chunks de 100; candidatos existentes são carregados em até uma consulta por tipo e chunk, enquanto cada linha usa uma transação independente para manter Company, Contact e Lead atomicamente consistentes sem reverter linhas anteriores já concluídas;
- criação usa somente campos externos permitidos no catálogo e payloads explicitamente montados. `use_existing` nunca atualiza a entidade e `reuse_import_row` só aceita resultado já materializado por linha anterior da mesma importação; soft-deleted não são restaurados ou vinculados;
- resultados por grupo ficam em `import_rows.execution_data` versionado, separados de dados originais, normalizados e decisões. Import concluído é idempotente, e falhas parciais não possuem retry automático no MVP;
- novos Leads usam `LeadSource` ativo resolvido exclusivamente pelo slug `importacao` e um `Channel` ativo escolhido na confirmação quando necessário. `imports.metadata.execution_config` guarda o slug semântico e a FK validada; ambos são revalidados antes da criação via `CreateLeadAction`;
- uma evolução futura poderá aceitar canal/origem semanticamente mapeado por Lead com fallback para a configuração do import, sem expor IDs internos como targets;
- a identidade fiscal de Company é revalidada imediatamente antes da escrita e a constraint PostgreSQL permanece como última barreira. Isso reduz, mas não elimina, races externas; conflitos são registrados de forma sanitizada por linha;
- o relatório autorizado por `imports.view` é read-only, paginado em 50 linhas e filtrado no PostgreSQL. A execução síncrona é um trade-off do MVP; o Action e o processamento em chunks permitem migração futura para Queue;
- uma interrupção abrupta do processo pode deixar o import em `processing` e requer recuperação operacional; o MVP não implementa watchdog nem retry parcial automático;
- não existe merge automático, atualização de entidade reutilizada, criação de Opportunity ou preenchimento de `related_entity_*` nesta etapa.
