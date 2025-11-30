# 🚀 SDC - API de Alta Performance para 100k+ Usuários

## 📋 Visão Geral

Sistema de API RESTful escalável com suporte a:
- ✅ **100.000+ usuários simultâneos**
- ✅ **Webhooks com filas Redis**
- ✅ **Rate Limiting inteligente por tier**
- ✅ **Documentação Swagger/OpenAPI completa**
- ✅ **Processamento assíncrono com priorização**
- ✅ **Laravel Octane para máxima performance**

---

## 🏗️ Arquitetura

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │
       ▼
┌─────────────┐     ┌──────────────┐
│    Nginx    │────▶│ Laravel App  │
│  (Port 80)  │     │  (Octane)    │
└─────────────┘     └──────┬───────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
              ▼            ▼            ▼
       ┌──────────┐ ┌──────────┐ ┌──────────┐
       │  MySQL   │ │  Redis   │ │  Queue   │
       │  (DB)    │ │ (Cache)  │ │ Workers  │
       └──────────┘ └──────────┘ └──────────┘
```

---

## 🔑 Níveis de Requisição (Tiers)

### 1. **Public** (Público)
- 60 requisições/minuto
- Sem autenticação
- Para endpoints públicos

### 2. **Default** (Padrão)
- 300 requisições/minuto
- Usuários autenticados
- Tier padrão

### 3. **Premium**
- 1.000 requisições/minuto
- Usuários pagos
- Prioridade normal

### 4. **Enterprise**
- 5.000 requisições/minuto
- Grandes clientes
- Alta prioridade

### 5. **Webhook**
- 10.000 requisições/minuto
- Integrações externas
- Fila dedicada

### 6. **Internal**
- 100.000 requisições/minuto
- Serviços internos
- Sem limite rígido

---

## 📡 Endpoints de Webhooks

### Base URL
```
http://localhost:8000/api/v1/webhooks
```

### 1. **Receber Webhook** (POST)
```http
POST /api/v1/webhooks/receive
Content-Type: application/json

{
  "type": "payment.completed",
  "data": {
    "order_id": "12345",
    "amount": 100.50
  },
  "timestamp": "2025-11-27T10:00:00Z",
  "signature": "hmac_sha256_signature"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Webhook received and processed",
  "webhook_id": "wh_1234567890",
  "result": {
    "status": "processed",
    "type": "payment.completed"
  }
}
```

---

### 2. **Enviar Webhook Assíncrono** (POST)
```http
POST /api/v1/webhooks/send
Authorization: Bearer {token}
Content-Type: application/json

