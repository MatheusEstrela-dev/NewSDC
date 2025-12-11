# 🚀 Deploy do Jenkins no Azure Container Instances

Este guia explica como fazer build e deploy do container Jenkins no Azure Container Instances (ACI) usando a imagem do Azure Container Registry.

---

## 📋 Pré-requisitos

1. **Azure CLI instalado e configurado**
2. **Azure Container Registry criado** (`apidover.azurecr.io`)
3. **Resource Group no Azure**
4. **Docker instalado** (para build local)

---

## 🔨 Passo 1: Build e Push da Imagem Jenkins para ACR

### Opção A: Linux/Mac

```bash
cd SDC/docker/jenkins

# Dar permissão de execução
chmod +x build-and-push-to-acr.sh

# Build e push
./build-and-push-to-acr.sh -n apidover -t latest
```

### Opção B: Windows (PowerShell)

```powershell
cd SDC\docker\jenkins

# Build e push
.\build-and-push-to-acr.ps1 -AcrName "apidover" -Tag "latest"
```

### Opção C: Manual

```bash
# 1. Login no Azure
az login

# 2. Login no ACR
az acr login --name apidover

# 3. Build da imagem
cd SDC/docker/jenkins
docker build -t sdc-jenkins:latest -f Dockerfile .

# 4. Tag para ACR
docker tag sdc-jenkins:latest apidover.azurecr.io/sdc-jenkins:latest

# 5. Push para ACR
docker push apidover.azurecr.io/sdc-jenkins:latest
```

---

## 🚀 Passo 2: Deploy no Azure Container Instances

### Opção A: Script Automatizado (Linux/Mac)

```bash
cd SDC/docker/jenkins

# Dar permissão de execução
chmod +x deploy-to-azure.sh

# Deploy
./deploy-to-azure.sh \
  -g meu-resource-group \
  -n apidover \
  -i sdc-jenkins \
  -t latest \
  --cpu 4 \
  --memory 8
```

### Opção B: Manual (Azure CLI)

```bash
# 1. Criar Resource Group (se não existir)
az group create \
  --name sdc-jenkins-rg \
  --location brazilsouth

# 2. Obter senha do ACR
ACR_PASSWORD=$(az acr credential show --name apidover --query "passwords[0].value" -o tsv)

# 3. Criar Container Instance
az container create \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins \
  --image apidover.azurecr.io/sdc-jenkins:latest \
  --registry-login-server apidover.azurecr.io \
  --registry-username apidover \
  --registry-password "$ACR_PASSWORD" \
  --cpu 4 \
  --memory 8Gi \
  --location brazilsouth \
  --ports 8080 50000 \
  --dns-name-label sdc-jenkins-$(date +%s) \
  --environment-variables \
    JAVA_OPTS="-Xms512m -Xmx6g -Djava.awt.headless=true" \
    JENKINS_OPTS="--prefix=/" \
  --secure-environment-variables \
    JENKINS_ADMIN_USER="admin" \
    JENKINS_ADMIN_PASSWORD="admin123"
```

---

## 📊 Passo 3: Verificar Deploy

### Obter Informações do Container

```bash
# Ver status
az container show \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins \
  --query "{FQDN:ipAddress.fqdn,IP:ipAddress.ip,State:containers[0].instanceView.currentState.state}" \
  -o table

# Ver logs
az container logs \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins \
  --follow
```

### Acessar Jenkins

Após o deploy, você receberá um FQDN (Fully Qualified Domain Name):

```
http://sdc-jenkins-12345678.brazilsouth.azurecontainer.io:8080
```

**Primeiro acesso:**
- Usuário: `admin` (ou o valor de `JENKINS_ADMIN_USER`)
- Senha: `admin123` (ou o valor de `JENKINS_ADMIN_PASSWORD`)

---

## 🔧 Configurações Recomendadas

### Recursos Mínimos

| Recurso | Mínimo | Recomendado | Produção |
|---------|--------|-------------|----------|
| **CPU** | 2 | 4 | 8+ |
| **Memória** | 4GB | 8GB | 16GB+ |
| **Disco** | N/A | N/A | Azure Files (persistente) |

### Variáveis de Ambiente

```bash
JAVA_OPTS="-Xms512m -Xmx6g -Djava.awt.headless=true"
JENKINS_OPTS="--prefix=/"
JENKINS_ADMIN_USER="admin"
JENKINS_ADMIN_PASSWORD="senha-segura-aqui"
```

---

## 💾 Persistência de Dados

**⚠️ IMPORTANTE**: Azure Container Instances não mantém dados após reinicialização!

### Opção 1: Azure Files (Recomendado)

```bash
# 1. Criar Azure Storage Account
az storage account create \
  --name sdcjenkinsstorage \
  --resource-group sdc-jenkins-rg \
  --location brazilsouth \
  --sku Standard_LRS

# 2. Criar File Share
az storage share create \
  --name jenkins-home \
  --account-name sdcjenkinsstorage

# 3. Obter chave de acesso
STORAGE_KEY=$(az storage account keys list \
  --resource-group sdc-jenkins-rg \
  --account-name sdcjenkinsstorage \
  --query "[0].value" -o tsv)

# 4. Criar container com volume montado
az container create \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins \
  --image apidover.azurecr.io/sdc-jenkins:latest \
  --registry-login-server apidover.azurecr.io \
  --registry-username apidover \
  --registry-password "$ACR_PASSWORD" \
  --cpu 4 \
  --memory 8Gi \
  --ports 8080 50000 \
  --azure-file-volume-share-name jenkins-home \
  --azure-file-volume-account-name sdcjenkinsstorage \
  --azure-file-volume-account-key "$STORAGE_KEY" \
  --azure-file-volume-mount-path /var/jenkins_home
```

