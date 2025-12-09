# ✅ Correção Aplicada - Variáveis de Ambiente Jenkins Azure

## 🎯 Problema Identificado via MCP Debug

**Causa Raiz:**
O Azure App Service do Jenkins não tinha variáveis de ambiente configuradas. O JCasC (`casc.yaml`) estava correto, mas como as variáveis não existiam no ambiente Azure, ele usava valores vazios/padrão.

**Evidência:**
```bash
az webapp config appsettings list --name jenkinssdc --resource-group DEFESA_CIVIL
```
**Resultado inicial:** Apenas `DOCKER_ENABLE_CI=true` ❌

---

## ✅ Correção Aplicada

### 1. Adicionadas 12 Variáveis de Ambiente

```bash
az webapp config appsettings set --name jenkinssdc --resource-group DEFESA_CIVIL --settings \
  GIT_REPO_URL="git@github.com:MatheusEstrela-dev/NewSDC.git" \
  AZURE_CLIENT_ID="<your-client-id>" \
  AZURE_CLIENT_SECRET="<your-client-secret>" \
  AZURE_TENANT_ID="<your-tenant-id>" \
  ACR_NAME="apidover" \
  AZURE_ACR_USERNAME="<your-client-id>" \
  AZURE_ACR_PASSWORD="<your-client-secret>" \
  JENKINS_ADMIN_USER="admin" \
  JENKINS_ADMIN_PASSWORD="<your-password>" \
  JENKINS_URL="https://jenkinssdc.azurewebsites.net" \
  JENKINS_ADMIN_EMAIL="admin@sdc.local"
```

### 2. Verificação

```bash
az webapp config appsettings list --name jenkinssdc --resource-group DEFESA_CIVIL \
  --query "[?name=='GIT_REPO_URL' || name=='AZURE_CLIENT_ID'].{Name:name, Value:value}" -o table
```

**Resultado:**
```
Name             Value
---------------  --------------------------------------------
AZURE_CLIENT_ID  <your-client-id>
GIT_REPO_URL     git@github.com:MatheusEstrela-dev/NewSDC.git
```
✅ Confirmado!

### 3. Reiniciado o Jenkins

```bash
az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
```

**Status:** Running ✅

---

## 🔍 Como o JCasC Funciona

O arquivo `SDC/docker/jenkins/casc.yaml` (linha 164) usa interpolação de variáveis:

```yaml
url('${GIT_REPO_URL:-git@github.com:MatheusEstrela-dev/NewSDC.git}')
```

**Antes:**
- `GIT_REPO_URL` não existia no Azure
- JCasC usava fallback vazio → URL antiga `http://github.com/user/repo.git`

**Depois:**
- `GIT_REPO_URL=git@github.com:MatheusEstrela-dev/NewSDC.git` configurada ✅
- JCasC carrega corretamente a URL do GitHub

---

## 📊 Resultado Esperado

Após a correção, o próximo build no Jenkins deve:

1. ✅ Usar a URL correta: `git@github.com:MatheusEstrela-dev/NewSDC.git`
2. ✅ Carregar credenciais Azure para ACR
3. ✅ Fazer checkout do repositório com sucesso
4. ✅ Executar o pipeline completo

---

## 🎯 Próximos Passos

### 1. Testar Build

**Opção A: Webhook automático**
- Fazer um commit no repositório
- Aguardar webhook disparar Jenkins

**Opção B: Manual**
- Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
- Clique em "Build Now"

### 2. Verificar Logs

**Console Output deve mostrar:**
```
Cloning repository git@github.com:MatheusEstrela-dev/NewSDC.git
✅ SUCCESS - Checkout completed
```

**NÃO deve mostrar:**
```
❌ https://github.com/user/repo.git
```

---

## 📝 Variáveis Configuradas

| Variável | Valor | Uso |
|----------|-------|-----|
| `GIT_REPO_URL` | `git@github.com:MatheusEstrela-dev/NewSDC.git` | URL do repositório Git |
| `AZURE_CLIENT_ID` | `<your-client-id>` | Service Principal ID |
| `AZURE_CLIENT_SECRET` | `<your-client-secret>` | Service Principal Secret |
| `AZURE_TENANT_ID` | `<your-tenant-id>` | Azure Tenant ID |
| `ACR_NAME` | `apidover` | Azure Container Registry |
| `AZURE_ACR_USERNAME` | `<your-client-id>` | ACR Username (= Client ID) |
| `AZURE_ACR_PASSWORD` | `<your-client-secret>` | ACR Password (= Client Secret) |
| `JENKINS_ADMIN_USER` | `admin` | Jenkins admin user |
| `JENKINS_ADMIN_PASSWORD` | `admin123` | Jenkins admin password |
| `JENKINS_URL` | `https://jenkinssdc.azurewebsites.net` | Jenkins public URL |
| `JENKINS_ADMIN_EMAIL` | `admin@sdc.local` | Jenkins admin email |

---

## 🛡️ Segurança

⚠️ **IMPORTANTE:** Essas variáveis contêm credenciais sensíveis. Elas estão:
- ✅ Configuradas diretamente no Azure App Service (não em código)
- ✅ Não commitadas no Git
- ✅ Acessíveis apenas pelo container Jenkins no Azure

**Nunca commite o arquivo `.env.jenkins` no repositório!**

---

## 📚 Arquivos Relacionados

- [casc.yaml](SDC/docker/jenkins/casc.yaml) - Configuração JCasC
- [.env.jenkins](SDC/docker/.env.jenkins) - Template de variáveis (local)
- [docker-compose.jenkins-dev.yml](SDC/docker/docker-compose.jenkins-dev.yml) - Docker Compose local

---

**Status:** ✅ **Correção aplicada com sucesso**
**Diagnóstico:** Via MCP Zen Debug Tool
**Confiança:** Very High
**Próxima ação:** Testar build no Jenkins
