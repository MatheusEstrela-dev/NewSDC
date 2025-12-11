# 🔧 RESOLVER - Erro de Autorização ACR Persistente

## 📊 Status Atual - Build #10

### ❌ Erro Ainda Presente:

```
ERROR: (AuthorizationFailed) The client '74596f5b-5c73-4256-9719-b52e7f978985'
with object id '36582784-e2a0-4b8d-980a-13bebee16c56' does not have authorization
to perform action 'Microsoft.ContainerRegistry/registries/read' over scope
'/subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/APIDOVER'

If access was recently granted, please refresh your credentials.
```

**Mensagem Chave:** "If access was recently granted, please **refresh your credentials**"

---

## ✅ Progresso Já Realizado

1. ✅ **Permissão AcrPush adicionada** via Azure Portal
2. ✅ **Permissão verificada** via `az role assignment list`
3. ✅ **ACR_NAME corrigido** para APIDOVER (maiúsculas)
4. ✅ **Commit enviado** e webhook disparado

**Verificação de Permissão:**

```bash
az role assignment list --assignee 74596f5b-5c73-4256-9719-b52e7f978985 --all
```

**Resultado:**
```json
[
  {
    "principalName": "jenkins-sdc-acr",
    "roleDefinitionName": "AcrPush",
    "scope": "/subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/APIDOVER"
  }
]
```

✅ **Permissão está CORRETA!**

---

## 🕐 Problema Identificado: Propagação de Permissões

### Azure RBAC Propagation Delay:

As permissões do Azure podem levar **até 5 minutos** para propagar completamente, especialmente para:

- ✅ Azure Active Directory
- ✅ Service Principals
- ✅ Container Registry
- ✅ App Services (Jenkins cache de credenciais)

### Timeline:

| Horário | Evento |
|---------|--------|
| 00:15 | Permissão AcrPush adicionada via Portal |
| 00:16 | Build #9 disparado (1 min após) → ❌ Falhou |
| 00:18 | Jenkinsfile corrigido (APIDOVER) |
| 00:19 | Build #10 disparado (4 min após) → ❌ Falhou |
| **00:21+** | **Aguardar propagação completa** ⏳ |

**Tempo decorrido:** ~4 minutos
**Tempo recomendado:** 5-10 minutos

---

## 🚀 SOLUÇÃO DEFINITIVA

### Opção 1: Aguardar Propagação (Recomendado)

**Tempo:** 3-5 minutos adicionais

**Passo a Passo:**

1. **Aguarde 5 minutos TOTAIS** desde que a permissão foi adicionada
2. **NÃO dispare novos builds** nesse período
3. **Após 5 minutos completos:**
   - Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
   - Clique em **"Build Now"**
   - Aguarde Build #11 executar

**Por que funciona:**
- Azure propaga permissões gradualmente
- Service Principal cache é atualizado
- Jenkins obtém novas credenciais no próximo login

---

### Opção 2: Forçar Refresh de Credenciais (Mais Rápido)

**Tempo:** 2-3 minutos

**Executar no terminal:**

```bash
# 1. Reiniciar Jenkins App Service para limpar cache de credenciais
az webapp restart \
  --name jenkinssdc \
  --resource-group DEFESA_CIVIL

# Aguardar 2 minutos para Jenkins reiniciar completamente

# 2. Disparar novo build
# Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
# Clique "Build Now"
```

**Por que funciona:**
- Reiniciar o Jenkins limpa cache do Azure CLI
- Força novo login do Service Principal
- Obtém token OAuth fresh com novas permissões

---

### Opção 3: Usar Contributor Role (Backup)

**Se ainda falhar após Opção 1 e 2:**

```bash
# Adicionar role mais permissiva
az role assignment create \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --role Contributor \
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/APIDOVER
```

**Diferença:**
- **AcrPush:** Push + Build (menos permissivo)
- **Contributor:** Acesso total ao ACR (mais permissivo)

**Nota:** Contributor pode propagar mais rápido em alguns casos

---

## 📋 Executar AGORA

### Escolha Uma Opção:

#### A) Aguardar (Sem comandos)
```
⏳ Aguarde 3-5 minutos adicionais
✅ Então execute Build #11 manualmente no Jenkins
```

#### B) Forçar Restart (Mais Rápido)
```bash
az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
```
```
⏳ Aguarde 2 minutos
✅ Execute Build #11 manualmente no Jenkins
```

#### C) Contributor Role (Backup)
```bash
az role assignment create \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --role Contributor \
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/APIDOVER
```
```
⏳ Aguarde 30 segundos
✅ Execute Build #11
```

---

## 🔍 Verificar Resultado do Build #11

### Console Output Esperado:

