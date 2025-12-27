# Stack de Monitoramento de Logs - Laravel

Este diretório contém a configuração completa para monitoramento de logs do sistema Laravel usando **Grafana + Loki + Promtail**.

## 📁 Estrutura

```
monitoring/
├── promtail/
│   ├── promtail-config.yml    # Configuração do Promtail (coleta de logs)
│   └── data/                   # Dados temporários do Promtail
├── loki/
│   └── data/                   # Banco de dados de logs
├── grafana/
│   ├── data/                   # Dados do Grafana
│   ├── dashboards/             # Dashboards pré-configurados
│   │   └── laravel-logs.json  # Dashboard principal
│   └── provisioning/           # Configuração automática
│       ├── datasources/        # Conexão com Loki
│       └── dashboards/         # Carregamento de dashboards
├── docker-compose.logging.yml  # Orquestração dos containers
├── start-logging-stack.sh      # Script para iniciar tudo
└── LOGGING_STACK_README.md     # Este arquivo
```

## 🚀 Quick Start

### 1. Iniciar a Stack

```bash
cd docker/monitoring
./start-logging-stack.sh
```

Este script irá:
- Verificar pré-requisitos (Docker, Docker Compose)
- Criar diretórios necessários
- Configurar permissões
- Criar docker-compose.yml (se não existir)
- Iniciar Loki, Promtail e Grafana
- Configurar datasource do Grafana automaticamente

### 2. Acessar Grafana

Abra seu navegador em: **http://localhost:3001**

**Credenciais padrão:**
- Usuário: `admin`
- Senha: `admin`

(O Grafana pedirá para alterar a senha no primeiro acesso)

### 3. Ver Dashboard

Após login, vá em:
- **Dashboards** → **Laravel** → **Laravel - Logs & Observability**

## 📊 Dashboard Incluído

O dashboard `laravel-logs.json` fornece:

### Métricas em Tempo Real
- **HTTP Request Rate**: Requisições por segundo
- **Average Response Time**: Tempo médio de resposta (últimos 5 minutos)
- **Error Rate by Status Code**: Taxa de erros 4xx e 5xx
- **Success Rate**: Porcentagem de requisições bem-sucedidas

### Tabelas e Listas
- **Top Endpoints**: Endpoints mais acessados
- **Slow Queries**: Queries SQL lentas (> 1 segundo)
- **Failed Jobs**: Jobs que falharam com detalhes da exceção
- **Critical Errors**: Erros críticos com stack trace

### Estatísticas (Cards)
- Critical Errors (últimas 24h)
- Slow Queries (última hora)
- Failed Jobs (última hora)
- Success Rate (últimos 5 minutos)

### Gráficos Temporais
- Jobs Processed per Second (por nome do job)
- Taxa de requisições HTTP
- Taxa de erros por tipo

## 🔍 Queries Úteis no Grafana

### Explorar Logs

No Grafana, vá em **Explore** e use estas queries:

#### Ver todos os logs
```logql
{app="laravel"}
```

#### Filtrar por severidade
```logql
{app="laravel"} | json | severity="error"
{app="laravel"} | json | severity="critical"
```

#### Procurar por texto
```logql
{app="laravel"} |= "Slow Query"
{app="laravel"} |= "exception"
```

#### Filtrar por request_id (rastreamento completo)
```logql
{app="laravel"} | json | request_id="9d7f8e2a-3c1b-4567-8901-23456789abcd"
```

#### Ver queries lentas
```logql
{app="laravel"} | json | event_name="Slow Query Detected"
```

#### Ver jobs que falharam
```logql
{app="laravel"} | json | job_name!="" | status="failed"
```

#### Ver erros de um endpoint específico
```logql
{app="laravel"} | json | path="/api/demandas" | status_code >= 500
```

#### Ver logs de um usuário específico
```logql
{app="laravel"} | json | user_id="123"
```

## 📈 Métricas Avançadas

### Taxa de Erros
```logql
sum(rate({app="laravel"} | json | severity="error" [5m]))
```

### P95 de Tempo de Resposta
```logql
quantile_over_time(0.95, {app="laravel"} | json | unwrap duration_ms [5m])
```

### Top 10 Endpoints Mais Lentos
```logql
topk(10,
  sum by (path) (rate({app="laravel"} | json | unwrap duration_ms [5m]))
)
```

### Contagem de Jobs Processados
```logql
sum by (job_name) (
  count_over_time({app="laravel"} | json | job_name!="" [1h])
)
```

## ⚙️ Configuração do Promtail

O arquivo `promtail/promtail-config.yml` define:

