# 🔧 Corrigir Permissões ACR Build

## ❌ Erro Identificado

**Erro:**
```
ERROR: (AuthorizationFailed) The client '74596f5b-5c73-4256-9719-b52e7f978985' 
does not have authorization to perform action 'Microsoft.ContainerRegistry/registries/read' 
over scope '/subscriptions/.../registries/APIDOVER'
```

**Causa:**
- O comando `az acr build` requer permissões além de `AcrPush`
- Precisa da role `AcrBuild` ou permissões de leitura no registro
- A role `AcrPush` não inclui `Microsoft.ContainerRegistry/registries/read`

---

## ✅ Solução: Adicionar Role AcrBuild

O comando `az acr build` requer a role **`AcrBuild`** que inclui:
- `Microsoft.ContainerRegistry/registries/read`
- `Microsoft.ContainerRegistry/registries/builds/write`
- `Microsoft.ContainerRegistry/registries/builds/read`

---

## 📋 Comandos para Corrigir

### 1. Verificar Roles Atuais

```bash
az role assignment list \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/APIDOVER \
  --output table
```

### 2. Adicionar Role AcrBuild

```bash
az role assignment create \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --role AcrBuild \
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/APIDOVER
```

**Ou usando o nome do Service Principal:**

```bash
az role assignment create \
  --assignee jenkins-sdc-acr \
  --role AcrBuild \
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/APIDOVER
```

### 3. Verificar Permissões Após Adicionar

```bash
az role assignment list \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/APIDOVER \
  --output table
```

**Deve mostrar:**
- `AcrPush` ✅
- `AcrBuild` ✅ (nova)

---

## 🔄 Alternativa: Usar Role Contributor (Não Recomendado)

Se `AcrBuild` não funcionar, pode usar `Contributor` (mais permissivo):

```bash
az role assignment create \
  --assignee 74596f5b-5c73-4256-9719-b52e7f978985 \
  --role Contributor \
  --scope /subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/APIDOVER
```

**⚠️ Atenção:** `Contributor` dá permissões amplas. Use apenas se `AcrBuild` não funcionar.

---

## 📊 Roles Necessárias para az acr build

| Role | Permissões Incluídas | Necessária? |
|------|---------------------|--------------|
| **AcrPush** | Push de imagens | ✅ Sim |
| **AcrBuild** | Build de imagens + read | ✅ Sim |
| **AcrPull** | Pull de imagens | ❌ Não (já incluído) |

---

## ✅ Após Adicionar AcrBuild

1. **Aguarde 1-2 minutos** para propagação das permissões
2. **Disparar novo build:**
   - Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
   - Clique em "Build Now"
3. **Verificar console:**
   - O stage "Build and Push to ACR" deve passar agora
   - Deve aparecer: `Successfully built and pushed image`

---

## 🎯 Resumo

**Problema:** Service Principal não tem permissão para `az acr build`  
**Solução:** Adicionar role `AcrBuild`  
**Comando:** `az role assignment create --assignee <SP> --role AcrBuild --scope <ACR>`  
**Status:** ⏳ Aguardando correção de permissões



