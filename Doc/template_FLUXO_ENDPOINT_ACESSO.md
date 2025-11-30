# 🔄 Como Funciona o Endpoint de Acesso - Resumo Visual

## 📊 Fluxo Completo em 3 Passos

### Passo 1: Obter Token Power BI (Uma vez)

```
Power BI → POST /api/v1/power-bi/token
          Authorization: Bearer {token_sanctum}
          
          ↓
          
SDC API → Gera token único
          Obtém tokens de todas as APIs (PAE, RAT, TDAP, BI)
          Armazena mapeamento no cache
          
          ↓
          
Power BI ← Recebe: { token: "abc123...", expires_in: 3600 }
```

### Passo 2: Usar Token para Acessar Dados (Múltiplas vezes)

#### Opção A: Via Proxy (✅ Mais Simples)

```
Power BI → GET /api/v1/power-bi/proxy/pae/api/v1/empreendimentos
          X-PowerBI-Token: abc123...
          
          ↓
          
SDC API → Valida token Power BI
          Busca token PAE no cache
          Faz requisição para API PAE externa
          
          ↓
          
API PAE → Retorna dados
          
          ↓
          
SDC API → Retorna dados para Power BI
          
          ↓
          
Power BI ← Recebe dados dos empreendimentos
```

#### Opção B: Resolver Token Manualmente

```
Power BI → GET /api/v1/power-bi/token/abc123...
          Authorization: Bearer {token_sanctum}
          
          ↓
          
SDC API → Valida token Power BI
          Retorna tokens individuais
          
          ↓
          
Power BI ← Recebe: {
                     pae: { token: "xyz...", base_url: "..." },
                     rat: { token: "def...", base_url: "..." }
                   }
          
          ↓
          
Power BI → GET https://api-pae.sdc.mg.gov.br/api/v1/empreendimentos
          Authorization: Bearer xyz...
          
          ↓
          
API PAE → Retorna dados
          
          ↓
          
Power BI ← Recebe dados
```

## 🎯 Exemplo Prático Completo

### Cenário: Power BI precisa buscar empreendimentos PAE

#### 1️⃣ Gerar Token Power BI (Uma vez por hora)

```http
POST http://localhost/api/v1/power-bi/token
Authorization: Bearer {seu_token_sanctum}
Content-Type: application/json

{
  "apis": ["pae", "rat", "tdap", "bi"]
}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "token": "a1b2c3d4e5f6789...",
    "expires_in": 3600,
    "apis": ["pae", "rat", "tdap", "bi"]
  }
}
```

#### 2️⃣ Usar Proxy para Acessar Dados (Múltiplas vezes)

```http
GET http://localhost/api/v1/power-bi/proxy/pae/api/v1/empreendimentos?page=1&per_page=50
X-PowerBI-Token: a1b2c3d4e5f6789...
```

**O que acontece internamente:**

1. ✅ Sistema valida o token Power BI
2. ✅ Busca token PAE no cache (ou obtém novo se necessário)
3. ✅ Faz requisição para: `https://api-pae.sdc.mg.gov.br/api/v1/empreendimentos?page=1&per_page=50`
4. ✅ Usa token PAE: `Authorization: Bearer {token_pae}`
5. ✅ Retorna resposta da API PAE diretamente para Power BI

**Resposta:**
```json
{
  "data": [
    {
      "id": 1,
      "nome": "Barragem Sul Superior",
      "tipo": "Barragem de Rejeitos",
      ...
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 100
  }
}
```

## 🔐 Segurança e Cache

### Como Funciona o Cache

```
┌─────────────────────────────────────┐
│  Cache de Tokens                    │
├─────────────────────────────────────┤
│                                     │
│  power_bi_token_abc123 → {          │
│    pae: { token: "xyz...", ... },   │
│    rat: { token: "def...", ... }    │
│  }                                  │
│  TTL: 1 hora                        │
│                                     │
│  api_token_pae → "xyz789..."        │
│  TTL: 55 minutos                    │
│                                     │
│  api_token_rat → "def456..."        │
│  TTL: 55 minutos                    │
└─────────────────────────────────────┘
```

