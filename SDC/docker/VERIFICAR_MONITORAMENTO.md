# Verificar Monitoramento

Com a stack ativa:

```bash
docker compose -f SDC/docker/docker-compose.yml --profile monitoring ps
```

Consultas uteis no Prometheus:

```promql
probe_success{probe_type=~"http|health_check"}
probe_duration_seconds{probe_type=~"http|health_check"}
probe_http_status_code{probe_type=~"http|health_check"}
pg_up
redis_up
```

URLs:

- Prometheus: http://localhost:19090
- Grafana: http://localhost:13000
- Alertmanager: http://localhost:19093
