# 🔌 Guia Completo - Plug & Play via Swagger

## 🎯 O que é o Sistema Plug-and-Play?

O sistema permite **integrar qualquer API externa** de forma dinâmica, sem precisar programar. Tudo via Swagger UI.

---

## 🚀 ACESSAR O SWAGGER

```
http://localhost:8000/api/documentation
```

**Login necessário:** Use seu usuário do sistema

---

## 📋 ENDPOINTS PRINCIPAIS

### 1️⃣ **Ver Templates Disponíveis**

**Endpoint:** `GET /api/v1/integration/templates`

**O que faz:** Lista integrações pré-configuradas (Salesforce, SAP, Stripe, HubSpot)

**Como usar no Swagger:**

1. Clique em **Integration** > **GET /api/v1/integration/templates**
2. Clique em **Try it out**
3. Clique em **Execute**

**Resposta:**
```json
[
  {
    "id": "salesforce_create_lead",
    "name": "Salesforce - Criar Lead",
    "type": "rest_api",
    "description": "Cria novo lead no Salesforce CRM",
    "endpoint": "https://na1.salesforce.com/services/data/v58.0/sobjects/Lead",
    "method": "POST",
    "required_fields": ["LastName", "Company"],
    "auth_type": "bearer"
  },
  {
    "id": "stripe_create_customer",
    "name": "Stripe - Criar Cliente",
    "type": "rest_api",
    ...
  }
]
```

---

### 2️⃣ **Executar Integração**

**Endpoint:** `POST /api/v1/integration/execute`

**O que faz:** Executa integração com qualquer API externa

---

## 🎨 EXEMPLOS PRÁTICOS NO SWAGGER

### **Exemplo 1: Integrar com Salesforce (criar lead)**

#### Passo 1: Abrir Swagger
```
http://localhost:8000/api/documentation
```

#### Passo 2: Autenticar
1. Clique no botão **Authorize** (cadeado no topo)
2. Cole seu Bearer token
3. Clique em **Authorize**

#### Passo 3: Executar Integração
1. Vá em **Integration** > **POST /api/v1/integration/execute**
2. Clique em **Try it out**
3. Cole o JSON abaixo no body:

```json
{
  "integration_type": "rest_api",
  "action": "create_lead",
  "endpoint": "https://na1.salesforce.com/services/data/v58.0/sobjects/Lead",
  "method": "POST",
  "payload": {
    "LastName": "Silva",
    "FirstName": "João",
    "Company": "Empresa XPTO",
    "Email": "joao@xpto.com",
    "Phone": "+5511999999999"
  },
  "auth": {
    "type": "bearer",
    "token": "SEU_TOKEN_SALESFORCE_AQUI"
  },
  "mapping": {
    "lead_id": "Id",
    "lead_status": "Status"
  },
  "priority": "high",
  "async": true,
  "bidirectional": true,
  "callback_url": "https://seu-sistema.com/webhook/salesforce"
}
```

4. Clique em **Execute**

#### Resposta esperada:
```json
{
  "success": true,
  "integration_id": "int_abc123xyz",
  "queue": "high",
  "estimated_delivery": "within 30 seconds",
  "callback_configured": true
}
```

---

### **Exemplo 2: Integrar com API Custom (qualquer endpoint)**

```json
{
  "integration_type": "rest_api",
  "action": "send_data",
  "endpoint": "https://api.exemplo.com/v1/dados",
  "method": "POST",
  "payload": {
    "nome": "Teste",
    "valor": 100
  },
  "auth": {
    "type": "api_key",
    "key": "X-API-Key",
    "value": "sua-chave-aqui"
  },
  "priority": "normal",
  "async": false
}
```

**Resultado:** Resposta imediata (síncrona)

---

### **Exemplo 3: Integrar com Stripe (criar cliente)**

```json
{
  "integration_type": "rest_api",
  "action": "create_customer",
  "endpoint": "https://api.stripe.com/v1/customers",
  "method": "POST",
  "payload": {
    "email": "cliente@exemplo.com",
    "name": "Maria Santos",
    "description": "Cliente VIP"
  },
  "auth": {
    "type": "bearer",
    "token": "sk_test_SUA_CHAVE_STRIPE"
  },
  "headers": {
    "Stripe-Version": "2023-10-16"
  },
  "priority": "high",
  "async": true
}
```

---

### **Exemplo 4: Integração SOAP (sistemas legados)**

