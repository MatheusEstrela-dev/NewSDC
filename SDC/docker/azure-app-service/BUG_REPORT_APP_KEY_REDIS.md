# 🐛 Bug Report - App Service: APP_KEY e Redis

## 📋 Resumo

A aplicação está rodando no Azure App Service, mas apresenta dois erros críticos que impedem o funcionamento correto:

1. **APP_KEY não configurada** - Laravel requer chave de criptografia
2. **Redis não conectado** - Falha ao conectar ao Redis para logs/cache

## 🔍 Sintomas

### Logs do App Service

```
2025-12-09T00:06:27.0897187Z   production.ERROR: No application encryption key has been specified.
{"exception":"[object] (Illuminate\\Encryption\\MissingAppKeyException(code: 0):
No application encryption key has been specified.
at /var/www/vendor/laravel/framework/src/Illuminate/Encryption/EncryptionServiceProvider.php:83)

2025-12-09T00:06:27.0896821Z   production.ERROR: Failed to log to Redis
{"error":"Connection refused"}
```

### Status da Aplicação

-   ✅ **Servidor rodando**: `Server running on [http://0.0.0.0:8000]`
-   ❌ **APP_KEY ausente**: Erro ao processar requisições
-   ❌ **Redis desconectado**: Logs e cache não funcionam

## 🔴 Problema 1: APP_KEY não configurada

### Causa

O Laravel requer uma chave de criptografia (`APP_KEY`) para:

-   Criptografar sessões
-   Criptografar cookies
-   Criptografar dados sensíveis
-   Funcionamento geral da aplicação

### Impacto

-   ❌ Aplicação não processa requisições corretamente
-   ❌ Sessões não funcionam
-   ❌ Cookies não são criptografados
-   ❌ Erro 500 em todas as requisições

### Solução

#### Opção 1: Configurar via Azure Portal

