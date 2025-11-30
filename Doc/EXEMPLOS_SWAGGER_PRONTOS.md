# 🚀 Exemplos Prontos para COPIAR/COLAR no Swagger

## 📍 Acesso: http://localhost:8000/api/documentation

---

## 1️⃣ EXEMPLO MAIS SIMPLES - Consultar CEP (ViaCEP)

**Cole no Swagger:**

```json
{
  "integration_type": "rest_api",
  "action": "consultar_cep",
  "endpoint": "https://viacep.com.br/ws/01310100/json/",
  "method": "GET",
  "async": false
}
```

**Resultado:** Dados do CEP 01310-100 (Av. Paulista)

---

## 2️⃣ CRIAR LEAD NO SALESFORCE

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
    "Email": "joao@xpto.com"
  },
  "auth": {
    "type": "bearer",
    "token": "COLE_SEU_TOKEN_SALESFORCE_AQUI"
  },
  "mapping": {
    "lead_id": "Id",
    "lead_status": "Status"
  },
  "priority": "high",
  "async": true,
  "callback_url": "https://webhook.site/seu-webhook-aqui"
}
```

**Resultado:** Lead criado + callback com ID

---

## 3️⃣ CRIAR CLIENTE NO STRIPE

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
    "token": "sk_test_COLE_SUA_CHAVE_STRIPE"
  },
  "headers": {
    "Stripe-Version": "2023-10-16"
  },
  "priority": "high",
  "async": true
}
```

---

## 4️⃣ ENVIAR MENSAGEM SLACK

```json
{
  "integration_type": "rest_api",
  "action": "send_message",
  "endpoint": "https://hooks.slack.com/services/SEU/WEBHOOK/AQUI",
  "method": "POST",
  "payload": {
    "text": "🎉 Nova venda realizada!",
    "channel": "#vendas",
    "username": "SDC Bot"
  },
  "priority": "normal",
  "async": true
}
```

---

## 5️⃣ ENVIAR EMAIL (SendGrid)

```json
{
  "integration_type": "rest_api",
  "action": "send_email",
  "endpoint": "https://api.sendgrid.com/v3/mail/send",
  "method": "POST",
  "payload": {
    "personalizations": [
      {
        "to": [{"email": "destino@exemplo.com"}],
        "subject": "Bem-vindo!"
      }
    ],
    "from": {"email": "noreply@seusite.com"},
    "content": [
      {
        "type": "text/plain",
        "value": "Olá! Seja bem-vindo ao nosso sistema."
      }
    ]
  },
  "auth": {
    "type": "bearer",
    "token": "SG.COLE_SUA_CHAVE_SENDGRID"
  },
  "priority": "high",
  "async": true
}
```

---

## 6️⃣ CONSULTAR DADOS - GitHub API (Público)

```json
{
  "integration_type": "rest_api",
  "action": "get_user",
  "endpoint": "https://api.github.com/users/octocat",
  "method": "GET",
  "headers": {
    "User-Agent": "SDC-Integration"
  },
  "async": false
}
```

**Resultado:** Dados do usuário "octocat"

---

## 7️⃣ CRIAR TAREFA NO TRELLO

```json
{
  "integration_type": "rest_api",
  "action": "create_card",
  "endpoint": "https://api.trello.com/1/cards",
  "method": "POST",
  "payload": {
    "name": "Nova Tarefa",
    "desc": "Descrição da tarefa",
    "idList": "ID_DA_SUA_LISTA",
    "key": "SUA_CHAVE_TRELLO",
    "token": "SEU_TOKEN_TRELLO"
  },
  "priority": "normal",
  "async": true
}
```

---

## 8️⃣ ENVIAR SMS (Twilio)

```json
{
  "integration_type": "rest_api",
  "action": "send_sms",
  "endpoint": "https://api.twilio.com/2010-04-01/Accounts/SEU_ACCOUNT_SID/Messages.json",
  "method": "POST",
  "payload": {
    "To": "+5511999999999",
    "From": "+15555555555",
    "Body": "Sua entrega está a caminho!"
  },
  "auth": {
    "type": "basic",
    "username": "SEU_ACCOUNT_SID",
    "password": "SEU_AUTH_TOKEN"
  },
  "priority": "high",
  "async": true
}
```

---

## 9️⃣ WEBHOOK GENÉRICO (POST)

```json
{
  "integration_type": "webhook",
  "action": "notify",
  "endpoint": "https://webhook.site/seu-id-aqui",
  "method": "POST",
  "payload": {
    "evento": "nova_venda",
    "valor": 150.00,
    "cliente": "João Silva"
  },
  "priority": "normal",
  "async": true
}
```

**Dica:** Use https://webhook.site para testar e ver os dados chegando!

---

## 🔟 GRAPHQL (GitHub)