```json
{
  "integration_type": "soap",
  "action": "consultar_cliente",
  "endpoint": "http://sistema-legado.com/soap",
  "method": "POST",
  "payload": {
    "cpf": "12345678900"
  },
  "soap_config": {
    "action": "ConsultarCliente",
    "namespace": "http://sistema.com/ws"
  },
  "priority": "normal",
  "async": false
}
```

---

### **Exemplo 5: GraphQL**

```json
{
  "integration_type": "graphql",
  "action": "query_users",
  "endpoint": "https://api.exemplo.com/graphql",
  "method": "POST",
  "payload": {
    "query": "{ users(limit: 10) { id name email } }"
  },
  "auth": {
    "type": "bearer",
    "token": "SEU_TOKEN"
  },
  "priority": "normal",
  "async": false
}
```

---

## 🔑 PARÂMETROS DISPONÍVEIS

### **integration_type** (obrigatório)
- `rest_api` - API REST padrão
- `graphql` - API GraphQL
- `soap` - SOAP/WSDL
- `webhook` - Webhook simples
- `database` - Conexão direta com DB
- `file_transfer` - FTP/SFTP

### **method** (para REST)
- `GET`, `POST`, `PUT`, `PATCH`, `DELETE`

### **auth.type**
- `bearer` - Token Bearer (OAuth)
- `basic` - Basic Auth (user:pass)
- `api_key` - API Key (header custom)
- `oauth2` - OAuth 2.0

### **priority**
- `low` - Baixa (fila: low)
- `normal` - Normal (fila: default)
- `high` - Alta (fila: high)
- `critical` - Crítica (fila: critical)
- `webhook` - Webhook (fila: webhooks)

### **async**
- `true` - Executa em fila (retorna ID imediato)
- `false` - Executa síncrono (aguarda resposta)

### **bidirectional**
- `true` - Ativa callback quando concluir
- `false` - Apenas envia dados

### **mapping** (opcional)
Mapeia campos da resposta para seu sistema:
```json
{
  "mapping": {
    "seu_campo_local": "campo_retornado_api",
    "id_interno": "external_id"
  }
}
```

---

## 🔄 FLUXO COMPLETO

### **Modo Assíncrono (async: true)**

```
┌─────────────────────────────────────────┐
│ 1. Cliente envia via Swagger            │
│    POST /api/v1/integration/execute     │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 2. Sistema retorna ID imediatamente     │
│    { integration_id: "int_123" }        │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 3. Job entra na fila Redis              │
│    (queue: high, critical, etc)         │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 4. Worker processa em background        │
│    - Faz requisição para API externa    │
│    - Aplica retry se falhar             │
│    - Loga tudo                          │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 5. Se bidirectional = true:             │
│    Envia callback com resultado         │
│    POST callback_url                    │
└─────────────────────────────────────────┘
```

### **Modo Síncrono (async: false)**

```
┌─────────────────────────────────────────┐
│ Cliente envia via Swagger               │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ Sistema aguarda resposta da API externa │
│ (timeout: até 30s)                      │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ Retorna resultado direto no Swagger     │
│ { success: true, data: {...} }          │
└─────────────────────────────────────────┘
```

---

## 📊 VERIFICAR STATUS DA INTEGRAÇÃO

**Endpoint:** `GET /api/v1/integration/status/{integration_id}`

**No Swagger:**
1. Vá em **Integration** > **GET /api/v1/integration/status/{id}**
2. **Try it out**
3. Cole o `integration_id` que você recebeu
4. **Execute**

**Resposta:**
```json
{
  "integration_id": "int_abc123",
  "status": "completed",
  "started_at": "2025-11-27T23:30:00Z",
  "completed_at": "2025-11-27T23:30:15Z",
  "response": {
    "status_code": 200,
    "data": {
      "Id": "00Q5g000001234",
      "success": true
    }
  },
  "mapped_data": {
    "lead_id": "00Q5g000001234"
  }
}
```

---

## 🎯 CASOS DE USO REAIS

### **Caso 1: Sincronizar Dados com ERP**
```json
{
  "integration_type": "rest_api",
  "action": "sync_order",
  "endpoint": "https://erp.empresa.com/api/orders",
  "method": "POST",
  "payload": {
    "order_id": "ORD-12345",
    "customer_id": "CUST-789",
    "total": 1500.00
  },
  "auth": {
    "type": "api_key",
    "key": "X-ERP-Token",
    "value": "token-secreto"
  },
  "priority": "high",
  "async": true,
  "bidirectional": true,
  "callback_url": "https://meu-sistema.com/erp-callback"
}
```

