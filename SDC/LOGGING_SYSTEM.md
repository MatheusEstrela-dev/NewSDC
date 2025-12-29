# Sistema de Logs de Ponta - Laravel

Sistema de logging estruturado e completo para aplicações Laravel em produção 24/7.

## 🎯 Objetivos

Este sistema foi arquitetado seguindo as melhores práticas de **Structured Logging** para responder às 5 perguntas do "Log Perfeito":

1. **Timestamp**: Quando aconteceu?
2. **Level**: Qual a gravidade?
3. **Context**: Quem foi o usuário e qual o Request ID?
4. **Message**: O que aconteceu?
5. **Trace**: Onde no código?

## 🏗️ Arquitetura

### Canais de Log Configurados

```
SDC/storage/logs/
├── laravel.log        # Log geral (daily, 14 dias)
├── events.log         # Eventos do sistema (30 dias)
├── critical.log       # Erros críticos (90 dias, JSON)
├── queries.log        # Queries lentas (7 dias, JSON)
└── jobs.log           # Jobs/Queues (30 dias, JSON)
```

### Formato dos Logs

- **Desenvolvimento**: Texto simples para leitura rápida
- **Produção**: JSON estruturado para ferramentas (Grafana Loki, Datadog, ELK)

## 🔑 Recursos Principais

### 1. Rastreamento por Request ID (UUID)

Cada requisição HTTP recebe um UUID único que permite rastrear todo o fluxo:

```json
{
  "request_id": "9d7f8e2a-3c1b-4567-8901-23456789abcd",
  "user_id": 123,
  "url": "/api/demandas",
  "method": "POST"
}
```

O `request_id` é:
- Gerado automaticamente no `AppServiceProvider`
- Adicionado ao contexto de TODOS os logs
- Retornado no header `X-Request-ID` da resposta
- Compartilhado entre todos os logs da mesma requisição

### 2. Monitoramento de Queries Lentas

Todas as queries SQL são monitoradas automaticamente:

```php
// Threshold configurável por ambiente
Production: > 1000ms (1 segundo)
Development: > 2000ms (2 segundos)

// Se > 2x threshold → Log CRÍTICO
```

**Logs gerados**:
- Canal `queries`: Todas as queries lentas
- Canal `critical`: Queries muito lentas (> 2x threshold)

### 3. Logging Completo de Requisições HTTP

O middleware `LogHttpRequests` registra:

**Entrada**:
- Headers, URL, method, IP, user-agent
- Query params
- Payload (body) com mascaramento de dados sensíveis
- Tamanho do payload

**Saída**:
- Status code, duração (ms)
- Tamanho da resposta
- Preview da resposta (estruturado em JSON)

**Proteção de Dados Sensíveis**:
```php
// Rotas mascaradas
login, register, password, two-factor

// Campos mascarados
password, token, secret, api_key, credit_card, cvv
```

### 4. Monitoramento de Jobs e Queues

O `QueueServiceProvider` monitora todo o ciclo de vida dos jobs:

**Eventos logados**:
- `Queue::before` → Job iniciou
- `Queue::after` → Job concluído com sucesso
- `Queue::failing` → Job falhou

**Informações capturadas**:
```json
{
  "job_id": "uuid",
  "job_name": "App\\Jobs\\ProcessReport",
  "queue": "default",
  "attempts": 1,
  "duration_ms": 1234.56,
  "status": "success|failed",
  "exception": {...}  // se falhou
}
```

**Alertas**:
- Jobs lentos (> 5 segundos) → WARNING
- Jobs que falharam após max tentativas → CRITICAL

### 5. Logs de Erros Críticos

O `ActivityLogger::logCriticalError()` captura:

```json
{
  "exception_class": "RuntimeException",
  "exception_message": "Error details",
  "error_file": "Controller.php",
  "error_line": 42,
  "stack_trace": [...],  // Top 10 chamadas estruturadas
  "previous_exception": {...},  // Se existir
  "system_metrics": {
    "memory_usage_mb": 45.2,
    "memory_peak_mb": 48.7,
    "php_version": "8.2.0"
  }
}
```

## 📊 Integração com Ferramentas

### Grafana + Loki (Recomendado)

**Fluxo de dados**:
```
Laravel (JSON logs)
  → stderr
  → Docker Container
  → Promtail
  → Loki
  → Grafana
```

