# 🐳 Resumo: Container Jenkins para Azure

## ✅ O que foi configurado

### 1. **Dockerfile atualizado**
- ✅ Azure CLI instalado
- ✅ Docker CLI e Docker Compose
- ✅ Node.js, PHP, Composer
- ✅ Plugins essenciais do Jenkins
- ✅ Scripts de configuração automática

### 2. **Scripts criados**
- ✅ `build-and-push-to-acr.sh` - Build e push para ACR (Linux/Mac)
- ✅ `build-and-push-to-acr.ps1` - Build e push para ACR (Windows)
- ✅ `deploy-to-azure.sh` - Deploy no Azure Container Instances

### 3. **Documentação**
- ✅ `JENKINS_AZURE_DEPLOY.md` - Guia completo de deploy
- ✅ `JENKINS_ACR_SETUP.md` - Configuração de credenciais
- ✅ `GITHUB_WEBHOOK_JENKINS.md` - Configuração de webhook

---

## 🚀 Como Montar o Container

### Passo 1: Build e Push para ACR

**Windows (PowerShell):**
```powershell
cd SDC\docker\jenkins
.\build-and-push-to-acr.ps1 -AcrName "apidover" -Tag "latest"
```

**Linux/Mac:**
```bash
cd SDC/docker/jenkins
chmod +x build-and-push-to-acr.sh
./build-and-push-to-acr.sh -n apidover -t latest
```

**Resultado**: Imagem `apidover.azurecr.io/sdc-jenkins:latest` no ACR

---

### Passo 2: Deploy no Azure Container Instances

**Linux/Mac:**
```bash
cd SDC/docker/jenkins
chmod +x deploy-to-azure.sh
./deploy-to-azure.sh \
  -g sdc-jenkins-rg \
  -n apidover \
  --cpu 4 \
  --memory 8
```

**Manual (Azure CLI):**
```bash
# 1. Criar Resource Group
az group create --name sdc-jenkins-rg --location brazilsouth

# 2. Obter senha do ACR
ACR_PASSWORD=$(az acr credential show --name apidover --query "passwords[0].value" -o tsv)

# 3. Criar Container
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
  --dns-name-label sdc-jenkins-$(date +%s) \
  --environment-variables \
    JAVA_OPTS="-Xms512m -Xmx6g -Djava.awt.headless=true"
```

---

### Passo 3: Acessar Jenkins

Após o deploy, obtenha o FQDN:

```bash
az container show \
  --resource-group sdc-jenkins-rg \
  --name sdc-jenkins \
  --query "ipAddress.fqdn" -o tsv
```

Acesse: `http://<fqdn>:8080`

**Credenciais padrão:**
- Usuário: `admin`
- Senha: `admin123`

---

## 📋 Checklist de Deploy

- [ ] Azure CLI instalado e configurado
- [ ] Login no Azure realizado (`az login`)
- [ ] Resource Group criado
- [ ] Build da imagem Jenkins concluído
- [ ] Push para ACR realizado
- [ ] Container criado no Azure
- [ ] FQDN obtido e acessível
- [ ] Jenkins acessível via navegador

---

## 🔍 Verificar Status

```bash
# Ver status do container
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

---

## 📚 Documentação Completa

- **Deploy no Azure**: [`Doc/JENKINS_AZURE_DEPLOY.md`](JENKINS_AZURE_DEPLOY.md)
- **Configuração ACR**: [`Doc/JENKINS_ACR_SETUP.md`](JENKINS_ACR_SETUP.md)
- **Webhook GitHub**: [`Doc/GITHUB_WEBHOOK_JENKINS.md`](GITHUB_WEBHOOK_JENKINS.md)

---

<div align="center">

**🐳 Container Jenkins - Pronto para Deploy!**

</div>




