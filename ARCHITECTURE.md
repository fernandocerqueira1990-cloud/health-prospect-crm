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