**Queries exemplo no Grafana**:
```logql
# Todos os erros
{app="laravel"} |= "error"

# Logs de um request específico
{app="laravel"} | json | request_id="9d7f8e2a-3c1b-4567-8901-23456789abcd"

# Queries lentas
{app="laravel"} | json | event_name="Slow Query Detected"

# Jobs que falharam
{app="laravel"} | json | job_name="ProcessReport" | status="failed"
```

### Sentry (Error Tracking)

Para instalar e configurar o Sentry:

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=your-dsn-here
```

**Configuração no `.env`**:
```env
SENTRY_LARAVEL_DSN=https://your-key@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=0.2  # 20% das transações
```

**Benefícios do Sentry**:
- Agrupa erros idênticos (1000 erros iguais = 1 Issue)
- Mostra linha exata do código + variáveis no momento do crash
- Alertas em tempo real
- Integração com Slack/Discord

## 🛠️ Como Usar

### 1. ActivityLogger (Eventos Customizados)

```php
use App\Services\Logging\ActivityLogger;

// Log de evento genérico
ActivityLogger::logEvent(
    type: 'business',
    event: 'invoice_generated',
    data: ['invoice_id' => 123, 'amount' => 1000.00],
    userId: auth()->id(),
    level: 'info'
);

// Log de API
ActivityLogger::logApiRequest(
    endpoint: '/api/users',
    statusCode: 200,
    duration: 145.2,
    userId: auth()->id()
);

// Log de integração
ActivityLogger::logIntegration(
    integrationType: 'payment_gateway',
    action: 'process_payment',
    success: true,
    duration: 1234.5,
    extra: ['transaction_id' => 'abc123']
);

// Log de erro crítico
try {
    // código...
} catch (\Exception $e) {
    ActivityLogger::logCriticalError(
        message: 'Failed to process payment',
        exception: $e,
        context: ['order_id' => 123]
    );
}

// Log de segurança
ActivityLogger::logSecurity(
    event: 'unauthorized_access_attempt',
    data: ['ip' => request()->ip(), 'path' => '/admin'],
    severity: 'warning'
);

// Log de performance
$start = microtime(true);
// operação...
$duration = (microtime(true) - $start) * 1000;

ActivityLogger::logPerformance(
    operation: 'report_generation',
    duration: $duration,
    metrics: ['rows_processed' => 10000]
);
```

### 2. Middleware de Logs HTTP

**Registrar no `bootstrap/app.php`** (Laravel 11):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append([
        \App\Http\Middleware\LogHttpRequests::class,
    ]);
})
```

**Ou no `app/Http/Kernel.php`** (Laravel 10):

```php
protected $middleware = [
    // ...
    \App\Http\Middleware\LogHttpRequests::class,
];
```

### 3. Jobs Logging

**Registrar o `QueueServiceProvider` no `config/app.php`**:

```php
'providers' => [
    // ...
    App\Providers\QueueServiceProvider::class,
],
```

Os logs acontecem automaticamente para todos os jobs.

## 🔍 Debugging em Desenvolvimento

### Laravel Telescope (Recomendado)

Para desenvolvimento local, instale o Telescope:

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Acesse: `http://localhost/telescope`

**⚠️ IMPORTANTE**: Desative em produção editando `TelescopeServiceProvider`:

```php
public function register()
{
    if ($this->app->environment('production')) {
        Telescope::night();
    }
}
```

## 📈 Métricas e Monitoramento

### Visualização de Logs em Tempo Real

```php
use App\Services\Logging\ActivityLogger;

// Obtém logs recentes do Redis
$recentLogs = ActivityLogger::getRecentLogs('api', limit: 100);

// Obtém métricas
$metrics = ActivityLogger::getMetrics();
```

### Golden Signals (Métricas Chave)

O sistema de logs permite monitorar os "4 Golden Signals":

1. **Latência**: `duration_ms` em todos os logs
2. **Tráfego**: Contagem de requests no Redis (métricas)
3. **Erros**: Canal `critical` + status codes 5xx
4. **Saturação**: `memory_usage_mb`, queries lentas

## 🚀 Configuração de Produção

### 1. Variáveis de Ambiente

