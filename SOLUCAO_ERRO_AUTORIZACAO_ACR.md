# 🔧 Solução - Erro de Autorização ACR no Jenkins

## ❌ Problema Identificado

### Erro no Build:
```
ERROR: (AuthorizationFailed) The client '74596f5b-5c73-4256-9719-b52e7f978985' 
with object id '36582784-e2a0-4b8d-980a-13bebee16c56' does not have authorization 
to perform action 'Microsoft.ContainerRegistry/registries/read' over scope 
'/subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/APIDOVER'
```

### Causa Raiz:
O **Service Principal** usado pelo Jenkins não tem permissões para acessar o Azure Container Registry (ACR) `APIDOVER`.

---

## ✅ Solução Rápida

### Opção 1: Script PowerShell (Recomendado para Windows)

Execute o script `adicionar-permissoes-acr.ps1`:

```powershell
# No PowerShell ou Azure Cloud Shell
.\adicionar-permissoes-acr.ps1
```

**Ou execute diretamente os comandos:**

```powershell
# 1. Login no Azure (se necessário)
az login

# 2. Definir subscription
az account set --subscription ef65818a-5356-4772-b849-0c793a23ec87

# 3. Adicionar permissão AcrPush
az role assignment create `
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 `
  --role AcrPush `
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/APIDOVER
```

### Opção 2: Script Bash (Linux/Mac/Cloud Shell)

Execute o script `adicionar-permissoes-acr.sh`:

```bash
chmod +x adicionar-permissoes-acr.sh
./adicionar-permissoes-acr.sh
```

---

## 🔍 Verificar Permissões

Após adicionar as permissões, verifique se foram aplicadas:

```bash
az role assignment list \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/APIDOVER \
  --output table
```

**Resultado esperado:**
```
Principal                            Role      Scope
----------------------------------   --------  ------------------------------------------------------------
74596f5b-5c73-4256-9719-b52e7f978985  AcrPush   /subscriptions/.../registries/APIDOVER
```

---

## ⏱️ Propagação de Permissões

As permissões do Azure podem levar **30 segundos a 5 minutos** para propagar completamente.

### Após adicionar permissões:

1. **Aguarde 1-2 minutos** para propagação
2. **Reinicie o Jenkins** (recomendado para limpar cache):
   ```bash
   az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
   ```
3. **Aguarde mais 1-2 minutos** para Jenkins reiniciar
4. **Execute novo build** no Jenkins:
   - Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
   - Clique em **"Build Now"**

---

## 🚀 Resultado Esperado

Após aplicar a solução, o build deve mostrar:

```
[Pipeline] stage { (Build and Push to ACR)
🏗️  Building Docker images using Azure Container Registry...

+ az login --service-principal ...
✅ Login successful

+ az acr build --registry APIDOVER --resource-group DOVER ...
Packing source code into tar to upload...
Uploading archived source code...
Sending context (XX.X MB) to registry: APIDOVER...
Queued a build with ID: ca1
Waiting for an agent...

2025/12/09 23:25:10 Running: docker build -f docker/Dockerfile.prod .
Step 1/20 : FROM php:8.2-fpm
...
Step 20/20 : CMD ["php-fpm"]
Successfully built 5f6a3b8c9d2e

Successfully tagged apidover.azurecr.io/sdc-dev-app:1-a14d306
Successfully tagged apidover.azurecr.io/sdc-dev-app:latest

2025/12/09 23:30:15 Successfully pushed image: apidover.azurecr.io/sdc-dev-app:1-a14d306
2025/12/09 23:30:20 Successfully pushed image: apidover.azurecr.io/sdc-dev-app:latest

Run ID: ca1 was successful after 5m15s
✅ Imagem buildada e enviada para ACR
```

---

## 🐛 Se Ainda Houver Erro

### Erro: "Role assignment already exists"

**Significa:** Permissão já foi adicionada. Aguarde propagação ou reinicie o Jenkins.

### Erro: "Insufficient permissions" (mesmo após AcrPush)

**Solução:** Use role Contributor (mais permissiva):

```bash
az role assignment create \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --role Contributor \
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/APIDOVER
```

### Erro: "ACR not found"

**Verifique o nome do ACR:**
```bash
az acr list --resource-group DOVER --output table
```

**Atualize o nome no Jenkinsfile se necessário:**
```groovy
ACR_NAME = 'APIDOVER'  // ← Verificar se está correto
```

---

## 📋 Roles do Azure Container Registry

| Role | Permissões | Recomendado Para |
|------|-----------|------------------|
| **AcrPull** | Apenas pull (baixar) imagens | Leitura |
| **AcrPush** | Pull + Push + Build | ✅ CI/CD (Recomendado) |
| **AcrDelete** | Pull + Push + Delete | Limpeza de imagens |
| **Contributor** | Acesso total ao ACR | ⚠️ Se AcrPush não funcionar |
| **Owner** | Acesso total + gerenciar permissões | Administração |

---

## ✅ Checklist de Resolução

- [ ] Executar script `adicionar-permissoes-acr.ps1` ou `adicionar-permissoes-acr.sh`
- [ ] Verificar permissões com `az role assignment list`
- [ ] Aguardar 1-2 minutos para propagação
- [ ] Reiniciar Jenkins: `az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL`
- [ ] Aguardar 1-2 minutos para Jenkins reiniciar
- [ ] Executar "Build Now" no Jenkins
- [ ] Verificar Console Output para confirmar sucesso
- [ ] Verificar se imagem foi criada no ACR:
  ```bash
  az acr repository list --name APIDOVER --resource-group DOVER
  ```

---

## 📊 Informações do Ambiente

- **Service Principal ID:** `74596f5b-5c73-4256-9719-b52e7f978985`
- **Subscription ID:** `ef65818a-5356-4772-b849-0c793a23ec87`
- **Resource Group:** `DOVER`
- **ACR Name:** `APIDOVER`
- **ACR Login Server:** `apidover.azurecr.io`
- **Jenkins URL:** https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/

---

## 🎯 Próximos Passos

1. **Execute o script de permissões** (Opção 1 ou 2 acima)
2. **Aguarde propagação** (1-2 minutos)
3. **Reinicie Jenkins** (recomendado)
4. **Execute novo build** no Jenkins
5. **Verifique o resultado** no Console Output

---

**Status:** 🟡 **Aguardando permissões serem adicionadas**

**Ação necessária:** Execute o script de permissões e reinicie o Jenkins!