```
[Pipeline] stage { (Build and Push to ACR)
🏗️  Building Docker images using Azure Container Registry...

+ az login --service-principal --username $AZURE_CLIENT_ID ...
[
  {
    "cloudName": "AzureCloud",
    "id": "ef65818a-5356-4772-b849-0c793a23ec87",
    "state": "Enabled",
    "tenantId": "14cbd5a7-ec94-46ba-b314-cc0fc972a161"
  }
]
✅ Login successful

+ az acr build --registry APIDOVER --resource-group DOVER ...
Packing source code into tar to upload...
Uploading archived source code from '/tmp/build_archive_xxx.tar.gz'...
Sending context (XX.X MB) to registry: APIDOVER...
Queued a build with ID: ca1
Waiting for an agent...

2025/12/09 23:25:00 Downloading source code...
2025/12/09 23:25:05 Successfully downloaded source code
2025/12/09 23:25:10 Running: docker build -f docker/Dockerfile.prod .

Step 1/20 : FROM php:8.2-fpm
 ---> Pulling from library/php
...
Step 20/20 : CMD ["php-fpm"]
 ---> Running in xxx
 ---> Successfully built 5f6a3b8c9d2e

Successfully tagged apidover.azurecr.io/sdc-dev-app:11-d9b39ca
Successfully tagged apidover.azurecr.io/sdc-dev-app:latest

2025/12/09 23:30:15 Successfully pushed image: apidover.azurecr.io/sdc-dev-app:11-d9b39ca
2025/12/09 23:30:20 Successfully pushed image: apidover.azurecr.io/sdc-dev-app:latest

Run ID: ca1 was successful after 5m15s
✅ Imagem buildada e enviada para ACR:
   - apidover.azurecr.io/sdc-dev-app:11-d9b39ca
   - apidover.azurecr.io/sdc-dev-app:latest

[Pipeline] stage { (Deploy to Azure App Service)
🚀 Deploying to Azure App Service AUTOMATICALLY...
Updating App Service: newsdc2027
Restarting App Service...
Verificando saúde da aplicação em https://newsdc2027.azurewebsites.net...
✅ App Service está respondendo!

✅ Deploy para Azure App Service concluído!
🌐 URL: https://newsdc2027.azurewebsites.net

Finished: SUCCESS
```

---

## ⚠️ Se AINDA Falhar Após Build #11

### Diagnóstico Avançado:

```bash
# 1. Verificar todas as permissões do Service Principal
az role assignment list \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --all \
  --output table

# 2. Verificar se Service Principal está ativo
az ad sp show --id 74596f5b-5c73-4256-9719-b52e7f978985

# 3. Testar acesso direto ao ACR
az acr login --name APIDOVER

# 4. Verificar credenciais do Service Principal no Jenkins
# Acesse: https://jenkinssdc.azurewebsites.net/manage/credentials/
# Verifique se "azure-service-principal" existe e está válido
```

### Alternativa: Usar Admin User do ACR

**Se Service Principal continuar falhando:**

```bash
# Habilitar Admin User no ACR
az acr update --name APIDOVER --admin-enabled true

# Obter credenciais
az acr credential show --name APIDOVER --resource-group DOVER

# Adicionar no Jenkins:
# Manage Jenkins → Credentials → Add Credentials
# Kind: Username with password
# ID: azure-acr-admin
# Username: apidover
# Password: <copiar da saída acima>
```

**Modificar Jenkinsfile:**
```groovy
// Trocar de Service Principal para Admin User
withCredentials([usernamePassword(
    credentialsId: 'azure-acr-admin',  // ← Mudar aqui
    usernameVariable: 'ACR_USERNAME',
    passwordVariable: 'ACR_PASSWORD'
)]) {
    sh """
        az acr login \
            --name APIDOVER \
            --username \$ACR_USERNAME \
            --password \$ACR_PASSWORD
    """
}
```

---

## 🎯 Recomendação Final

### Executar AGORA:

```bash
# Opção B - Restart Jenkins (Mais Confiável)
az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
```

**Aguardar 2 minutos**, então:

1. Acessar: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. Clicar **"Build Now"**
3. Aguardar Build #11 completar (10-25 minutos)
4. Verificar aplicação: https://newsdc2027.azurewebsites.net/login

---

## 📊 Checklist

- [x] Permissão AcrPush adicionada no Azure Portal
- [x] Permissão verificada via Azure CLI
- [x] ACR_NAME corrigido para APIDOVER
- [ ] **Restart do Jenkins executado** ← FAZER AGORA
- [ ] Aguardar 2 minutos
- [ ] Build #11 disparado manualmente
- [ ] Console Output mostra "Successfully pushed image"
- [ ] Deploy completa com sucesso
- [ ] Produção acessível

---

**Status:** 🟡 **Permissão correta, aguardando propagação completa**

**Próximo passo:** Restart do Jenkins + Build #11 manual!

**Comando:**
```bash
az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
```
