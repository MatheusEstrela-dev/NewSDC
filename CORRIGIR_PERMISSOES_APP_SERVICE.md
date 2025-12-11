# 🔐 Corrigir Permissões do Service Principal no App Service

## 📊 Status Atual (Build #8)

### ✅ O que está funcionando:
- ✅ Build and Push to ACR - SUCESSO!
- ✅ Imagem Docker criada: `apidover.azurecr.io/sdc-dev-app:8-30c062d`
- ✅ az acr login removido (sem erro de Docker socket)

### ❌ O que está falhando:
- ❌ Deploy to Azure App Service - FALHA de permissões

## 🔴 Erro Atual

```
ERROR: (AuthorizationFailed) The client '74596f5b-5c73-4256-9719-b52e7f978985'
with object id '36582784-e2a0-4b8d-980a-13bebee16c56' does not have authorization
to perform action 'Microsoft.Web/sites/config/list/action' over scope
'/subscriptions/ef65818a-5356-4772-b849-0c793a23ec87/resourceGroups/DEFESA_CIVIL/providers/Microsoft.Web/sites/newsdc2027/config/appsettings'
```

### Traduzindo:
O Service Principal usado pelo Jenkins não tem permissão para:
- Listar/atualizar configurações do App Service `newsdc2027`
- Executar `az webapp config container set`

## 🔧 Solução

### Opção 1: Via Azure Portal (Recomendado)

#### Passo 1: Acessar o App Service

1. Acesse o Azure Portal: https://portal.azure.com
2. Navegue para: **App Services** → **newsdc2027**

#### Passo 2: Adicionar Role Assignment

1. No menu lateral do App Service, clique em **Access control (IAM)**
2. Clique no botão **+ Add** → **Add role assignment**

#### Passo 3: Selecionar a Role

Na aba **Role**:
- Selecione: **"Website Contributor"**
  - Esta role permite gerenciar websites, mas não deletá-los
  - Permissões: start, stop, restart, configurar container, etc.

Alternativa (mais permissiva):
- **"Contributor"** - Permissões completas no App Service

#### Passo 4: Selecionar o Service Principal

Na aba **Members**:
1. Em **Assign access to**, selecione: **User, group, or service principal**
2. Clique em **+ Select members**
3. Procure pelo Service Principal usando uma destas opções:

   **Opção A - Por Object ID (mais confiável):**
   ```
   36582784-e2a0-4b8d-980a-13bebee16c56
   ```

   **Opção B - Por Client ID:**
   ```
   74596f5b-5c73-4256-9719-b52e7f978985
   ```

4. Selecione o Service Principal da lista
5. Clique em **Select**

#### Passo 5: Revisar e Atribuir

1. Clique em **Review + assign**
2. Revise as informações
3. Clique em **Review + assign** novamente
4. Aguarde a confirmação: "Role assignment created"

#### Passo 6: Aguardar Propagação

⏳ **Aguarde 2-5 minutos** para as permissões propagarem no Azure

---

### Opção 2: Conceder Permissões no Resource Group (Mais amplo)

Se você quiser que o Service Principal possa gerenciar TODOS os recursos no Resource Group `DEFESA_CIVIL`:

#### Via Azure Portal:

1. Acesse: **Resource groups** → **DEFESA_CIVIL**
2. Clique em **Access control (IAM)**
3. **+ Add** → **Add role assignment**
4. Role: **"Contributor"** ou **"Website Contributor"**
5. Members: Selecione o Service Principal (Object ID: `36582784-e2a0-4b8d-980a-13bebee16c56`)
6. **Review + assign**

#### Via Azure CLI:

```bash
# Variáveis
RESOURCE_GROUP="DEFESA_CIVIL"
SERVICE_PRINCIPAL_ID="36582784-e2a0-4b8d-980a-13bebee16c56"  # Object ID
SUBSCRIPTION_ID="ef65818a-5356-4772-b849-0c793a23ec87"

# Conceder role "Website Contributor" no Resource Group
az role assignment create \
  --assignee $SERVICE_PRINCIPAL_ID \
  --role "Website Contributor" \
  --scope /subscriptions/$SUBSCRIPTION_ID/resourceGroups/$RESOURCE_GROUP

# OU conceder "Contributor" (mais permissivo)
az role assignment create \
  --assignee $SERVICE_PRINCIPAL_ID \
  --role "Contributor" \
  --scope /subscriptions/$SUBSCRIPTION_ID/resourceGroups/$RESOURCE_GROUP
```

