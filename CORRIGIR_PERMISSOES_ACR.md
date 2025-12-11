# 🔧 Correção - Permissões do Service Principal no ACR

## ❌ Problema Identificado - Build #8

### Erro Completo:

```
ERROR: (AuthorizationFailed) The client '74596f5b-5c73-4256-9719-b52e7f978985'
with object id '36582784-e2a0-4b8d-980a-13bebee16c56' does not have authorization
to perform action 'Microsoft.ContainerRegistry/registries/read' over scope
'/subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/apidover'
```

### Causa Raiz:

O **Service Principal** `74596f5b-5c73-4256-9719-b52e7f978985` não tem permissões no ACR `apidover` que está no resource group `DOVER`.

---

## ✅ Solução - Adicionar Permissões

### Permissões Necessárias:

Para que o Jenkins possa fazer build e push de imagens, o Service Principal precisa da role **AcrPush** ou **Contributor** no ACR.

---

## 🚀 Comandos para Executar

### Opção 1: Role AcrPush (Recomendada - Mais Restrita)

```bash
# Dar permissão AcrPush (build + push de imagens)
az role assignment create \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --role AcrPush \
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/apidover
```

**O que essa role permite:**
- ✅ Ler imagens do ACR
- ✅ Push de imagens para o ACR
- ✅ Executar `az acr build`
- ❌ Não permite deletar ACR ou modificar configurações

---

### Opção 2: Role Contributor (Mais Ampla)

```bash
# Dar permissão Contributor (acesso total ao ACR)
az role assignment create \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --role Contributor \
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/apidover
```

**O que essa role permite:**
- ✅ Tudo que AcrPush permite
- ✅ Modificar configurações do ACR
- ✅ Criar/deletar repositories
- ⚠️ Mais permissiva (use apenas se necessário)

---

## 📋 Passo a Passo

### 1. Execute o comando no terminal:

**Recomendado:** Use a Opção 1 (AcrPush)

```bash
az role assignment create \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --role AcrPush \
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/apidover
```

### 2. Aguarde a propagação (30 segundos)

As permissões levam alguns segundos para propagar no Azure.

### 3. Teste o pipeline manualmente

Acesse o Jenkins e clique em **"Build Now"**:
```
https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
```

---

## 🔍 Verificar Permissões Atuais

Para ver as permissões que o Service Principal já tem:

```bash
# Listar todas as permissões do Service Principal
az role assignment list \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --output table
```

**Deve mostrar algo como:**

```
Principal                            Role              Scope
----------------------------------   ----------------  --------------------------------------------
74596f5b-5c73-4256-9719-b52e7f978985  Contributor      /subscriptions/ef65818a-.../resourceGroups/DEFESA_CIVIL
74596f5b-5c73-4256-9719-b52e7f978985  AcrPush          /subscriptions/ef65818a-.../DOVER/.../apidover
```

---

## 🎯 Alternativa - Usar Admin User do ACR

Se você não quiser usar Service Principal, pode habilitar o **Admin User** do ACR:

### 1. Habilitar Admin User:

```bash
az acr update --name apidover --admin-enabled true
```

### 2. Obter credenciais:

```bash
az acr credential show --name apidover --resource-group DOVER
```

**Resultado:**
```json
{
  "username": "apidover",
  "passwords": [
    {
      "name": "password",
      "value": "<senha-1>"
    },
    {
      "name": "password2",
      "value": "<senha-2>"
    }
  ]
}
```

### 3. Adicionar credencial no Jenkins:

1. Acesse: `https://jenkinssdc.azurewebsites.net/manage`
2. Credentials → System → Global credentials → Add Credentials
3. Kind: **Username with password**
4. ID: `azure-acr-credentials`
5. Username: `apidover`
6. Password: `<senha-1>` (da saída acima)

**⚠️ Menos seguro:** Admin User tem acesso total. Service Principal com AcrPush é mais seguro.

---

## 📊 Comparação das Soluções

| Aspecto | Service Principal + AcrPush | Admin User |
|---------|----------------------------|------------|
| **Segurança** | ✅ Melhor - Role específica | ⚠️ Acesso total |
| **Rotação de credenciais** | ✅ Fácil via Azure AD | ❌ Manual |
| **Auditoria** | ✅ Rastreável | ⚠️ Menos granular |
| **Recomendado para** | ✅ Produção | ⚠️ Desenvolvimento |

---

## 🔄 Após Adicionar Permissões

### Resultado Esperado no Build #9:

```
[Pipeline] stage { (Build and Push to ACR)
🏗️  Building Docker images using Azure Container Registry...
+ az login --service-principal ...
✅ Login successful

+ az acr build --registry apidover --resource-group DOVER ...
Packing source code into tar to upload...
Uploading archived source code from '/tmp/build_archive_xxx.tar.gz'...
Sending context (XX.X MB) to registry: apidover...
Queued a build with ID: ca1
Waiting for an agent...
2025/12/09 23:25:00 Downloading source code...
2025/12/09 23:25:05 Successfully downloaded source code
2025/12/09 23:25:10 Running: docker build ...
Step 1/20 : FROM php:8.2-fpm
...
Step 20/20 : CMD ["php-fpm"]
Successfully built 5f6a3b8c9d2e
Successfully tagged apidover.azurecr.io/sdc-dev-app:8-f59820e
Successfully tagged apidover.azurecr.io/sdc-dev-app:latest
2025/12/09 23:30:15 Successfully pushed image: apidover.azurecr.io/sdc-dev-app:8-f59820e
2025/12/09 23:30:20 Successfully pushed image: apidover.azurecr.io/sdc-dev-app:latest

✅ Imagem buildada e enviada para ACR

[Pipeline] stage { (Deploy to Azure App Service)
🚀 Deploying to newsdc2027...
✅ Deploy successful

Finished: SUCCESS
```

---

## 🐛 Se Ainda Houver Erro

### Erro: "Insufficient permissions"

**Solução:** Use Contributor em vez de AcrPush:

```bash
az role assignment create \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --role Contributor \
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/apidover
```

### Erro: "Role assignment already exists"

**Significa:** Permissão já foi adicionada. Aguarde alguns segundos e tente o build novamente.

---

## 📚 Roles do Azure Container Registry

| Role | Permissões |
|------|-----------|
| **AcrPull** | Apenas pull (baixar) imagens |
| **AcrPush** | Pull + Push + Build |
| **AcrDelete** | Pull + Push + Delete |
| **Contributor** | Acesso total ao ACR |
| **Owner** | Acesso total + gerenciar permissões |

---

## ✅ Checklist

- [ ] Executar comando `az role assignment create` com role AcrPush
- [ ] Aguardar 30 segundos para propagação
- [ ] Verificar permissões com `az role assignment list`
- [ ] Executar "Build Now" no Jenkins
- [ ] Verificar Console Output para confirmar sucesso

---

**Status:** 🟡 **Aguardando permissões serem adicionadas ao Service Principal**

**Próximo passo:** Execute o comando `az role assignment create` e depois clique em "Build Now" no Jenkins!
