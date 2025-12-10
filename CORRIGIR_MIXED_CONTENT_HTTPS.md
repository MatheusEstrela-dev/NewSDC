# 🔒 Corrigir Mixed Content e Forçar HTTPS

## 🔴 Problema Identificado

A aplicação está apresentando erros de **Mixed Content**:

- ❌ Página carregada via **HTTP**: `http://newsdc2027.azurewebsites.net`
- ✅ Assets carregados via **HTTPS**: `https://newsdc2027.azurewebsites.net/build/js/...`
- ❌ **Erro CORS**: Scripts bloqueados pelo navegador
- ❌ **Erro de Rede**: Requisições AJAX falhando

### Erros no Console:

```
Access to script at 'https://newsdc2027.azurewebsites.net/build/js/app-B7COwmiV.js' 
from origin 'http://newsdc2027.azurewebsites.net' has been blocked by CORS policy

Mixed Content: The page at 'https://newsdc2027.azurewebsites.net/dashboard' 
was loaded over HTTPS, but requested an insecure XMLHttpRequest endpoint 
'http://newsdc2027.azurewebsites.net/logout'
```

## ✅ Correções Aplicadas

### 1. TrustProxies Configurado

**Arquivo**: `SDC/app/Http/Middleware/TrustProxies.php`

```php
protected $proxies = '*';  // Confiar em todos os proxies (Azure App Service)
```

**Por quê**: O Azure App Service usa proxies, então precisamos confiar nos headers `X-Forwarded-*`.

### 2. Middleware ForceHttps Criado

**Arquivo**: `SDC/app/Http/Middleware/ForceHttps.php`

Redireciona automaticamente HTTP → HTTPS em produção.

### 3. AppServiceProvider Configurado

**Arquivo**: `SDC/app/Providers/AppServiceProvider.php`

```php
public function boot(): void
{
    // Forçar HTTPS em produção (Azure App Service)
    if (app()->environment('production')) {
        \URL::forceScheme('https');
    }
}
```

**Por quê**: Garante que todas as URLs geradas pelo Laravel usem HTTPS.

### 4. Entrypoint Corrigido

**Arquivo**: `SDC/docker/scripts/entrypoint.prod.sh`

```bash
APP_URL=${APP_URL:-https://newsdc2027.azurewebsites.net}
```

**Por quê**: Define APP_URL correto por padrão.

### 5. Middleware Adicionado ao Kernel

**Arquivo**: `SDC/app/Http/Kernel.php`

```php
protected $middleware = [
    \App\Http\Middleware\TrustProxies::class,
    \App\Http\Middleware\ForceHttps::class,  // ← Novo
    // ...
];
```

## 🎯 Como Funciona

1. **Requisição HTTP chega** → `http://newsdc2027.azurewebsites.net`
2. **ForceHttps middleware** → Redireciona para HTTPS
3. **TrustProxies** → Confia nos headers do Azure
4. **AppServiceProvider** → Força todas as URLs para HTTPS
5. **Assets carregam via HTTPS** → Sem Mixed Content ✅

## 📋 Verificação no Azure

Após o deploy, verifique se a variável `APP_URL` está configurada:

```bash
az webapp config appsettings list \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --query "[?name=='APP_URL'].{name:name, value:value}" -o table
```

Se não estiver, configure:

```bash
az webapp config appsettings set \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --settings "APP_URL=https://newsdc2027.azurewebsites.net"
```

## ✅ Resultado Esperado

Após o deploy:

1. ✅ Todas as requisições HTTP redirecionam para HTTPS
2. ✅ Assets carregam via HTTPS (sem CORS)
3. ✅ Requisições AJAX funcionam corretamente
4. ✅ Sem erros de Mixed Content
5. ✅ Rotas funcionando normalmente

## 🧪 Teste

Após o deploy, teste:

1. Acesse: `http://newsdc2027.azurewebsites.net` (deve redirecionar para HTTPS)
2. Verifique console do navegador (não deve ter erros de CORS)
3. Teste logout, navegação, etc.

---

**Data**: 10/12/2025  
**Status**: Correções aplicadas - aguardando deploy