```json
{
  "integration_type": "graphql",
  "action": "query_repos",
  "endpoint": "https://api.github.com/graphql",
  "method": "POST",
  "payload": {
    "query": "{ viewer { login repositories(first: 5) { nodes { name } } } }"
  },
  "auth": {
    "type": "bearer",
    "token": "ghp_SEU_TOKEN_GITHUB"
  },
  "async": false
}
```

---

## 1️⃣1️⃣ TESTE RÁPIDO - Echo API

```json
{
  "integration_type": "rest_api",
  "action": "test",
  "endpoint": "https://httpbin.org/post",
  "method": "POST",
  "payload": {
    "teste": "funcionando",
    "timestamp": "2025-11-27"
  },
  "async": false
}
```

**Resultado:** Echo de todos os dados enviados (perfeito para testar!)

---

## 1️⃣2️⃣ API COM AUTENTICAÇÃO API KEY

```json
{
  "integration_type": "rest_api",
  "action": "get_data",
  "endpoint": "https://api.exemplo.com/v1/data",
  "method": "GET",
  "auth": {
    "type": "api_key",
    "key": "X-API-Key",
    "value": "sua-api-key-aqui"
  },
  "async": false
}
```

---

## 1️⃣3️⃣ BUSCAR COTAÇÃO DO DÓLAR (Banco Central)

```json
{
  "integration_type": "rest_api",
  "action": "cotacao_dolar",
  "endpoint": "https://economia.awesomeapi.com.br/json/last/USD-BRL",
  "method": "GET",
  "mapping": {
    "valor_dolar": "USDBRL.bid",
    "timestamp": "USDBRL.timestamp"
  },
  "async": false
}
```

---

## 1️⃣4️⃣ CLIMA TEMPO (OpenWeatherMap)

```json
{
  "integration_type": "rest_api",
  "action": "get_weather",
  "endpoint": "https://api.openweathermap.org/data/2.5/weather",
  "method": "GET",
  "payload": {
    "q": "São Paulo,BR",
    "appid": "SUA_CHAVE_OPENWEATHER",
    "units": "metric",
    "lang": "pt_br"
  },
  "mapping": {
    "temperatura": "main.temp",
    "descricao": "weather[0].description"
  },
  "async": false
}
```

---

## 1️⃣5️⃣ CRIAR NOTA FISCAL (NFe.io)

```json
{
  "integration_type": "rest_api",
  "action": "create_invoice",
  "endpoint": "https://api.nfe.io/v1/companies/SUA_EMPRESA/serviceinvoices",
  "method": "POST",
  "payload": {
    "borrower": {
      "federalTaxNumber": "12345678000199",
      "name": "Cliente Exemplo"
    },
    "servicesAmount": 100.00,
    "description": "Serviço prestado"
  },
  "auth": {
    "type": "bearer",
    "token": "SUA_CHAVE_NFE_IO"
  },
  "priority": "critical",
  "async": true,
  "callback_url": "https://seu-sistema.com/nfe-callback"
}
```

---

## 🎯 COMO USAR

### **No Swagger UI:**

1. Acesse: http://localhost:8000/api/documentation

2. Clique no cadeado **Authorize** (topo direito)

3. Cole seu Bearer token

4. Vá em **Integration** > **POST /api/v1/integration/execute**

5. Clique em **Try it out**

6. **Copie e cole** um dos exemplos acima

7. **Troque** os valores necessários:
   - Tokens
   - URLs
   - Dados do payload

8. Clique em **Execute**

9. Veja o resultado! 🚀

---

## 📊 VERIFICAR STATUS

Depois de executar com `async: true`, copie o `integration_id` retornado e use:

**Endpoint:** `GET /api/v1/integration/status/{integration_id}`

**No Swagger:**
1. Vá em **Integration** > **GET /api/v1/integration/status/{id}**
2. Try it out
3. Cole o `integration_id`
4. Execute

---

## 🔄 CALLBACK WEBHOOKS

Para receber notificação quando concluir, configure:

```json
{
  "bidirectional": true,
  "callback_url": "https://seu-sistema.com/callback"
}
```

**Seu sistema receberá:**
```json
{
  "integration_id": "int_123",
  "status": "completed",
  "response": {
    "status_code": 200,
    "data": { ... }
  },
  "mapped_data": { ... }
}
```

---

## ⚡ DICAS

✅ **Teste primeiro** com https://webhook.site ou https://httpbin.org
✅ **Use async: false** para debug (vê resultado na hora)
✅ **Use async: true** para produção (não trava)
✅ **Configure callback_url** para saber quando terminar
✅ **Use mapping** para transformar resposta automaticamente

---

## 🎉 PRONTO!

**Agora você pode integrar qualquer API via Swagger sem programar nada!**

Acesse agora: http://localhost:8000/api/documentation
