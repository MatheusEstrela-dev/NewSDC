# 🔧 Solução: Executar Comandos no Azure App Service com Docker

## ❌ Problema

No Azure App Service com containers Docker, o SSH do Kudu **não tem acesso ao Docker daemon**. Por isso:
- `docker ps` → "command not found"
- `docker exec` → não funciona
- `php artisan` → não funciona (PHP está dentro do container)

## ✅ Solução: Entrypoint Automático

A melhor solução é fazer o container executar os comandos **automaticamente na inicialização**.

### O que foi feito:

Atualizei o `entrypoint.prod.sh` para:
1. ✅ Executar migrations automaticamente
2. ✅ **Sempre verificar/corrigir o usuário de teste** usando `php artisan app:create-test-user --fix`
3. ✅ Se o comando não existir, usar método alternativo (seeders)

### Como funciona:

Toda vez que o container inicia, ele:
1. Verifica se migrations foram executadas
2. Executa migrations se necessário
3. **Executa `app:create-test-user --fix`** para garantir que o usuário existe e está correto
4. Inicia o servidor Laravel

## 🚀 Próximos Passos

### 1. Fazer Deploy da Correção

O entrypoint já foi atualizado. No próximo deploy, o usuário será criado/corrigido automaticamente.

### 2. Forçar Reinicialização (Opcional)

Se quiser aplicar agora sem esperar o próximo deploy:

```bash
# Reiniciar o App Service (vai executar o entrypoint novamente)
az webapp restart --name newsdc2027 --resource-group DEFESA_CIVIL
```

Aguarde 2-3 minutos e o usuário será criado/corrigido automaticamente.

### 3. Verificar Logs

```bash
# Ver logs do container para confirmar
az webapp log tail --name newsdc2027 --resource-group DEFESA_CIVIL
```

Procure por:
```
✅ Usuário de teste verificado/corrigido
```

## 📋 Alternativas (Se Necessário)

### Opção 1: Criar Rota HTTP para Executar Comandos

Criar uma rota protegida que execute comandos Artisan via HTTP:

```php
// routes/web.php (apenas para emergências!)
Route::post('/admin/run-command', function() {
    // Proteger com autenticação/token
    $command = request('command');
    Artisan::call($command);
    return response()->json(['output' => Artisan::output()]);
})->middleware('auth');
```

### Opção 2: Usar Azure CLI (Se Disponível)

Alguns planos do Azure App Service permitem executar comandos via Azure CLI:

```bash
az webapp command run --name newsdc2027 --resource-group DEFESA_CIVIL --command "php artisan app:create-test-user --fix"
```

**Nota**: Isso pode não estar disponível em todos os planos.

### Opção 3: Modificar Entrypoint para Executar em Background

Se precisar executar comandos periodicamente, pode criar um script que roda em background:

```bash
# No entrypoint.prod.sh
# Executar comando em background após iniciar servidor
(php artisan app:create-test-user --fix &)
```

## 🎯 Solução Recomendada

**Use o entrypoint automático** (já implementado). É a forma mais confiável e não requer acesso SSH ao container.

## ✅ Verificação

Após o próximo deploy ou reinicialização:

1. Acesse: https://newsdc2027.azurewebsites.net/login
2. Tente fazer login com:
   - CPF: `12345678900`
   - Senha: `password`

Se funcionar, o entrypoint automático está funcionando! ✅

---

**Data**: 10/12/2025  
**Status**: Entrypoint atualizado - aguardando próximo deploy ou reinicialização


