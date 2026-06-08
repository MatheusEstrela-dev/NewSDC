# Monitoramento

Monitoramento da stack Swoole sem proxy web dedicado.

Componentes:

- Prometheus
- Grafana
- Alertmanager
- Node Exporter
- cAdvisor
- PostgreSQL Exporter
- Redis Exporter
- Blackbox Exporter

Os probes HTTP apontam diretamente para `newsdc_app:8000` e para `/health`.

Metricas principais:

- Latencia: `probe_duration_seconds`
- Disponibilidade: `probe_success`
- Status HTTP: `probe_http_status_code`
- CPU: `node_cpu_seconds_total`
- Memoria: `node_memory_MemAvailable_bytes`
- Redis: `redis_up`, `redis_keyspace_hits_total`, `redis_keyspace_misses_total`
- PostgreSQL: `pg_up`