1. Acesse: [Azure Portal](https://portal.azure.com)
2. Navegue até: **App Services** → **newsdc2027** → **Configuration** → **Application settings**
3. Adicione:
    ```
    APP_KEY = base64:SUA_CHAVE_AQUI
    ```
4. Clique em **Save** e reinicie o App Service

#### Opção 2: Via Azure CLI

```powershell
# Gerar APP_KEY
$bytes = New-Object byte[] 32
[System.Security.Cryptography.RandomNumberGenerator]::Fill($bytes)
$appKey = "base64:" + [Convert]::ToBase64String($bytes)

# Configurar no App Service
az webapp config appsettings set `
    --name newsdc2027 `
    --resource-group DEFESA_CIVIL `
    --settings "APP_KEY=$appKey"

# Reiniciar
az webapp restart --name newsdc2027 --resource-group DEFESA_CIVIL
```

#### Opção 3: Usar Script Automatizado

```powershell
cd SDC/docker/azure-app-service
.\configurar-variaveis-ambiente.ps1
```

## 🔴 Problema 2: Redis não conectado

### Causa

A aplicação está configurada para usar Redis como:

-   Driver de cache (`CACHE_DRIVER=redis`)
-   Driver de sessão (`SESSION_DRIVER=redis`)
-   Driver de fila (`QUEUE_CONNECTION=redis`)
-   Driver de logs (`LOG_CHANNEL` com Redis)

Mas não há um serviço Redis disponível no App Service.

### Impacto

-   ❌ Cache não funciona
-   ❌ Sessões não funcionam
-   ❌ Filas não funcionam
-   ❌ Logs falham ao escrever no Redis

### Solução

#### Opção 1: Usar File Driver (Temporário)

Configurar variáveis de ambiente para usar arquivos:

```powershell
az webapp config appsettings set `
    --name newsdc2027 `
    --resource-group DEFESA_CIVIL `
    --settings `
        "CACHE_DRIVER=file" `
        "SESSION_DRIVER=file" `
        "QUEUE_CONNECTION=sync" `
        "LOG_CHANNEL=stack"
```

#### Opção 2: Configurar Azure Cache for Redis

1. Criar Azure Cache for Redis:

    ```powershell
    az redis create `
        --name sdc-redis `
        --resource-group DEFESA_CIVIL `
        --location brazilsouth `
        --sku Basic `
        --vm-size c0
    ```

2. Obter chave de acesso:

    ```powershell
    az redis list-keys --name sdc-redis --resource-group DEFESA_CIVIL
    ```

3. Configurar no App Service:
    ```powershell
    az webapp config appsettings set `
        --name newsdc2027 `
        --resource-group DEFESA_CIVIL `
        --settings `
            "REDIS_HOST=sdc-redis.redis.cache.windows.net" `
            "REDIS_PORT=6380" `
            "REDIS_PASSWORD=SUA_CHAVE_AQUI" `
            "CACHE_DRIVER=redis" `
            "SESSION_DRIVER=redis"
    ```

## 🛠️ Solução Rápida (Recomendada)

Execute o script para corrigir tudo automaticamente:

### Windows (PowerShell)

```powershell
cd SDC/docker/azure-app-service
.\corrigir-app-key.ps1
```

### Linux/Mac (Bash)

```bash
cd SDC/docker/azure-app-service
./corrigir-app-key.sh
```

Este script:

1. ✅ Gera APP_KEY automaticamente
2. ✅ Configura variáveis essenciais do Laravel
3. ✅ Desabilita Redis (usa file driver)
4. ✅ Reinicia o App Service

## 🛠️ Solução Completa (Script Alternativo)

Execute o script alternativo para configurar tudo automaticamente:

```powershell
cd SDC/docker/azure-app-service
.\configurar-variaveis-ambiente.ps1
```

Este script:

1. ✅ Gera APP_KEY automaticamente
2. ✅ Configura variáveis essenciais do Laravel
3. ✅ Desabilita Redis (usa file driver)
4. ✅ Reinicia o App Service

## 📝 Variáveis de Ambiente Necessárias

### Obrigatórias

```env
APP_NAME=SDC
APP_ENV=production
APP_KEY=base64:...  # ⚠️ OBRIGATÓRIO
APP_DEBUG=false
APP_URL=https://newsdc2027.azurewebsites.net
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### Database (Configurar conforme seu banco)

```env
DB_CONNECTION=mysql
DB_HOST=seu-banco.mysql.database.azure.com
DB_PORT=3306
DB_DATABASE=sdc
DB_USERNAME=seu-usuario
DB_PASSWORD=sua-senha
```

### Cache/Session (Sem Redis)

```env
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### Cache/Session (Com Redis)

```env
REDIS_HOST=seu-redis.redis.cache.windows.net
REDIS_PORT=6380
REDIS_PASSWORD=sua-chave-redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## ✅ Checklist de Correção

-   [ ] APP_KEY configurada no App Service
-   [ ] APP_ENV configurado como `production`
-   [ ] APP_DEBUG configurado como `false`
-   [ ] APP_URL configurado corretamente
-   [ ] Database configurado (se aplicável)
-   [ ] Cache/Session configurado (file ou redis)
-   [ ] App Service reiniciado após mudanças
-   [ ] Testar aplicação após correções

## 🔍 Verificar Correções

### 1. Verificar variáveis configuradas

```powershell
az webapp config appsettings list `
    --name newsdc2027 `
    --resource-group DEFESA_CIVIL `
    --query "[?name=='APP_KEY' || name=='CACHE_DRIVER' || name=='SESSION_DRIVER']"
```

### 2. Verificar logs após correção

```powershell
az webapp log tail `
    --name newsdc2027 `
    --resource-group DEFESA_CIVIL
```

### 3. Testar aplicação

```powershell
# Testar health endpoint
Invoke-WebRequest -Uri "https://newsdc2027.azurewebsites.net/health" -UseBasicParsing

# Testar página principal
Invoke-WebRequest -Uri "https://newsdc2027.azurewebsites.net" -UseBasicParsing
```

## 📊 Status Atual

| Item             | Status | Observação                      |
| ---------------- | ------ | ------------------------------- |
| Servidor rodando | ✅     | `php artisan serve` funcionando |
| APP_KEY          | ❌     | **NÃO CONFIGURADA**             |
| Redis            | ❌     | **NÃO CONECTADO**               |
| Cache            | ❌     | Falha por Redis                 |
| Sessões          | ❌     | Falha por Redis                 |
| Requisições      | ❌     | Erro 500 por APP_KEY            |

## 🎯 Próximos Passos

1. **Imediato**: Configurar APP_KEY
2. **Imediato**: Configurar CACHE_DRIVER=file (temporário)
3. **Futuro**: Configurar Azure Cache for Redis (opcional)
4. **Futuro**: Configurar banco de dados (se necessário)

## 📚 Referências

-   [Laravel - Configuration](https://laravel.com/docs/configuration)
-   [Laravel - Encryption](https://laravel.com/docs/encryption)
-   [Azure App Service - Environment Variables](https://docs.microsoft.com/azure/app-service/configure-common)
-   [Azure Cache for Redis](https://docs.microsoft.com/azure/azure-cache-for-redis/)

## 🔗 Arquivos Relacionados

-   `SDC/docker/azure-app-service/configurar-variaveis-ambiente.ps1` - Script de configuração
-   `SDC/docker/Dockerfile.prod` - Dockerfile de produção
-   `SDC/.env.example` - Exemplo de variáveis de ambiente

---

**Data do Bug**: 2025-12-09  
**Ambiente**: Azure App Service (newsdc2027)  
**Severidade**: 🔴 Crítica - Aplicação não funcional
