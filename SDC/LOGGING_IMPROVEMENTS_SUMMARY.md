# Resumo das Melhorias do Sistema de Logs

## 📋 O Que Foi Implementado

Sistema de logging **de ponta** para produção 24/7, seguindo as melhores práticas do **Structured Logging** e baseado no documento `papiro2.md`.

---

## ✅ Melhorias Implementadas

### 1. **Contexto Global com Request ID (UUID)**
📁 Arquivo: [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php)

**O que foi feito:**
- Cada requisição HTTP recebe um UUID único (`request_id`)
- O `request_id` é adicionado automaticamente a TODOS os logs
- Permite rastrear o fluxo completo de uma requisição do início ao fim
- Compatível com Laravel 10 e 11+

**Benefício:**
```json
{
  "request_id": "9d7f8e2a-3c1b-4567-8901-23456789abcd",
  "message": "User logged in",
  "url": "/api/login",
  "duration_ms": 234
}
```

Agora você pode filtrar todos os logs de uma requisição específica no Grafana:
```logql
{app="laravel"} | json | request_id="9d7f8e2a-3c1b-4567-8901-23456789abcd"
```

---

### 2. **Logs Estruturados em JSON para Produção**
📁 Arquivo: [config/logging.php](config/logging.php)

**O que foi feito:**
- Canal `json_stderr` configurado com `JsonFormatter`
- Logs estruturados com processadores Monolog:
  - `IntrospectionProcessor`: Adiciona arquivo/linha do código
  - `WebProcessor`: Adiciona IP, URL, método HTTP
  - `MemoryUsageProcessor`: Adiciona uso de memória
- Em produção: JSON para máquinas (Grafana/Loki)
- Em desenvolvimento: Texto para leitura humana

**Benefício:**
Logs agora são **queryable** em ferramentas de análise. Você pode fazer queries complexas:
```logql
{app="laravel"} | json | status_code >= 500 | duration_ms > 1000
```

---

### 3. **Monitoramento Automático de Queries Lentas**
📁 Arquivo: [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php:63-87)

**O que foi feito:**
- `DB::listen()` monitora TODAS as queries
- Threshold configurável por ambiente:
  - Produção: > 1000ms (1 segundo)
  - Desenvolvimento: > 2000ms (2 segundos)
- Queries > 2x threshold → Log CRÍTICO
- Logs incluem: SQL, bindings, tempo, URL, user_id

**Benefício:**
Detecta problemas de performance automaticamente. Exemplo de log:
```json
{
  "event": "Slow Query Detected",
  "sql": "SELECT * FROM tasks WHERE...",
  "time_ms": 2345,
  "url": "/api/demandas",
  "user_id": 123,
  "severity": "critical"
}
```

---

### 4. **ActivityLogger Enriquecido**
📁 Arquivo: [app/Services/Logging/ActivityLogger.php](app/Services/Logging/ActivityLogger.php)

**O que foi feito:**
- Logs agora respondem às **5 perguntas do "Log Perfeito"**:
  1. **Timestamp**: `timestamp` (ISO 8601)
  2. **Level**: `severity` (info/warning/error/critical)
  3. **Context**: `request_id`, `user_id`, `ip_address`, `environment`
  4. **Message**: `event_name`, `event_type`
  5. **Trace**: `source` (arquivo, linha, classe, função)

- Contexto enriquecido adicionado:
  ```json
  {
    "request_id": "uuid",
    "user_id": 123,
    "ip_address": "192.168.1.1",
    "environment": "production",
    "app_name": "SDC",
    "hostname": "server-01",
    "source": {
      "file": "TaskController.php",
      "line": 42,
      "class": "TaskController",
      "function": "store"
    }
  }
  ```

- Método `logCriticalError()` melhorado:
  - Stack trace estruturado (top 10 chamadas)
  - Exception anterior (se existir)
  - Métricas de sistema (memória, PHP version)

**Benefício:**
Logs muito mais ricos em contexto para debugging rápido em produção.

---

### 5. **Middleware de Log Completo de Requisições HTTP**
📁 Arquivo: [app/Http/Middleware/LogHttpRequests.php](app/Http/Middleware/LogHttpRequests.php) ⭐ **NOVO**

