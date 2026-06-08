# Docker - NewSDC

Stack atual:

- App Laravel Octane com Swoole
- PostgreSQL/PostGIS
- Redis
- Mailhog
- Queue worker
- Monitoramento opcional com Prometheus, Grafana, Alertmanager, cAdvisor, Redis exporter, PostgreSQL exporter e Blackbox exporter

## Desenvolvimento local

Da raiz do repositorio:

```bash
just dev-build
just dev-up
just dev-status
```

URLs principais:

- App: http://localhost:19444
- Mailhog: http://localhost:8025
- PostgreSQL no host: localhost:5433
- Redis no host: localhost:6380

O Vite roda no host:

```bash
just dev-vite
```

## Compose principal

```bash
docker compose -f SDC/docker/docker-compose.yml up -d
```

URL:

- App: http://localhost:18001

## Monitoramento

O monitoramento usa probes HTTP diretamente contra o app Swoole.

```bash
docker compose -f SDC/docker/docker-compose.yml --profile monitoring up -d
```

URLs:

- Prometheus: http://localhost:19090
- Grafana: http://localhost:13000
- Alertmanager: http://localhost:19093
