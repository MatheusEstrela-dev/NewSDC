# 🚀 Configuração Jenkins + Azure Container Registry (ACR)

Este guia explica como configurar o Jenkins para fazer push automático de imagens Docker para o Azure Container Registry.

---

## 📋 Pré-requisitos

1. **Azure Container Registry criado**

   - Nome: `apidover` (ou o seu)
   - URL: `apidover.azurecr.io`

2. **Azure CLI instalado** (no servidor do Jenkins)

   - Ou será instalado automaticamente pelo pipeline

3. **Credenciais do Azure**
   - Service Principal (recomendado)
   - Ou credenciais de admin do ACR

---

## 🔐 Passo 1: Criar Service Principal no Azure

### 1.1. Criar Service Principal

```bash
# Login no Azure
az login

# Criar Service Principal
az ad sp create-for-rbac \
  --name "jenkins-sdc-acr" \
  --role contributor \
  --scopes /subscriptions/{SUBSCRIPTION_ID}/resourceGroups/{RESOURCE_GROUP}

# Ou com permissões específicas para ACR
az ad sp create-for-rbac \
  --name "jenkins-sdc-acr" \
  --role acrpush \
  --scopes /subscriptions/{SUBSCRIPTION_ID}/resourceGroups/{RESOURCE_GROUP}/providers/Microsoft.ContainerRegistry/registries/apidover
```

### 1.2. Obter Credenciais

O comando acima retorna:

```json
{
  "appId": "xxxx-xxxx-xxxx-xxxx",
  "displayName": "jenkins-sdc-acr",
  "password": "xxxx-xxxx-xxxx-xxxx",
  "tenant": "xxxx-xxxx-xxxx-xxxx"
}
```

**Guarde essas informações!**

---

## 🔑 Passo 2: Configurar Credenciais no Jenkins

### 2.1. Método 1: Via Interface Web (Manual)

1. Acesse Jenkins: `http://seu-jenkins:8080`
2. **Manage Jenkins** → **Manage Credentials**
3. Clique em **System** → **Global credentials**
4. Clique em **Add Credentials**

#### Credencial 1: Azure Service Principal

| Campo           | Valor                           |
| --------------- | ------------------------------- |
| **Kind**        | Username with password          |
| **Scope**       | Global                          |
| **Username**    | `appId` do Service Principal    |
| **Password**    | `password` do Service Principal |
| **ID**          | `azure-service-principal`       |
| **Description** | Azure Service Principal for ACR |

#### Credencial 2: Azure ACR (Alternativa)

| Campo           | Valor                                |
| --------------- | ------------------------------------ |
| **Kind**        | Username with password               |
| **Scope**       | Global                               |
| **Username**    | Nome do ACR (ex: `apidover`)         |
| **Password**    | Senha do admin do ACR                |
| **ID**          | `azure-acr-credentials`              |
| **Description** | Azure Container Registry Credentials |

**Obter senha do admin do ACR**:

```bash
az acr credential show --name apidover --query "passwords[0].value" -o tsv
```

### 2.2. Método 2: Via Variáveis de Ambiente (Automático)

Configure no arquivo `.env` do Jenkins ou no `docker-compose.yml`:

```bash
# Azure Service Principal
AZURE_CLIENT_ID=xxxx-xxxx-xxxx-xxxx
AZURE_CLIENT_SECRET=xxxx-xxxx-xxxx-xxxx
AZURE_TENANT_ID=xxxx-xxxx-xxxx-xxxx

# Azure ACR (alternativa)
AZURE_ACR_USERNAME=apidover
AZURE_ACR_PASSWORD=sua-senha-acr

# Nome do ACR
ACR_NAME=apidover
```

O script `03-azure-acr-config.groovy` criará as credenciais automaticamente.

---

## 🐳 Passo 3: Configurar Dockerfile do Jenkins

Atualize o `Dockerfile` do Jenkins para incluir Azure CLI:

```dockerfile
FROM jenkins/jenkins:lts

# Instalar Azure CLI
USER root
RUN curl -sL https://aka.ms/InstallAzureCLIDeb | bash

# Voltar para usuário jenkins
USER jenkins
```

Ou use o script de instalação:

```dockerfile
FROM jenkins/jenkins:lts

USER root
COPY install-azure-cli.sh /tmp/
RUN chmod +x /tmp/install-azure-cli.sh && \
    /tmp/install-azure-cli.sh

USER jenkins
```

---

## ✅ Passo 4: Verificar Configuração

### 4.1. Testar Credenciais

No Jenkins, execute no **Script Console** (`Manage Jenkins` → `Script Console`):

```groovy
import com.cloudbees.plugins.credentials.CredentialsProvider
import com.cloudbees.plugins.credentials.domains.Domain

def credentials = CredentialsProvider.lookupCredentials(
    com.cloudbees.plugins.credentials.common.UsernamePasswordCredentials.class,
    Jenkins.instance,
    null,
    null
)

credentials.each { cred ->
    println "ID: ${cred.id}, Description: ${cred.description}"
}
```

