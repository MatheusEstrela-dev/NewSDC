# 🐳 Jenkins Container - Build e Deploy

Este diretório contém todos os arquivos necessários para construir e fazer deploy do container Jenkins no Azure.

---

## 📁 Estrutura de Arquivos

```
jenkins/
├── Dockerfile                    # Imagem do Jenkins com todas as ferramentas
├── docker-compose.jenkins.yml    # Compose para desenvolvimento local
├── casc.yaml                     # Configuration as Code
├── nginx.conf                    # Configuração do Nginx (reverse proxy)
├── healthcheck.sh                # Script de health check
├── build-and-push-to-acr.sh     # Script Linux/Mac para build e push
├── build-and-push-to-acr.ps1    # Script Windows para build e push
├── deploy-to-azure.sh            # Script Linux/Mac para deploy no Azure
├── install-azure-cli.sh          # Script de instalação do Azure CLI
└── init.groovy.d/                # Scripts de inicialização
    ├── 01-security.groovy
    ├── 02-docker-config.groovy
    └── 03-azure-acr-config.groovy
```

---

## 🚀 Quick Start

### 1. Build e Push para ACR

**Linux/Mac:**
```bash
cd SDC/docker/jenkins
chmod +x build-and-push-to-acr.sh
./build-and-push-to-acr.sh -n apidover -t latest
```

**Windows:**
```powershell
cd SDC\docker\jenkins
.\build-and-push-to-acr.ps1 -AcrName "apidover" -Tag "latest"
```

### 2. Deploy no Azure

**Linux/Mac:**
```bash
chmod +x deploy-to-azure.sh
./deploy-to-azure.sh -g meu-rg -n apidover
```

**Manual:**
```bash
az container create \
  --resource-group meu-rg \
  --name sdc-jenkins \
  --image apidover.azurecr.io/sdc-jenkins:latest \
  --registry-login-server apidover.azurecr.io \
  --registry-username apidover \
  --registry-password $(az acr credential show --name apidover --query "passwords[0].value" -o tsv) \
  --cpu 4 --memory 8Gi \
  --ports 8080 50000
```

---

## 📖 Documentação Completa

- **Deploy no Azure**: [`Doc/JENKINS_AZURE_DEPLOY.md`](../../Doc/JENKINS_AZURE_DEPLOY.md)
- **Configuração ACR**: [`Doc/JENKINS_ACR_SETUP.md`](../../Doc/JENKINS_ACR_SETUP.md)
- **Webhook GitHub**: [`Doc/GITHUB_WEBHOOK_JENKINS.md`](../../Doc/GITHUB_WEBHOOK_JENKINS.md)

---

## 🔧 Desenvolvimento Local

Para rodar o Jenkins localmente:

```bash
cd SDC/docker
docker-compose -f docker-compose.jenkins.yml up -d
```

Acesse: `http://localhost:8080`

---

## 📝 Notas

- A imagem inclui: Docker CLI, Azure CLI, Node.js, PHP, Composer
- Plugins essenciais são instalados automaticamente
- Configuração via JCasC (Configuration as Code)
- Health check configurado

---

<div align="center">

**🐳 Jenkins Container - SDC Project**

</div>




