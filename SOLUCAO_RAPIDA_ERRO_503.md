# ⚡ Solução Rápida - Erro 503 no newsdc2027

## 🔴 Problema

**Erro:** Composer.json não encontrado no container
**Causa:** Dockerfile não copia os arquivos da aplicação

## ✅ Solução Rápida (5 minutos)

### Passo 1: Build com Dockerfile Correto

```bash
cd c:\Users\kdes\Documentos\GitHub\New_SDC\SDC

# Build com Dockerfile de produção
docker build -f docker/Dockerfile.prod -t sdc-dev-app:prod .

# Tag para ACR
docker tag sdc-dev-app:prod apidover.azurecr.io/sdc-dev-app:latest

# Login e push
az acr login --name apidover
docker push apidover.azurecr.io/sdc-dev-app:latest
```

### Passo 2: Gerar APP_KEY

```bash
# Gerar nova APP_KEY
APP_KEY=$(docker run --rm php:8.3-cli php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;")

# Configurar no App Service
az webapp config appsettings set \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --settings \
    APP_KEY="$APP_KEY" \
    WEBSITES_PORT="8000" \
    APP_ENV="production" \
    APP_DEBUG="false" \
    DB_CONNECTION="sqlite"
```

### Passo 3: Forçar Pull da Nova Imagem

```bash
# Restart para puxar nova imagem
az webapp restart --name newsdc2027 --resource-group DEFESA_CIVIL

# Aguardar 30 segundos
sleep 30

# Verificar
curl -I https://newsdc2027.azurewebsites.net/
```

---

## 🧪 Testar

```bash
# Ver logs em tempo real
az webapp log tail --name newsdc2027 --resource-group DEFESA_CIVIL

# Aguardar mensagem: "Laravel development server started"

# Testar
curl https://newsdc2027.azurewebsites.net/
```

---

## 📝 O que foi corrigido?

**Dockerfile.prod criado:**
- ✅ Copia código da aplicação (`COPY . .`)
- ✅ Instala dependências (`composer install`)
- ✅ Configura permissões
- ✅ Expõe porta 8000
- ✅ Inicia com `php artisan serve`

---

## 🚀 Próximos Passos

Após o site funcionar:

1. **Configurar Webhook no GitHub:**
   ```
   URL: https://jenkinssdc.azurewebsites.net/github-webhook/
   ```

2. **Configurar Jenkins:**
   - Credencial `azure-service-principal`
   - Variáveis de ambiente

3. **Testar CI/CD:**
   ```bash
   git commit -m "test: CI/CD" --allow-empty
   git push
   ```

---

## 📖 Documentação Completa

- [CORRIGIR_ERRO_COMPOSER_CONTAINER.md](CORRIGIR_ERRO_COMPOSER_CONTAINER.md) - Solução detalhada
- [RESOLVER_503_E_CONFIGURAR_CICD.md](RESOLVER_503_E_CONFIGURAR_CICD.md) - Configuração completa
- [TESTAR_GATILHO_CICD.md](TESTAR_GATILHO_CICD.md) - Como testar o gatilho

---

<div align="center">

**⚡ Solução Rápida - 5 Minutos**

*Build → Push → Restart → Funciona!*

</div>
