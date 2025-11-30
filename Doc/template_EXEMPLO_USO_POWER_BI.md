# Como Funciona o Endpoint de Acesso - Power BI

## 🔄 Fluxo Completo de Funcionamento

### 1. Geração do Token Único para Power BI

O Power BI faz uma requisição para obter um token único que permite acesso a múltiplas APIs:

```http
POST /api/v1/power-bi/token
Authorization: Bearer {token_sanctum_do_usuario}
Content-Type: application/json

{
  "apis": ["pae", "rat", "tdap", "bi"],
  "refresh": false
}
```

**Resposta:**

```json
{
    "success": true,
    "data": {
        "token": "a1b2c3d4e5f6789...",
        "expires_in": 3600,
        "apis": ["pae", "rat", "tdap", "bi"],
        "endpoints": {
            "pae": {
                "url": "https://api-pae.sdc.mg.gov.br",
                "name": "API PAE"
            },
            "rat": {
                "url": "https://api-rat.sdc.mg.gov.br",
                "name": "API RAT"
            },
            "tdap": {
                "url": "https://api-tdap.sdc.mg.gov.br",
                "name": "API TDAP"
            },
            "bi": {
                "url": "https://sdc.mg.gov.br",
                "name": "API Business Intelligence"
            }
        }
    }
}
```

### 2. Como o Power BI Usa o Token

O Power BI armazena o token único e o usa para fazer requisições. Quando precisa acessar uma API específica, faz uma requisição através do nosso sistema:

#### Opção A: Proxy/Gateway (Recomendado)

O Power BI faz requisições através do nosso sistema, que resolve o token:

```http
GET /api/v1/power-bi/proxy/pae/empreendimentos
Authorization: Bearer {token_power_bi}
```

Nosso sistema:

1. Valida o token do Power BI
2. Busca o token individual da API PAE associado
3. Faz a requisição para a API PAE usando o token correto
4. Retorna os dados para o Power BI

#### Opção B: Endpoint de Resolução de Token

O Power BI primeiro resolve o token para obter os tokens individuais:

```http
GET /api/v1/power-bi/token/{token_power_bi}
Authorization: Bearer {token_sanctum}
```

**Resposta:**

```json
{
    "success": true,
    "data": {
        "valid": true,
        "apis": {
            "pae": {
                "token": "token_pae_123...",
                "base_url": "https://api-pae.sdc.mg.gov.br"
            },
            "rat": {
                "token": "token_rat_456...",
                "base_url": "https://api-rat.sdc.mg.gov.br"
            }
        }
    }
}
```

Depois usa os tokens individuais diretamente nas APIs.

## 🎯 Exemplo Prático Completo

### Cenário: Power BI precisa buscar dados de empreendimentos PAE

#### Passo 1: Obter Token Power BI

```javascript
// No Power BI (Power Query M ou JavaScript)
let
    tokenResponse = Web.Contents("http://localhost/api/v1/power-bi/token", [
        Headers = [
            #"Authorization" = "Bearer " & sanctumToken,
            #"Content-Type" = "application/json"
        ],
        Content = Json.FromValue([
            apis = {"pae", "rat", "tdap", "bi"},
            refresh = false
        ])
    ]),
    tokenData = Json.Document(tokenResponse),
    powerBIToken = tokenData[data][token]
in
    powerBIToken
```

#### Passo 2: Usar o Token para Acessar Dados

**Opção 1: Via Proxy (✅ RECOMENDADO - Já Implementado)**

```javascript
let
    source = Web.Contents("http://localhost/api/v1/power-bi/proxy/pae/api/v1/empreendimentos", [
        Headers = [
            #"X-PowerBI-Token" = powerBIToken
        ],
        Query = [
            page = "1",
            per_page = "50"
        ]
    ]),
    json = Json.Document(source),
    data = json[data]
in
    data
```

**Vantagens do Proxy:**

-   ✅ Mais simples: apenas uma URL base
-   ✅ Não precisa resolver tokens manualmente
-   ✅ Funciona com qualquer método HTTP (GET, POST, PUT, DELETE)
-   ✅ Suporta query parameters e body

**Opção 2: Resolver Token e Usar Diretamente**

```javascript
let
    // Resolver token
    tokenInfo = Web.Contents("http://localhost/api/v1/power-bi/token/" & powerBIToken, [
        Headers = [
            #"Authorization" = "Bearer " & sanctumToken
        ]
    ]),
    tokenData = Json.Document(tokenInfo),
    paeToken = tokenData[data][apis][pae][token],
    paeUrl = tokenData[data][apis][pae][base_url],

    // Usar token diretamente na API PAE
    source = Web.Contents(paeUrl & "/api/v1/empreendimentos", [
        Headers = [
            #"Authorization" = "Bearer " & paeToken
        ]
    ]),
    json = Json.Document(source),
    data = json[data]
in
    data
```

