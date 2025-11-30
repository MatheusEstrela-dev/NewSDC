# Saloon - Integrações com APIs Externas

## 📋 Visão Geral

Este projeto utiliza **Saloon** (Padrão Ouro para Integrações) para gerenciar integrações com múltiplas APIs externas. O sistema centraliza a autenticação e geração de tokens, especialmente para uso com **Power BI**.

## 🎯 Funcionalidades

- ✅ Gerenciamento centralizado de tokens para múltiplas APIs
- ✅ Geração de token único para Power BI acessar todas as APIs
- ✅ Cache inteligente de tokens
- ✅ Suporte a múltiplas APIs (PAE, RAT, TDAP, BI)
- ✅ Documentação Swagger completa

## 🚀 Endpoint Principal para Power BI

### Gerar Token Único

```http
POST /api/v1/power-bi/token
Authorization: Bearer {seu_token_sanctum}
Content-Type: application/json

{
  "apis": ["pae", "rat", "tdap", "bi"],  // Opcional: APIs específicas
  "refresh": false  // Opcional: Força renovação de tokens
}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "token": "a1b2c3d4e5f6...",
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
      }
    }
  }
}
```

## 📚 Endpoints Disponíveis

### 1. Gerar Token Power BI
- **POST** `/api/v1/power-bi/token`
- Gera um token único que permite acesso a múltiplas APIs

### 2. Validar Token Power BI
- **GET** `/api/v1/power-bi/token/{token}`
- Valida um token e retorna os tokens individuais

### 3. Listar Tokens Individuais
- **GET** `/api/v1/power-bi/tokens?apis=pae,rat,bi`
- Retorna tokens individuais para cada API

## ⚙️ Configuração

### 1. Variáveis de Ambiente

Adicione as seguintes variáveis no arquivo `.env`:

```env
# API PAE
API_PAE_BASE_URL=https://api-pae.sdc.mg.gov.br
API_PAE_CLIENT_ID=seu_client_id
API_PAE_CLIENT_SECRET=seu_client_secret

# API RAT
API_RAT_BASE_URL=https://api-rat.sdc.mg.gov.br
API_RAT_CLIENT_ID=seu_client_id
API_RAT_CLIENT_SECRET=seu_client_secret

# API TDAP
API_TDAP_BASE_URL=https://api-tdap.sdc.mg.gov.br
API_TDAP_CLIENT_ID=seu_client_id
API_TDAP_CLIENT_SECRET=seu_client_secret

# API Business Intelligence
API_BI_BASE_URL=https://sdc.mg.gov.br
API_BI_CLIENT_ID=seu_client_id
API_BI_CLIENT_SECRET=seu_client_secret

# Power BI
POWER_BI_ENABLED=true
POWER_BI_TOKEN_TTL=3600

# Cache
TOKEN_CACHE_ENABLED=true
TOKEN_CACHE_TTL=3300
```

### 2. Arquivo de Configuração

O arquivo `config/integrations.php` contém todas as configurações das APIs.

## 🔧 Estrutura do Projeto

```
app/
├── Integrations/
│   ├── BaseConnector.php          # Connector base Saloon
│   ├── Auth/
│   │   └── BearerTokenAuthenticator.php
│   └── Requests/
│       └── GetTokenRequest.php
├── Services/
│   └── IntegrationTokenService.php  # Serviço principal
└── Http/
    └── Controllers/
        └── Api/
            └── V1/
                └── PowerBI/
                    └── TokenController.php

config/
└── integrations.php                # Configuração das APIs
```

## 💡 Como Usar no Power BI

### Passo 1: Obter Token

1. Faça login na API SDC
2. Obtenha um token Sanctum
3. Chame o endpoint `/api/v1/power-bi/token`

### Passo 2: Usar no Power BI

O token retornado pode ser usado no Power BI para acessar todas as APIs configuradas. O Power BI pode usar este token único em vez de gerenciar múltiplos tokens.

### Exemplo de Uso

```javascript
// No Power BI, use o token único
const powerBIToken = "a1b2c3d4e5f6...";

// Para acessar dados da API PAE
fetch('https://api-pae.sdc.mg.gov.br/api/v1/empreendimentos', {
  headers: {
    'Authorization': `Bearer ${powerBIToken}`,
    'X-PowerBI-Token': powerBIToken
  }
});
```

## 🔐 Segurança

- Todos os tokens são armazenados em cache com TTL configurável
- Tokens do Power BI expiram automaticamente após 1 hora (configurável)
- Requer autenticação Sanctum para gerar tokens
- Tokens individuais são gerados sob demanda

## 📖 Documentação Swagger

Acesse a documentação completa em:
- **URL**: `http://localhost/api/documentation`
- **Tag**: Power BI

## 🛠️ Desenvolvimento

### Adicionar Nova API

1. Adicione a configuração em `config/integrations.php`:

```php
'minha_api' => [
    'name' => 'Minha API',
    'base_url' => env('API_MINHA_API_BASE_URL'),
    'auth_type' => 'bearer',
    'token_endpoint' => '/api/auth/token',
    'credentials' => [
        'client_id' => env('API_MINHA_API_CLIENT_ID'),
        'client_secret' => env('API_MINHA_API_CLIENT_SECRET'),
    ],
    'scopes' => ['read'],
],
```

2. Adicione as variáveis de ambiente no `.env`

3. A API estará automaticamente disponível no endpoint Power BI!

## 📝 Notas

- O sistema utiliza **Saloon v3** (Padrão Ouro para Integrações PHP)
- Tokens são cacheados para melhor performance
- Suporte a múltiplos tipos de autenticação (Bearer, Basic, OAuth2)
- Fácil extensão para novas APIs