**O que foi feito:**
- **Entrada**: Loga headers, query params, payload, tamanho
- **Saída**: Loga status code, resposta, duração
- **Proteção de dados sensíveis**:
  - Rotas mascaradas: login, register, password
  - Campos mascarados: password, token, secret, api_key, cvv
- Preview inteligente de resposta (não enche os logs)

**Exemplo de log:**
```json
{
  "direction": "incoming",
  "request_id": "uuid",
  "method": "POST",
  "url": "/api/demandas",
  "payload": {
    "titulo": "Nova demanda",
    "password": "***MASKED***"  // ✅ Protegido
  },
  "payload_size_bytes": 1234
}
```

**Benefício:**
Auditoria completa de todas as requisições. Essencial para debugging de problemas em produção.

---

### 6. **Monitoramento Completo de Jobs e Queues**
📁 Arquivo: [app/Providers/QueueServiceProvider.php](app/Providers/QueueServiceProvider.php) ⭐ **NOVO**

**O que foi feito:**
- Eventos monitorados:
  - `Queue::before` → Job iniciou
  - `Queue::after` → Job concluído
  - `Queue::failing` → Job falhou
- Calcula duração automática (armazena início no cache)
- Detecta jobs lentos (> 5 segundos)
- Logs críticos para jobs que falharam após max tentativas

**Exemplo de log:**
```json
{
  "job_id": "uuid",
  "job_name": "App\\Jobs\\ProcessReport",
  "queue": "default",
  "attempts": 3,
  "duration_ms": 5678,
  "status": "failed",
  "exception": {
    "class": "RuntimeException",
    "message": "Database timeout",
    "trace": [...]
  }
}
```

**Benefício:**
Rastreamento completo do ciclo de vida dos jobs. Detecta gargalos e falhas.

---

### 7. **Canais Separados e Otimizados**
📁 Arquivo: [config/logging.php](config/logging.php)

**Canais criados/otimizados:**

| Canal | Formato | Retenção | Uso |
|-------|---------|----------|-----|
| `json_stderr` | JSON | - | Produção (stdout para Docker) |
| `daily` | Texto | 14 dias | Desenvolvimento |
| `events` | JSON (prod) | 30 dias | ActivityLogger |
| `critical` | JSON | 90 dias | Erros críticos |
| `queries` | JSON | 7 dias | Queries lentas |
| `jobs` | JSON | 30 dias | Jobs/Queues |

**Benefício:**
Logs organizados por tipo, fáceis de filtrar e com retenção apropriada.

---

## 🎯 Comparação: Antes vs Depois

### Antes (Sistema Básico)
```
[2024-01-15 10:30:15] local.ERROR: Undefined index: user_id
```

❌ Onde aconteceu?
❌ Qual requisição?
❌ Qual usuário?
❌ Quanto tempo levou?
❌ Como reproduzir?

### Depois (Sistema de Ponta)
```json
{
  "timestamp": "2024-01-15T10:30:15.234Z",
  "severity": "error",
  "message": "Undefined index: user_id",
  "request_id": "9d7f8e2a-3c1b-4567-8901-23456789abcd",
  "user_id": 123,
  "url": "/api/demandas/create",
  "method": "POST",
  "ip_address": "192.168.1.100",
  "duration_ms": 234,
  "source": {
    "file": "TaskController.php",
    "line": 42,
    "class": "TaskController",
    "function": "store"
  },
  "environment": "production",
  "hostname": "server-01",
  "memory_usage_mb": 45.2
}
```

✅ TaskController.php linha 42
✅ Request ID: 9d7f8e2a-3c1b-4567-8901-23456789abcd
✅ Usuário ID: 123
✅ Duração: 234ms
✅ Completamente reproduzível

---

## 📊 Integração com Grafana/Loki

### Arquivo Criado
📁 [docker/monitoring/promtail/promtail-config.yml](docker/monitoring/promtail/promtail-config.yml) ⭐ **NOVO**

**O que contém:**
- 5 jobs configurados:
  - `laravel-app`: Logs gerais
  - `laravel-critical`: Apenas erros críticos
  - `laravel-queries`: Queries lentas
  - `laravel-jobs`: Jobs/Queues
  - `laravel-http`: Requisições HTTP
