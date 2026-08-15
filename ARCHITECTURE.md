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
Action -> CSV Reader nativo / SplFileObject
  |
PostgreSQL: imports -> import_rows JSONB
```

- parsing CSV síncrono e em streaming, com escrita em lotes configuráveis;
- o reader valida registros CSV de forma conservadora, rejeita estrutura malformada e preserva em `row_number` a linha física inicial de cada registro, inclusive quando campos válidos ocupam múltiplas linhas;
- estados mínimos `uploaded`, `processing`, `parsed` e `failed` permitem mover o processamento para queue futuramente sem alterar o contrato persistido;
- arquivos não são servidos diretamente e o conteúdo integral não é enviado à auditoria;
- o parser da TASK-080 exige um disco Laravel com driver `local`, pois `SplFileObject` opera sobre caminho local; discos remotos não são suportados nesta versão;
- exclusões removem primeiro o arquivo privado e depois os registros em transação PostgreSQL; arquivo já ausente é tratado de forma idempotente e falha de filesystem preserva o banco para nova tentativa, sem alegar atomicidade distribuída;
- `normalized_data` e vínculos com entidades permanecem reservados para as tarefas posteriores de mapping, preview, deduplicação e relatório.
