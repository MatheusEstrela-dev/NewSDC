# Webhooks e Rate Limiting - Documentacao Tecnica

## Visao Geral

Este documento descreve a arquitetura de webhooks e rate limiting implementada no NewSDC, seguindo padroes DDD e principios SOLID.

---

## 1. Arquitetura de Webhooks

### 1.1 Fluxo de Recebimento (Inbound)

```
[Sistema Externo]
      |
      v
[WebhookController::receive()]  <-- Validacao basica + 202 Accepted
      |
      v
[WebhookService::receive()]     <-- Verifica idempotencia
      |
      v
[WebhookEvent Model]            <-- Registro no banco (idempotencia)
      |
      v
[ProcessInboundWebhook Job]     <-- Fila Redis (async)
      |
      v
[Handler especifico]            <-- Logica de negocio
```

### 1.2 Fluxo de Envio (Outbound)

```
[Aplicacao]
      |
      v
[WebhookService::send()]        <-- Verifica Circuit Breaker
      |
      v
[ProcessWebhook Job]            <-- Fila Redis (async)
      |
      v
[HTTP Request]                  <-- Exponential backoff
      |
      v
[Circuit Breaker]               <-- Registra sucesso/falha
```

---

## 2. Componentes

### 2.1 WebhookService
**Arquivo:** `app/Services/Webhook/WebhookService.php`

| Metodo | Descricao |
|--------|-----------|
| `send()` | Enfileira webhook de saida com Circuit Breaker |
| `sendSync()` | Envio sincrono (apenas testes) |
| `receive()` | Recebe webhook com idempotencia |
| `validateSignature()` | Valida assinatura HMAC |

### 2.2 WebhookSignatureValidator
**Arquivo:** `app/Services/Webhook/WebhookSignatureValidator.php`

Responsavel por validar assinaturas HMAC de webhooks recebidos.

**Providers suportados:**
- GitHub (`X-Hub-Signature-256`)
- Stripe (`Stripe-Signature`)
- GitLab (`X-Gitlab-Token`)
- Default (`X-Webhook-Signature`)

**Uso:**
```php
$validator = app(WebhookSignatureValidator::class);

// Validar webhook recebido
$isValid = $validator->validate($rawPayload, $signature, 'github');

// Gerar assinatura para envio
$signature = $validator->generateSignature($payload);
```

### 2.3 CircuitBreakerService
**Arquivo:** `app/Services/Webhook/CircuitBreakerService.php`

Implementa o padrao Circuit Breaker para proteger contra falhas em cascata.

**Estados:**
- `closed`: Funcionamento normal
- `open`: Bloqueando requisicoes (servico instavel)
- `half-open`: Testando se servico voltou

**Configuracao:** `config/webhooks.php`
```php
'circuit_breaker' => [
    'threshold' => 5,   // Falhas para abrir
    'timeout' => 60,    // Segundos para half-open
],
```

**Uso:**
```php
$cb = app(CircuitBreakerService::class);

if ($cb->isOpen('api.externa.com')) {
    throw new CircuitBreakerOpenException('api.externa.com');
}

try {
    // requisicao
    $cb->recordSuccess('api.externa.com');
} catch (Exception $e) {
    $cb->recordFailure('api.externa.com');
}
```

### 2.4 WebhookEvent Model
**Arquivo:** `app/Models/WebhookEvent.php`

Tabela de idempotencia para webhooks recebidos.

**Status:**
- `pending`: Aguardando processamento
- `processing`: Em processamento
- `completed`: Concluido com sucesso
- `failed`: Falhou (vai tentar novamente)
- `dead_letter`: Falhou apos todas tentativas

**Uso:**
```php
// Verificar idempotencia
$existing = WebhookEvent::where('external_event_id', $id)
    ->where('provider', 'stripe')
    ->first();

if ($existing && $existing->isProcessedOrProcessing()) {
    return; // Ja processado
}
```

---

## 3. Jobs

### 3.1 ProcessWebhook (Outbound)
**Arquivo:** `app/Jobs/ProcessWebhook.php`

Processa webhooks de saida com:
- Exponential backoff: `[5, 30, 300, 3600]` segundos
- Circuit Breaker integrado
- Dead Letter Queue em caso de falha permanente