### 4.2. Testar Push Manual

Execute no terminal do Jenkins:

```bash
# Login no Azure
az login --service-principal \
  --username $AZURE_CLIENT_ID \
  --password $AZURE_CLIENT_SECRET \
  --tenant $AZURE_TENANT_ID

# Login no ACR
az acr login --name apidover

# Testar push
docker tag sdc-dev-app:latest apidover.azurecr.io/sdc-dev-app:test
docker push apidover.azurecr.io/sdc-dev-app:test
```

---

## 🔄 Passo 5: Configurar Pipeline

O `Jenkinsfile` já está configurado com o stage **"Tag and Push to ACR"**.

### Variáveis de Ambiente no Pipeline

O pipeline usa as seguintes variáveis:

```groovy
ACR_NAME = 'apidover'
ACR_LOGIN_SERVER = 'apidover.azurecr.io'
ACR_IMAGE = 'apidover.azurecr.io/sdc-dev-app'
ACR_TAG = "${BUILD_NUMBER}-${GIT_COMMIT.take(7)}"
```

### Tags Criadas

O pipeline cria duas tags:

- `${BUILD_NUMBER}-${GIT_COMMIT}` - Tag única por build
- `latest` - Tag sempre atualizada

---

## 🐛 Troubleshooting

### ❌ Erro: "az: command not found"

**Problema**: Azure CLI não está instalado

**Solução**:

```bash
# Instalar Azure CLI no Jenkins
docker exec -it jenkins-container bash
curl -sL https://aka.ms/InstallAzureCLIDeb | bash
```

Ou adicione ao Dockerfile do Jenkins.

### ❌ Erro: "unauthorized: authentication required"

**Problema**: Credenciais incorretas ou expiradas

**Soluções**:

1. **Verificar credenciais**:

   ```bash
   az acr login --name apidover
   ```

2. **Renovar Service Principal**:

   ```bash
   az ad sp credential reset --name jenkins-sdc-acr
   ```

3. **Verificar permissões**:
   ```bash
   az role assignment list \
     --assignee <appId> \
     --scope /subscriptions/{SUBSCRIPTION_ID}/resourceGroups/{RESOURCE_GROUP}
   ```

### ❌ Erro: "denied: requested access to the resource is denied"

**Problema**: Service Principal não tem permissão no ACR

**Solução**:

```bash
# Adicionar role AcrPush
az role assignment create \
  --assignee <appId> \
  --role AcrPush \
  --scope /subscriptions/{SUBSCRIPTION_ID}/resourceGroups/{RESOURCE_GROUP}/providers/Microsoft.ContainerRegistry/registries/apidover
```

### ❌ Pipeline pula o stage de push

**Problema**: Login falhou silenciosamente

**Solução**:

1. Verificar logs do pipeline
2. Verificar se credenciais estão configuradas
3. Testar login manualmente

---

## 📊 Verificar Imagens no ACR

### Via Azure CLI

```bash
# Listar repositórios
az acr repository list --name apidover --output table

# Listar tags
az acr repository show-tags --name apidover --repository sdc-dev-app --output table

# Ver detalhes
az acr repository show --name apidover --repository sdc-dev-app
```

### Via Portal Azure

1. Acesse [portal.azure.com](https://portal.azure.com)
2. Navegue até **Container registries** → **apidover**
3. Clique em **Repositories** → **sdc-dev-app**
4. Veja todas as tags e digests

---

## 🔒 Segurança

### Boas Práticas

1. **Use Service Principal** ao invés de admin do ACR
2. **Rotacione credenciais** regularmente
3. **Use escopo mínimo** de permissões
4. **Armazene secrets** em Jenkins Credentials (não em código)
5. **Use tags específicas** além de `latest`

### Rotação de Credenciais

```bash
# Renovar senha do Service Principal
az ad sp credential reset --name jenkins-sdc-acr

# Atualizar no Jenkins
# Manage Jenkins → Credentials → Editar credencial
```

---

## 📝 Exemplo de Uso

### Deploy usando Imagem do ACR

```bash
# Pull da imagem
docker pull apidover.azurecr.io/sdc-dev-app:latest

# Ou em Azure Container Instances
az container create \
  --resource-group meu-rg \
  --name sdc-app \
  --image apidover.azurecr.io/sdc-dev-app:latest \
  --registry-login-server apidover.azurecr.io \
  --registry-username apidover \
  --registry-password $(az acr credential show --name apidover --query "passwords[0].value" -o tsv)
```

---

## 🔗 Referências

- [Azure Container Registry Documentation](https://docs.microsoft.com/en-us/azure/container-registry/)
- [Jenkins Credentials Plugin](https://plugins.jenkins.io/credentials/)
- [Azure CLI Documentation](https://docs.microsoft.com/en-us/cli/azure/)

---

<div align="center">

**🚀 Jenkins + Azure ACR - Configuração Completa**

_Última atualização: 2025-01-21_

</div>