### 5 Jobs de Coleta
1. **laravel-app**: Logs gerais da aplicação
2. **laravel-critical**: Apenas erros críticos
3. **laravel-queries**: Queries lentas do banco
4. **laravel-jobs**: Jobs e queues
5. **laravel-http**: Requisições HTTP

### Labels Automáticos
- `app`: laravel
- `environment`: production/development
- `job`: tipo do job
- `severity`: nível de log
- `request_id`: UUID da requisição

### Métricas Geradas
- `query_duration_ms`: Histograma de duração de queries
- `job_duration_ms`: Histograma de duração de jobs
- `http_request_duration_ms`: Histograma de duração HTTP
- `http_requests_total`: Contador de requisições

## 🛠️ Comandos Úteis

### Ver logs dos containers
```bash
cd docker/monitoring
docker-compose -f docker-compose.logging.yml logs -f
```

### Ver logs apenas do Promtail
```bash
docker-compose -f docker-compose.logging.yml logs -f promtail
```

### Reiniciar a stack
```bash
docker-compose -f docker-compose.logging.yml restart
```

### Parar a stack
```bash
docker-compose -f docker-compose.logging.yml down
```

### Parar e remover volumes (limpar dados)
```bash
docker-compose -f docker-compose.logging.yml down -v
```

### Ver status dos containers
```bash
docker-compose -f docker-compose.logging.yml ps
```

## 🔧 Troubleshooting

### Promtail não está coletando logs

1. Verifique se o Promtail tem acesso aos logs do Docker:
```bash
docker-compose -f docker-compose.logging.yml logs promtail
```

2. Verifique permissões:
```bash
sudo chmod -R 755 /var/lib/docker/containers
```

3. Verifique se o path está correto no `promtail-config.yml`:
```yaml
__path__: /var/lib/docker/containers/*/*.log
```

### Grafana não consegue se conectar ao Loki

1. Verifique se o Loki está rodando:
```bash
curl http://localhost:3100/ready
```

2. Verifique a configuração do datasource em:
   `grafana/provisioning/datasources/loki.yml`

3. No Grafana, vá em **Configuration** → **Data Sources** → **Loki** e teste a conexão

### Dashboard não aparece

1. Verifique se o arquivo existe:
```bash
ls -la grafana/dashboards/laravel-logs.json
```

2. Verifique os logs do Grafana:
```bash
docker-compose -f docker-compose.logging.yml logs grafana | grep -i dashboard
```

3. Importe manualmente:
   - Grafana → **+** → **Import**
   - Copie o conteúdo de `grafana/dashboards/laravel-logs.json`
   - Cole e clique em **Load**

### Logs não aparecem em tempo real

1. Verifique se a aplicação Laravel está enviando logs para stderr:
```bash
# No container da aplicação
tail -f /proc/1/fd/2
```

2. Verifique se o LOG_CHANNEL está configurado corretamente no `.env`:
```env
LOG_CHANNEL=stack
```

3. Force um log de teste:
```bash
docker exec -it <container_laravel> php artisan tinker
>>> \Log::info('Test log from tinker');
```

## 📊 Alertas (Próximo Passo)

Para configurar alertas no Grafana:

1. Vá em **Alerting** → **Alert rules**
2. Crie regras como:
   - **Alta taxa de erros**: > 5% em 5 minutos
   - **Muitas queries lentas**: > 10 em 1 minuto
   - **Jobs falhando**: > 3 em 5 minutos

3. Configure canais de notificação:
   - Slack
   - Discord
   - Email
   - PagerDuty

## 🔗 Recursos Relacionados

- [Documentação do Sistema de Logs](../../LOGGING_SYSTEM.md)
- [Resumo das Melhorias](../../LOGGING_IMPROVEMENTS_SUMMARY.md)
- [Grafana Loki Docs](https://grafana.com/docs/loki/latest/)
- [Promtail Docs](https://grafana.com/docs/loki/latest/clients/promtail/)
- [LogQL (Loki Query Language)](https://grafana.com/docs/loki/latest/logql/)

## 🎯 Próximos Passos

1. [ ] Personalizar o dashboard conforme suas necessidades
2. [ ] Configurar alertas para erros críticos
3. [ ] Integrar com Slack/Discord para notificações
4. [ ] Criar dashboards específicos por módulo (Demandas, RAT, PAE)
5. [ ] Configurar retenção de logs (atualmente ilimitado)
6. [ ] Adicionar autenticação OAuth no Grafana

---

**Stack criada e configurada como parte do sistema de logs de ponta para Laravel 24/7**