### **Caso 2: Enviar Notificação SMS (Twilio)**
```json
{
  "integration_type": "rest_api",
  "action": "send_sms",
  "endpoint": "https://api.twilio.com/2010-04-01/Accounts/ACCOUNT_SID/Messages.json",
  "method": "POST",
  "payload": {
    "To": "+5511999999999",
    "From": "+15555555555",
    "Body": "Sua entrega está a caminho!"
  },
  "auth": {
    "type": "basic",
    "username": "ACCOUNT_SID",
    "password": "AUTH_TOKEN"
  },
  "priority": "high",
  "async": true
}
```

### **Caso 3: Consultar CEP (ViaCEP - público)**
```json
{
  "integration_type": "rest_api",
  "action": "consultar_cep",
  "endpoint": "https://viacep.com.br/ws/01310100/json/",
  "method": "GET",
  "priority": "normal",
  "async": false
}
```

**Resposta imediata:**
```json
{
  "success": true,
  "data": {
    "cep": "01310-100",
    "logradouro": "Avenida Paulista",
    "bairro": "Bela Vista",
    "localidade": "São Paulo",
    "uf": "SP"
  }
}
```

---

## 🔒 SEGURANÇA

### **Headers Customizados**
```json
{
  "headers": {
    "X-Custom-Header": "valor",
    "User-Agent": "SDC-Integration/1.0"
  }
}
```

### **Validação HMAC (para webhooks recebidos)**
O sistema valida automaticamente webhooks com assinatura HMAC.

### **Rate Limiting**
Integrações respeitam os limites:
- **Public tier:** 60/min
- **Enterprise tier:** 5000/min

---

## 📝 LOGS E DEBUGGING

**Ver logs da integração:**

1. Acesse: http://localhost:8000/api/v1/logs/recent?type=integration
2. Ou veja no Grafana: http://localhost:3000

**Exemplo de log:**
```json
{
  "timestamp": "2025-11-27T23:30:00Z",
  "type": "integration",
  "event": "executed",
  "data": {
    "integration_id": "int_123",
    "endpoint": "https://api.salesforce.com/...",
    "status_code": 200,
    "duration_ms": 567.8,
    "success": true
  }
}
```

---

## ⚡ DICAS RÁPIDAS

1. **Use async: true** para operações demoradas (> 5s)
2. **Use priority: high** para dados críticos
3. **Sempre configure callback_url** em async para receber resultado
4. **Teste primeiro com async: false** para debug
5. **Use mapping** para transformar resposta automaticamente

---

## 🎉 EXEMPLO COMPLETO NO SWAGGER

**Passo a passo visual:**

```
1. Abrir Swagger
   http://localhost:8000/api/documentation

2. Authorize (cadeado)
   Bearer: seu-token-aqui

3. Ir em "Integration"

4. Clicar em POST /api/v1/integration/execute

5. Try it out

6. Colar exemplo (Salesforce, Stripe, etc)

7. Execute

8. Ver resposta:
   ✅ { integration_id: "int_123", queue: "high" }

9. Verificar status:
   GET /api/v1/integration/status/int_123

10. Ver resultado final! 🚀
```

---

## 📚 TEMPLATES PRÉ-CONFIGURADOS

Acesse `GET /api/v1/integration/templates` para ver todos os templates prontos:

- ✅ Salesforce (CRM)
- ✅ SAP (ERP)
- ✅ Stripe (Pagamentos)
- ✅ HubSpot (Marketing)

**Use-os como base para suas integrações!**

---

## 🆘 TROUBLESHOOTING

### Erro: "Unauthorized"
→ Configure o header Authorization corretamente

### Erro: "Timeout"
→ Use `async: true` para APIs lentas

### Erro: "Invalid mapping"
→ Verifique se os campos existem na resposta da API

### Erro: "Queue backlog"
→ Muitas requisições. Use priority adequado

---

## ✅ CHECKLIST

- [ ] Obter token de autenticação
- [ ] Ver templates disponíveis
- [ ] Testar integração simples (ViaCEP)
- [ ] Testar integração async
- [ ] Configurar callback_url
- [ ] Verificar logs
- [ ] Testar mapping de campos
- [ ] Integrar com sistema real

---

**🎯 Pronto! Agora você pode integrar qualquer API via Swagger sem programar!**

**Acessos:**
- Swagger: http://localhost:8000/api/documentation
- Logs: http://localhost:8000/api/v1/logs/recent
- Grafana: http://localhost:3000