{
  "url": "https://example.com/webhook",
  "payload": {
    "event": "user.created",
    "user_id": 12345
  },
  "priority": "high",
  "headers": {
    "X-Custom-Header": "value"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Webhook queued for delivery",
  "priority": "high",
  "queue": "high",
  "estimated_delivery": "within 30 seconds"
}
```

**Prioridades disponíveis:**
- `low` - 5 minutos
- `normal` - 1 minuto
- `high` - 30 segundos
- `critical` - 10 segundos
- `webhook` - 45 segundos

---

### 3. **Enviar Webhook Síncrono** (POST)
```http
POST /api/v1/webhooks/send-sync
Authorization: Bearer {token}
Content-Type: application/json

{
  "url": "https://example.com/webhook",
  "payload": {
    "event": "test"
  },
  "timeout": 30
}
```

**Response:**
```json
{
  "success": true,
  "status": 200,
  "body": {
    "received": true
  },
  "duration_ms": 145.67
}
```

---

## 🔧 Configuração

### 1. Variáveis de Ambiente (.env)

```bash
# Queue
QUEUE_CONNECTION=redis
REDIS_QUEUE_CONNECTION=default

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

### 2. Executar Migrations

```bash
# Dentro do container ou localmente
php artisan migrate

# Ou via Docker
docker compose -f docker/docker-compose.yml exec app php artisan migrate
```

### 3. Gerar Documentação Swagger

```bash
# Gerar docs
php artisan l5-swagger:generate

# Acessar documentação
http://localhost:8000/api/documentation
```

---

## 🚀 Iniciar Workers de Fila

### Modo Desenvolvimento
```bash
# Worker padrão
php artisan queue:work redis --queue=critical,high,default,webhooks,low

# Worker crítico (apenas fila crítica)
php artisan queue:work redis-critical --queue=critical --tries=5

# Worker webhooks
php artisan queue:work redis-webhooks --queue=webhooks --tries=3
```

### Modo Produção (Supervisor)

Criar arquivo `/etc/supervisor/conf.d/sdc-workers.conf`:

```ini
[program:sdc-worker-critical]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis-critical --queue=critical --sleep=1 --tries=5 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/worker-critical.log
stopwaitsecs=3600

[program:sdc-worker-high]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis-high --queue=high --sleep=1 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/worker-high.log
stopwaitsecs=3600

[program:sdc-worker-webhooks]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis-webhooks --queue=webhooks --sleep=1 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=10
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/worker-webhooks.log
stopwaitsecs=3600

[program:sdc-worker-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis --queue=default --sleep=3 --tries=2 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=6
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/worker-default.log
stopwaitsecs=3600

[program:sdc-worker-low]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis-low --queue=low --sleep=5 --tries=1 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/worker-low.log
stopwaitsecs=3600
```

**Total de Workers:** 30 processos (4+8+10+6+2)

---

## 📊 Monitoramento

### Ver Status das Filas
```bash
# Ver jobs pendentes
php artisan queue:monitor redis:critical,high,default,webhooks,low

# Ver jobs falhados
php artisan queue:failed

# Retentar jobs falhados
php artisan queue:retry all
```

### Logs de Webhooks
```bash
# Via banco de dados
SELECT * FROM webhook_logs
WHERE success = false
ORDER BY created_at DESC
LIMIT 100;

# Ver webhooks lentos (> 1s)
SELECT * FROM webhook_logs
WHERE duration_ms > 1000
ORDER BY duration_ms DESC;
```

---

## 🧪 Testes

### Teste de Carga com Artillery

```yaml
# artillery-test.yml
config:
  target: "http://localhost:8000"
  phases:
    - duration: 60
      arrivalRate: 100
      name: "Warm up"
    - duration: 300
      arrivalRate: 1000
      name: "Sustained load - 1000 req/s"
    - duration: 60
      arrivalRate: 2000
      name: "Peak load - 2000 req/s"

scenarios:
  - name: "Send Webhook"
    flow:
      - post:
          url: "/api/v1/webhooks/send"
          headers:
            Authorization: "Bearer {{token}}"
          json:
            url: "https://webhook.site/unique-id"
            payload:
              test: true
            priority: "normal"
```

Execute:
```bash
artillery run artillery-test.yml
```

---

## 🔒 Segurança

### Validação de Webhooks Recebidos
```php
// No WebhookService.php
private function validateWebhookSignature(array $payload, string $source): bool
{
    $signature = request()->header('X-Webhook-Signature');
    $secret = config("webhooks.sources.{$source}.secret");

    $expected = hash_hmac('sha256', json_encode($payload), $secret);

    return hash_equals($expected, $signature);
}
```

### Envio Seguro
```php
// Sempre incluir assinatura
$headers = [
    'X-Webhook-Signature' => hash_hmac('sha256', json_encode($payload), $secret),
    'X-Webhook-Timestamp' => now()->timestamp,
];
```

---

## 📈 Otimizações para 100k Usuários

### 1. **Laravel Octane**
```bash
# Iniciar Octane (já configurado no Docker)
php artisan octane:start --server=roadrunner --workers=4
```

### 2. **Redis Optimization**
```ini
# redis.conf
maxmemory 2gb
maxmemory-policy allkeys-lru
tcp-backlog 511
timeout 0
tcp-keepalive 300
```

### 3. **MySQL Tuning**
```ini
# my.cnf
max_connections = 1000
innodb_buffer_pool_size = 4G
innodb_log_file_size = 512M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 256M
```

### 4. **Nginx**
```nginx
worker_processes auto;
worker_connections 4096;
keepalive_timeout 65;
client_max_body_size 64M;
```

---

## 🐛 Troubleshooting

### Jobs não estão sendo processados
```bash
# Verificar workers rodando
ps aux | grep queue:work

# Verificar conexão Redis
redis-cli ping

# Verificar filas
redis-cli
> LLEN queues:critical
> LLEN queues:webhooks
```

### Rate Limiting muito agressivo
```php
// Ajustar em ApiRateLimiter.php
'default' => [
    'max_attempts' => 500,  // Aumentar
    'decay_seconds' => 60,
],
```

### Webhooks falhando
```bash
# Ver logs
tail -f storage/logs/laravel.log

# Ver jobs falhados
php artisan queue:failed

# Retentar específico
php artisan queue:retry {id}
```

---

## 📚 Documentação Adicional

- **Swagger UI:** http://localhost:8000/api/documentation
- **Swagger JSON:** http://localhost:8000/api/documentation/json
- **Laravel Docs:** https://laravel.com/docs

---

## ✅ Checklist de Deploy

- [ ] Migrations executadas
- [ ] Swagger gerado
- [ ] Workers configurados no Supervisor
- [ ] Redis configurado e rodando
- [ ] Rate limiting testado
- [ ] Testes de carga executados
- [ ] Logs configurados
- [ ] Backup automático ativado
- [ ] Monitoramento ativo (opcional: Grafana)

---

**Sistema otimizado para 100.000+ usuários simultâneos! 🚀**