---

### Opção 3: Via Azure CLI - Apenas no App Service

```bash
# Variáveis
APP_SERVICE_NAME="newsdc2027"
RESOURCE_GROUP="DEFESA_CIVIL"
SERVICE_PRINCIPAL_ID="36582784-e2a0-4b8d-980a-13bebee16c56"
SUBSCRIPTION_ID="ef65818a-5356-4772-b849-0c793a23ec87"

# Conceder role "Website Contributor" no App Service
az role assignment create \
  --assignee $SERVICE_PRINCIPAL_ID \
  --role "Website Contributor" \
  --scope /subscriptions/$SUBSCRIPTION_ID/resourceGroups/$RESOURCE_GROUP/providers/Microsoft.Web/sites/$APP_SERVICE_NAME
```

---

## 🎯 Próximos Passos

### Após Conceder Permissões:

1. ⏳ **Aguardar 2-5 minutos** para propagação

2. **Opção A - Disparar novo build no Jenkins:**
   - Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
   - Clique em **Build Now**

3. **Opção B - Reiniciar Jenkins (força refresh de credenciais):**
   ```bash
   az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
   ```
   - Aguarde 2-3 minutos
   - Dispare novo build

---

## ✅ Como Verificar se Funcionou

O próximo build (Build #9 ou posterior) deve:

```
✅ Stage: Checkout
✅ Stage: Pre-flight Checks
✅ Stage: Build and Push to ACR
✅ Stage: Deploy to Azure App Service  ← Deve passar agora!
```

Se você ver no console:

```
✅ Imagem buildada e enviada para ACR
Atualizando App Service: newsdc2027
Imagem: apidover.azurecr.io/sdc-dev-app:X-XXXXXXX
Reiniciando App Service...
✅ Deploy para Azure App Service concluído!
🌐 URL: https://newsdc2027.azurewebsites.net
```

Significa que o deploy funcionou! 🎉

---

## 📋 Checklist de Permissões

- [ ] 1. Acessar Azure Portal → App Service `newsdc2027`
- [ ] 2. Access control (IAM) → Add role assignment
- [ ] 3. Selecionar role: "Website Contributor" ou "Contributor"
- [ ] 4. Selecionar Service Principal (Object ID: `36582784-e2a0-4b8d-980a-13bebee16c56`)
- [ ] 5. Review + assign
- [ ] 6. Aguardar 2-5 minutos
- [ ] 7. Disparar novo build no Jenkins
- [ ] 8. Verificar que "Deploy to Azure App Service" passa com sucesso

---

## 🔍 Roles Disponíveis

| Role | Escopo | Permissões |
|------|--------|------------|
| **Website Contributor** | Recomendado | Gerenciar websites (start, stop, restart, configurar) |
| **Contributor** | Mais amplo | Gerenciar todos os recursos (exceto RBAC) |
| **Owner** | Não recomendado | Permissões completas (incluindo RBAC) |

**Recomendação:** Use **"Website Contributor"** para seguir o princípio de menor privilégio.

---

## 🐛 Troubleshooting

### Problema: "Access was recently granted, please refresh your credentials"

**Solução:**
1. Aguarde mais 5-10 minutos (pode demorar até 15 minutos em casos raros)
2. Reinicie o Jenkins:
   ```bash
   az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
   ```

### Problema: Role já está atribuída mas erro continua

**Verificar:**
1. Confirme que a role foi atribuída ao Service Principal CORRETO
   - Object ID deve ser: `36582784-e2a0-4b8d-980a-13bebee16c56`
2. Confirme que a role é "Website Contributor" ou "Contributor"
3. Confirme que o escopo é o App Service `newsdc2027` ou o Resource Group `DEFESA_CIVIL`

---

## 📊 Resumo da Situação

### O que JÁ está funcionando:
- ✅ Jenkins encontra o Jenkinsfile (Script Path correto)
- ✅ Pre-flight checks passando
- ✅ Build do Docker funcionando
- ✅ Push para ACR funcionando (permissões ACR OK)
- ✅ Login no Azure via Service Principal

### O que FALTA:
- ❌ Permissões do Service Principal no App Service `newsdc2027`

**Estamos a 1 passo de ter o CI/CD completo funcionando!** 🚀

---

**Data:** 10/12/2025
**Build analisado:** #8
**Próxima ação:** Conceder permissões "Website Contributor" no App Service