```env
# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info
LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter

# Sentry (opcional, mas recomendado)
SENTRY_LARAVEL_DSN=https://key@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=0.2

# Redis (para logs em tempo real)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 2. Docker Compose (Monitoramento)

```yaml
# docker/docker-compose.monitoring.yml
services:
  promtail:
    image: grafana/promtail:latest
    volumes:
      - /var/lib/docker/containers:/var/lib/docker/containers:ro
      - ./promtail-config.yml:/etc/promtail/config.yml
    command: -config.file=/etc/promtail/config.yml

  loki:
    image: grafana/loki:latest
    ports:
      - "3100:3100"

  grafana:
    image: grafana/grafana:latest
    ports:
      - "3000:3000"
    environment:
      - GF_AUTH_ANONYMOUS_ENABLED=true
```

### 3. Rotação de Logs

Os logs já estão configurados com rotação automática:
- `laravel.log`: 14 dias
- `events.log`: 30 dias
- `critical.log`: 90 dias
- `queries.log`: 7 dias
- `jobs.log`: 30 dias

Para limpar logs manualmente:

```bash
# Limpar logs antigos
find storage/logs -name "*.log" -mtime +90 -delete
```

## 🎯 Checklist de Implementação

- [x] Contexto global com request_id (AppServiceProvider)
- [x] Logs estruturados em JSON para produção
- [x] Monitoramento de queries lentas
- [x] ActivityLogger com 5 perguntas do log perfeito
- [x] Middleware de log completo de requisições HTTP
- [x] Log estruturado de Jobs e Queues
- [x] Proteção de dados sensíveis (mascaramento)
- [x] Suporte a Sentry (documentado)
- [x] Canais separados por tipo (events, critical, queries, jobs)
- [x] Métricas de sistema (memória, duração, etc)
- [x] Stack trace estruturado para erros
- [ ] Configurar Promtail + Loki + Grafana (opcional)
- [ ] Instalar Sentry (opcional, mas recomendado)
- [ ] Configurar alertas no Grafana
- [ ] Instalar Telescope para desenvolvimento (opcional)

## 🔧 Troubleshooting

### Logs não aparecem

1. Verifique permissões da pasta `storage/logs`:
```bash
chmod -R 775 storage/logs
chown -R www-data:www-data storage/logs
```

2. Verifique o canal de log no `.env`:
```env
LOG_CHANNEL=stack
```

3. Limpe o cache:
```bash
php artisan config:clear
php artisan cache:clear
```

### Logs muito grandes

Se os logs estão enchendo o disco:

1. Reduza o tempo de retenção no `config/logging.php`:
```php
'days' => 7,  // ao invés de 14
```

2. Aumente o threshold de queries lentas no `AppServiceProvider`:
```php
$threshold = 5000;  // 5 segundos ao invés de 1
```

3. Desative logs de desenvolvimento em produção:
```env
LOG_LEVEL=warning  # ao invés de debug
```

### Performance impactada

1. Desative o Telescope em produção
2. Use Redis apenas se disponível (o código já trata isso)
3. Reduza o SENTRY_TRACES_SAMPLE_RATE:
```env
SENTRY_TRACES_SAMPLE_RATE=0.1  # 10% ao invés de 20%
```

## 📚 Recursos Adicionais

- [Documentação Monolog](https://github.com/Seldaek/monolog)
- [Laravel Logging](https://laravel.com/docs/11.x/logging)
- [Grafana Loki](https://grafana.com/oss/loki/)
- [Sentry Laravel](https://docs.sentry.io/platforms/php/guides/laravel/)
- [Laravel Telescope](https://laravel.com/docs/11.x/telescope)

## 🎓 Próximos Passos

1. **Implementar alertas**: Configure Grafana para enviar alertas quando:
   - Taxa de erros 5xx > 5% em 5 minutos
   - Queries lentas > 10 em 1 minuto
   - Jobs falhando > 3 em 5 minutos

2. **Dashboard Grafana**: Crie painéis para:
   - Taxa de requisições por endpoint
   - Tempo médio de resposta (P50, P95, P99)
   - Top 10 queries mais lentas
   - Jobs executados vs falhados

3. **Notificações**: Configure Slack/Discord para alertas críticos

---

**Sistema implementado seguindo as melhores práticas de "Structured Logging" e "Observability" para aplicações Laravel em produção 24/7.**
