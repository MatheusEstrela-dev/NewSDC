# Monitoring Stack Summary

Stack de monitoramento atual para NewSDC/Swoole:

- Prometheus para coleta de metricas
- Grafana para dashboards
- Alertmanager para alertas
- Blackbox Exporter para probes HTTP no app Swoole
- PostgreSQL Exporter
- Redis Exporter
- Node Exporter
- cAdvisor

Endpoints monitorados:

- App: `http://newsdc_app:8000`
- Health: `http://newsdc_app:8000/health`

Dashboards e alertas usam metricas de probe e recursos do host/container.