- Métricas automáticas (histogramas, contadores)
- Labels para filtragem fácil

**Queries exemplo no Grafana:**
```logql
# Top 10 endpoints mais lentos
topk(10,
  sum by (path) (rate({job="laravel-http"} | json | unwrap duration_ms [5m]))
)

# Taxa de erros por minuto
sum(rate({job="laravel-app"} | json | severity="error" [1m]))

# Jobs que falharam nas últimas 24h
{job="laravel-jobs"} | json | status="failed"
```

---

## 📚 Documentação Completa

### Arquivo Criado
📁 [LOGGING_SYSTEM.md](LOGGING_SYSTEM.md) ⭐ **NOVO**

**Conteúdo:**
- Arquitetura completa
- Todos os recursos explicados
- Exemplos de código
- Configuração de produção
- Integração com ferramentas (Sentry, Loki, Telescope)
- Troubleshooting
- Checklist de implementação

---

## 🚀 Como Começar a Usar

### 1. Ative o Middleware de Logs HTTP

**Laravel 11** (`bootstrap/app.php`):
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append([
        \App\Http\Middleware\LogHttpRequests::class,
    ]);
})
```

**Laravel 10** (`app/Http/Kernel.php`):
```php
protected $middleware = [
    \App\Http\Middleware\LogHttpRequests::class,
];
```

### 2. Registre o QueueServiceProvider

Em `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\QueueServiceProvider::class,
],
```

### 3. Configure Variáveis de Ambiente

No `.env`:
```env
LOG_CHANNEL=stack
LOG_LEVEL=info
LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter
```

### 4. (Opcional) Instale Sentry

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=your-dsn-here
```

### 5. (Opcional) Instale Telescope (Desenvolvimento)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

---

## 🎓 Próximos Passos Recomendados

1. **Configurar Grafana + Loki**
   - Use o arquivo `docker/monitoring/promtail/promtail-config.yml`
   - Configure dashboards para:
     - Taxa de requisições por endpoint
     - Tempo de resposta (P50, P95, P99)
     - Top 10 queries mais lentas
     - Jobs executados vs falhados

2. **Instalar Sentry**
   - Agrupa erros idênticos
   - Alertas em tempo real
   - Integração com Slack

3. **Criar Alertas**
   - Taxa de erros 5xx > 5% em 5 minutos
   - Queries lentas > 10 em 1 minuto
   - Jobs falhando > 3 em 5 minutos

---

## 📈 Métricas de Sucesso

Com esse sistema, você pode responder:

✅ **Qual foi o tempo médio de resposta nas últimas 24h?**
✅ **Quantos erros 5xx ocorreram hoje?**
✅ **Qual endpoint está mais lento?**
✅ **Quais queries estão demorando mais de 1 segundo?**
✅ **Quantos jobs falharam na última hora?**
✅ **Qual usuário teve o erro X?**
✅ **Como reproduzir o erro que aconteceu às 14:35?**

---

## 🎯 Conclusão

Você agora tem um **sistema de logs de nível enterprise**, compatível com:
- Grafana + Loki
- Datadog
- ELK Stack
- Sentry
- Splunk

Todas as **5 perguntas do "Log Perfeito"** são respondidas automaticamente em cada log. Seu sistema está pronto para **produção 24/7** com observabilidade completa.

---

**Arquivos modificados:**
- ✏️ [config/logging.php](config/logging.php)
- ✏️ [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php)
- ✏️ [app/Services/Logging/ActivityLogger.php](app/Services/Logging/ActivityLogger.php)

**Arquivos criados:**
- ⭐ [app/Http/Middleware/LogHttpRequests.php](app/Http/Middleware/LogHttpRequests.php)
- ⭐ [app/Providers/QueueServiceProvider.php](app/Providers/QueueServiceProvider.php)
- ⭐ [docker/monitoring/promtail/promtail-config.yml](docker/monitoring/promtail/promtail-config.yml)
- ⭐ [LOGGING_SYSTEM.md](LOGGING_SYSTEM.md)
- ⭐ [LOGGING_IMPROVEMENTS_SUMMARY.md](LOGGING_IMPROVEMENTS_SUMMARY.md)