## 🔐 Segurança e Cache

### Como Funciona Internamente

1. **Geração do Token Power BI:**

    - Sistema obtém tokens individuais de cada API configurada
    - Gera um hash único (token Power BI)
    - Armazena mapeamento no cache: `power_bi_token_{hash} => {tokens_individuais}`
    - TTL: 1 hora (configurável)

2. **Validação do Token:**

    - Quando Power BI usa o token, sistema busca no cache
    - Se encontrado e válido, retorna tokens individuais
    - Se expirado, retorna erro 404

3. **Cache de Tokens Individuais:**
    - Cada API tem seu token em cache separado
    - TTL: 55 minutos (menor que o token Power BI)
    - Evita múltiplas requisições desnecessárias

## 📊 Arquitetura do Sistema

```
┌─────────────┐
│  Power BI   │
└──────┬──────┘
       │
       │ 1. POST /api/v1/power-bi/token
       │    (com token Sanctum)
       ▼
┌─────────────────────────────┐
│   SDC API Gateway           │
│  (Laravel + Saloon)         │
│                             │
│  ┌───────────────────────┐  │
│  │ IntegrationTokenService│  │
│  │                       │  │
│  │ - Gera token Power BI │  │
│  │ - Cache de tokens     │  │
│  │ - Valida tokens       │  │
│  └───────────────────────┘  │
└──────┬──────────────────────┘
       │
       │ 2. Para cada API configurada:
       │    - Obtém token via Saloon
       │    - Armazena em cache
       │
       ▼
┌─────────────────────────────┐
│   APIs Externas             │
│                             │
│  ┌──────┐  ┌──────┐  ┌────┐│
│  │ PAE  │  │ RAT  │  │ BI ││
│  └──────┘  └──────┘  └────┘│
└─────────────────────────────┘
```

## 🚀 Proxy Implementado (Recomendado)

✅ **Proxy já implementado!** Facilita muito o uso no Power BI:

```http
GET /api/v1/power-bi/proxy/{api}/{path}
X-PowerBI-Token: {token_power_bi}
```

**Exemplo:**

```http
GET /api/v1/power-bi/proxy/pae/api/v1/empreendimentos?page=1
X-PowerBI-Token: a1b2c3d4e5f6...
```

O proxy:

1. ✅ Valida token Power BI automaticamente
2. ✅ Busca token individual da API
3. ✅ Faz requisição para API externa
4. ✅ Retorna resposta diretamente

**Vantagens:**

-   Power BI só precisa conhecer uma URL base
-   Não precisa gerenciar múltiplos tokens
-   Todas as requisições passam pelo nosso sistema (auditoria)
-   Cache automático de tokens

## 📝 Exemplo de Uso no Power BI Desktop

### 1. Criar Função para Obter Token

```m
(powerBIToken as text) =>
let
    tokenInfo = Web.Contents("http://localhost/api/v1/power-bi/token/" & powerBIToken, [
        Headers = [
            #"Authorization" = "Bearer " & sanctumToken
        ]
    ]),
    json = Json.Document(tokenInfo)
in
    json
```

### 2. Criar Função para Acessar API

```m
(apiName as text, endpoint as text, powerBIToken as text) =>
let
    // Resolver token
    tokenInfo = GetPowerBITokenInfo(powerBIToken),
    apiToken = tokenInfo[data][apis][apiName][token],
    apiUrl = tokenInfo[data][apis][apiName][base_url],

    // Fazer requisição
    source = Web.Contents(apiUrl & endpoint, [
        Headers = [
            #"Authorization" = "Bearer " & apiToken
        ]
    ]),
    json = Json.Document(source)
in
    json
```

### 3. Usar nas Queries

```m
let
    powerBIToken = "a1b2c3d4e5f6...",
    source = AccessAPI("pae", "/api/v1/empreendimentos", powerBIToken),
    data = Table.FromRecords(source[data])
in
    data
```

## ✅ Vantagens desta Abordagem

1. **Um único token** para o Power BI gerenciar
2. **Segurança**: Tokens individuais não expostos diretamente
3. **Cache inteligente**: Reduz requisições desnecessárias
4. **Flexibilidade**: Fácil adicionar novas APIs
5. **Auditoria**: Todas as requisições passam pelo nosso sistema
6. **Renovação automática**: Tokens são renovados quando necessário

## 🔧 Configuração Necessária

No Power BI, você precisa:

1. **Token Sanctum**: Para autenticar no nosso sistema
2. **Token Power BI**: Gerado uma vez e reutilizado
3. **Funções M**: Para facilitar o acesso às APIs

O token Power BI pode ser armazenado como parâmetro no Power BI e renovado periodicamente.