### 3.2 ProcessInboundWebhook (Inbound)
**Arquivo:** `app/Jobs/ProcessInboundWebhook.php`

Processa webhooks recebidos com:
- Idempotencia via WebhookEvent
- Exponential backoff: `[5, 30, 300, 3600]` segundos
- Handlers extensiveis por tipo de evento

---

## 4. Rate Limiting

### 4.1 Configuracao
**Arquivo:** `config/queue.php` - Default: `redis`

**Tiers disponives:**
| Tier | Requests/min | Uso |
|------|-------------|-----|
| public | 60 | IPs nao autenticados |
| authenticated | 300 | Usuarios logados |
| api_client | 1000 | Integradores API |
| webhook | 50000 | Webhooks de sistemas |

### 4.2 Frontend (429 Handler)
**Arquivo:** `resources/js/bootstrap.js`

Interceptor Axios que dispara evento `rate-limit-exceeded`:
```javascript
window.addEventListener('rate-limit-exceeded', (e) => {
    console.log('Aguarde', e.detail.retryAfter, 'segundos');
});
```

---

## 5. Logging

### 5.1 Canais Dedicados
**Arquivo:** `config/logging.php`

| Canal | Arquivo | Retencao |
|-------|---------|----------|
| `webhooks` | `logs/webhooks/webhooks.log` | 30 dias |
| `circuit_breaker` | `logs/circuit_breaker.log` | 14 dias |
| `rate_limit` | `logs/rate_limit.log` | 7 dias |

**Uso:**
```php
Log::channel('webhooks')->info('Webhook recebido', ['id' => $id]);
Log::channel('circuit_breaker')->warning('Circuito aberto', ['service' => $s]);
```

---

## 6. Filas

### 6.1 Configuracao Redis
**Arquivo:** `config/queue.php`

| Fila | Uso | retry_after |
|------|-----|-------------|
| `critical` | Alta prioridade | 30s |
| `high` | Segunda prioridade | 60s |
| `webhooks` | Processamento de webhooks | 120s |
| `low` | Background | 300s |
| `dead-letter` | Falhas permanentes | 24h |

### 6.2 Executar Workers
```bash
# Webhook worker
php artisan queue:work redis --queue=webhooks,default

# Todos os workers
php artisan queue:work redis --queue=critical,high,webhooks,default,low

# Dead Letter (analise manual)
php artisan queue:work redis --queue=dead-letter
```

---

## 7. Variaveis de Ambiente

```env
# Queue
QUEUE_CONNECTION=redis

# Webhooks
WEBHOOK_SECRET_KEY=sua_chave_secreta_aqui
WEBHOOK_MAX_RETRIES=4
WEBHOOK_RETRY_BACKOFF=5,30,300,3600
WEBHOOK_LOG_PAYLOADS=false

# Circuit Breaker
CIRCUIT_BREAKER_THRESHOLD=5
CIRCUIT_BREAKER_TIMEOUT=60

# Providers
GITHUB_WEBHOOK_SECRET=github_secret
STRIPE_WEBHOOK_SECRET=stripe_secret
GITLAB_WEBHOOK_SECRET=gitlab_secret
```

---

## 8. Migration

**Arquivo:** `database/migrations/2026_02_24_000001_create_webhook_events_table.php`

```bash
php artisan migrate
```

---

## 9. Exceptions

### CircuitBreakerOpenException
**Arquivo:** `app/Exceptions/CircuitBreakerOpenException.php`

Lancada quando o circuit breaker esta aberto. Retorna HTTP 503.

```php
try {
    $webhookService->send($url, $payload);
} catch (CircuitBreakerOpenException $e) {
    // Servico indisponivel
    return response()->json([
        'error' => 'Service temporarily unavailable',
        'service' => $e->getService(),
    ], 503);
}
```

---

## 10. Swagger/OpenAPI

Endpoints documentados em `/api/documentation`:

- `POST /api/v1/webhooks/receive` - Receber webhook (202 Accepted)
- `POST /api/v1/webhooks/send` - Enviar webhook async (202 Accepted)
- `POST /api/v1/webhooks/send-sync` - Enviar webhook sync (200 OK)

Response 429 inclui headers:
- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
- `X-RateLimit-Reset`
- `Retry-After`