### Opção 2: Azure Container Apps (Melhor para Produção)

Para persistência automática e melhor escalabilidade, considere usar **Azure Container Apps**:

```bash
# Criar Container App Environment
az containerapp env create \
  --name sdc-jenkins-env \
  --resource-group sdc-jenkins-rg \
  --location brazilsouth

# Criar Container App
az containerapp create \
  --name sdc-jenkins \
  --resource-group sdc-jenkins-rg \
  --environment sdc-jenkins-env \
  --image apidover.azurecr.io/sdc-jenkins:latest \
  --registry-server apidover.azurecr.io \
  --registry-username apidover \
  --registry-password "$ACR_PASSWORD" \
  --cpu 4 \
  --memory 8Gi \
  --target-port 8080 \
  --ingress external
```

---

## 🔄 Gerenciamento do Container

### Parar Container

```bash
az container stop \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins
```

### Iniciar Container

```bash
az container start \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins
```

### Reiniciar Container

```bash
az container restart \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins
```

### Ver Logs

```bash
# Logs completos
az container logs \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins

# Logs em tempo real
az container logs \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins \
  --follow
```

### Atualizar Imagem

```bash
# 1. Fazer novo build e push
cd SDC/docker/jenkins
./build-and-push-to-acr.sh -n apidover -t latest

# 2. Recriar container
az container delete \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins \
  --yes

# 3. Recriar com nova imagem
./deploy-to-azure.sh -g sdc-jenkins-rg -n apidover
```

### Remover Container

```bash
az container delete \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins \
  --yes
```

---

## 🔒 Segurança

### 1. Usar Senhas Fortes

```bash
# Gerar senha segura
openssl rand -base64 32

# Usar no deploy
--secure-environment-variables \
  JENKINS_ADMIN_PASSWORD="senha-gerada-aqui"
```

### 2. Configurar HTTPS

Use **Azure Application Gateway** ou **Azure Front Door** para HTTPS:

```bash
# Criar Application Gateway com SSL
az network application-gateway create \
  --name sdc-jenkins-gateway \
  --resource-group sdc-jenkins-rg \
  --location brazilsouth \
  --capacity 2 \
  --sku Standard_v2 \
  --public-ip-address sdc-jenkins-ip
```

### 3. Restringir Acesso por IP

```bash
# Adicionar regra de firewall
az container create \
  ... \
  --ip-address Public \
  --ports 8080 50000
```

---

## 🐛 Troubleshooting

### Container não inicia

```bash
# Ver logs de erro
az container logs \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins

# Ver eventos
az container show \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins \
  --query "containers[0].instanceView.events" \
  -o table
```

### Erro de autenticação ACR

```bash
# Verificar credenciais
az acr credential show --name apidover

# Renovar senha
az acr credential renew --name apidover --password-name password1
```

### Container reinicia constantemente

```bash
# Verificar recursos
az container show \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins \
  --query "{CPU:containers[0].resources.requests.cpu,Memory:containers[0].resources.requests.memoryInGb}"

# Aumentar recursos se necessário
az container create \
  ... \
  --cpu 8 \
  --memory 16Gi
```

### Não consigo acessar via FQDN

```bash
# Verificar se container está rodando
az container show \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins \
  --query "containers[0].instanceView.currentState.state"

# Verificar IP público
az container show \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins \
  --query "ipAddress.ip" -o tsv
```

---

## 📊 Monitoramento

### Ver Métricas

```bash
# CPU e Memória
az monitor metrics list \
  --resource /subscriptions/{SUBSCRIPTION_ID}/resourceGroups/sdc-jenkins-rg/providers/Microsoft.ContainerInstance/containerGroups/sdc-jenkins \
  --metric "CpuUsage" "MemoryUsage" \
  --start-time 2025-01-21T00:00:00Z
```

### Configurar Alertas

```bash
# Criar alerta de CPU
az monitor metrics alert create \
  --name "jenkins-high-cpu" \
  --resource-group sdc-jenkins-rg \
  --scopes /subscriptions/{SUBSCRIPTION_ID}/resourceGroups/sdc-jenkins-rg/providers/Microsoft.ContainerInstance/containerGroups/sdc-jenkins \
  --condition "avg CpuUsage > 80" \
  --window-size 5m \
  --evaluation-frequency 1m
```

---

## 💰 Estimativa de Custos

### Azure Container Instances

| Configuração | Custo Mensal (Aproximado) |
|-------------|---------------------------|
| 2 CPU, 4GB RAM | ~$50 USD |
| 4 CPU, 8GB RAM | ~$100 USD |
| 8 CPU, 16GB RAM | ~$200 USD |

**Nota**: Custos variam por região e uso real.

---

## 🔗 Referências

- [Azure Container Instances Documentation](https://docs.microsoft.com/en-us/azure/container-instances/)
- [Azure Container Registry Documentation](https://docs.microsoft.com/en-us/azure/container-registry/)
- [Jenkins Docker Image](https://hub.docker.com/r/jenkins/jenkins)

---

<div align="center">

**🚀 Jenkins no Azure - Deploy Completo**

*Última atualização: 2025-01-21*

</div>