### Fluxo de Validação

```
Requisição com Token Power BI
         ↓
    Token válido?
         ↓ SIM
    Token expirado?
         ↓ NÃO
    API solicitada existe?
         ↓ SIM
    Token individual em cache?
         ↓ SIM
    Retorna dados
```

## 📝 Código Power BI (Power Query M)

### Função Simples com Proxy

```m
(powerBIToken as text, api as text, endpoint as text, optional queryParams as record) =>
let
    baseUrl = "http://localhost/api/v1/power-bi/proxy",
    fullUrl = baseUrl & "/" & api & "/" & endpoint,
    
    // Adiciona query parameters se fornecidos
    urlWithParams = if queryParams <> null then
        fullUrl & "?" & Uri.BuildQueryString(queryParams)
    else
        fullUrl,
    
    source = Web.Contents(urlWithParams, [
        Headers = [
            #"X-PowerBI-Token" = powerBIToken
        ]
    ]),
    json = Json.Document(source),
    data = json[data]
in
    data
```

### Uso na Query

```m
let
    powerBIToken = "a1b2c3d4e5f6789...", // Obtido uma vez
    
    // Buscar empreendimentos PAE
    empreendimentos = AccessAPI(
        powerBIToken,
        "pae",
        "api/v1/empreendimentos",
        [page = "1", per_page = "50"]
    ),
    
    // Converter para tabela
    table = Table.FromRecords(empreendimentos)
in
    table
```

## ✅ Vantagens desta Abordagem

1. **Um único token** para o Power BI gerenciar
2. **Proxy transparente** - Power BI não precisa conhecer URLs das APIs externas
3. **Cache inteligente** - Tokens são reutilizados automaticamente
4. **Segurança** - Tokens individuais nunca expostos ao Power BI
5. **Auditoria** - Todas as requisições passam pelo nosso sistema
6. **Flexibilidade** - Fácil adicionar novas APIs

## 🚀 Endpoints Disponíveis

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `/api/v1/power-bi/token` | POST | Gera token único Power BI |
| `/api/v1/power-bi/token/{token}` | GET | Valida e retorna tokens individuais |
| `/api/v1/power-bi/tokens` | GET | Lista tokens individuais |
| `/api/v1/power-bi/proxy/{api}/{path}` | GET/POST/PUT/DELETE | Proxy para APIs externas |

## 📌 Exemplos de Uso do Proxy

### Buscar Empreendimentos PAE
```
GET /api/v1/power-bi/proxy/pae/api/v1/empreendimentos?page=1
X-PowerBI-Token: abc123...
```

### Buscar Protocolos RAT
```
GET /api/v1/power-bi/proxy/rat/api/v1/protocolos?status=em_analise
X-PowerBI-Token: abc123...
```

### Criar Novo Empreendimento
```
POST /api/v1/power-bi/proxy/pae/api/v1/empreendimentos
X-PowerBI-Token: abc123...
Content-Type: application/json

{
  "nome": "Nova Barragem",
  "tipo": "Barragem de Rejeitos",
  ...
}
```

### Buscar Dados BI
```
GET /api/v1/power-bi/proxy/bi/api/bi/entrada-processos
X-PowerBI-Token: abc123...
```

## 🎓 Resumo em 1 Minuto

1. **Power BI obtém token único** → `POST /api/v1/power-bi/token`
2. **Power BI usa proxy** → `GET /api/v1/power-bi/proxy/{api}/{endpoint}`
3. **Sistema resolve tokens automaticamente** → Busca token individual da API
4. **Sistema faz requisição** → Para API externa com token correto
5. **Sistema retorna dados** → Diretamente para Power BI

**Resultado:** Power BI só precisa conhecer uma URL base e um token! 🎉

