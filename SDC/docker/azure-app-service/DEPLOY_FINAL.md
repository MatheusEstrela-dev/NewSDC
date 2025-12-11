# 🚀 Deploy Final - SDC para Azure App Service

## ✅ Status Atual

- ✅ Imagem Docker buildada: `sdc-dev-app:latest`
- ✅ Imagem no ACR: `apidover.azurecr.io/sdc-dev-app:latest`
- ✅ App Service: `newsdc2027`
- ✅ Resource Group: `DEFESA_CIVIL`
- ✅ Jenkinsfile atualizado com valores corretos

## 🎯 Deploy Rápido

### Opção 1: Script Automático (Recomendado)

**Windows (PowerShell):**
```powershell
cd SDC/docker/azure-app-service
.\deploy-rapido.ps1
```

**Linux/Mac:**
```bash
cd SDC/docker/azure-app-service
chmod +x deploy-rapido.sh
./deploy-rapido.sh
```

### Opção 2: Comandos Manuais

```bash
# 1. Login no Azure
az login

# 2. Login no ACR
az acr login --name apidover

# 3. Tag e Push (se necessário)
docker tag sdc-dev-app:latest apidover.azurecr.io/sdc-dev-app:latest
docker push apidover.azurecr.io/sdc-dev-app:latest

# 4. Atualizar App Service
az webapp config container set \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL \
    --docker-custom-image-name apidover.azurecr.io/sdc-dev-app:latest

# 5. Reiniciar App Service
az webapp restart \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL

# 6. Verificar status
az webapp show \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL \
    --query state

# 7. Ver logs
az webapp log tail \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL
```

## 🔍 Verificar Deploy

### Testar URL
```bash
curl https://newsdc2027.azurewebsites.net/health
```

### Ver logs em tempo real
```bash
az webapp log tail \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL \
    --follow
```

### Verificar configuração atual
```bash
az webapp config container show \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL
```

## 🔄 CI/CD Automático

O Jenkinsfile está configurado para fazer deploy automático quando você fizer push na branch `main` ou `master`.

### Variáveis no Jenkins

Configure no Jenkins (Manage Jenkins → Configure System → Global properties):

```bash
AZURE_APP_SERVICE_NAME=newsdc2027
AZURE_RESOURCE_GROUP=DEFESA_CIVIL
ACR_NAME=apidover
AZURE_CLIENT_ID=seu-client-id
AZURE_CLIENT_SECRET=seu-client-secret
AZURE_TENANT_ID=seu-tenant-id
```

## 📋 Checklist Final

- [ ] Imagem buildada localmente
- [ ] Imagem no ACR (`apidover.azurecr.io/sdc-dev-app:latest`)
- [ ] App Service atualizado
- [ ] App Service reiniciado
- [ ] Health check passando
- [ ] URL acessível: `https://newsdc2027.azurewebsites.net`
- [ ] CI/CD configurado no Jenkins
- [ ] Webhook do GitHub configurado

## 🐛 Troubleshooting

### Erro 503 - Service Unavailable

```bash
# Ver logs detalhados
az webapp log tail --name newsdc2027 --resource-group DEFESA_CIVIL

# Verificar configuração
az webapp config show --name newsdc2027 --resource-group DEFESA_CIVIL

# Verificar variáveis de ambiente
az webapp config appsettings list \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL
```

### Imagem não atualiza

```bash
# Forçar pull da imagem
az webapp restart --name newsdc2027 --resource-group DEFESA_CIVIL

# Verificar imagem atual
az webapp config container show \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL \
    --query dockerCfgImage
```

### Verificar se container está rodando

```bash
# Ver métricas
az monitor metrics list \
    --resource /subscriptions/{sub-id}/resourceGroups/DEFESA_CIVIL/providers/Microsoft.Web/sites/newsdc2027 \
    --metric "Http2xx,Http5xx"
```

## 🎉 Pronto!

Após executar o deploy, sua aplicação estará disponível em:

**🌐 https://newsdc2027.azurewebsites.net**

E o CI/CD estará configurado para fazer deploy automático a cada push! 🚀




