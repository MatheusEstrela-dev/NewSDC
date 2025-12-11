# 🚀 Resumo da Configuração CI/CD Completa

Este documento resume todas as configurações necessárias para o pipeline CI/CD completo com Jenkins, Azure ACR e GitHub.

---

## ✅ Checklist de Configuração

### 1. ✅ Azure Container Registry (ACR)

- [x] ACR criado: `apidover.azurecr.io`
- [x] Imagem push realizada: `apidover.azurecr.io/sdc-dev-app:latest`
- [x] Jenkinsfile atualizado com stage de push para ACR

**Status**: ✅ **CONCLUÍDO**

---

### 2. 🔑 Jenkins > Credenciais

#### Credenciais Necessárias:

1. **Azure Service Principal** (Recomendado)

   - **ID**: `azure-service-principal`
   - **Username**: App ID do Service Principal
   - **Password**: Password do Service Principal
   - **Configuração**: Via variáveis de ambiente ou interface web

2. **Azure ACR Credentials** (Alternativa)
   - **ID**: `azure-acr-credentials`
   - **Username**: Nome do ACR (`apidover`)
   - **Password**: Senha do admin do ACR
   - **Obter senha**: `az acr credential show --name apidover --query "passwords[0].value" -o tsv`

#### Como Configurar:

**Opção A: Via Interface Web**

1. Jenkins → **Manage Jenkins** → **Manage Credentials**
2. **System** → **Global credentials** → **Add Credentials**
3. Preencher conforme acima

**Opção B: Via Variáveis de Ambiente**

```bash
# No docker-compose.yml ou .env do Jenkins
AZURE_CLIENT_ID=xxxx-xxxx-xxxx-xxxx
AZURE_CLIENT_SECRET=xxxx-xxxx-xxxx-xxxx
AZURE_TENANT_ID=xxxx-xxxx-xxxx-xxxx
AZURE_ACR_USERNAME=apidover
AZURE_ACR_PASSWORD=sua-senha-acr
ACR_NAME=apidover
```

**Status**: ⚠️ **PENDENTE - Configurar credenciais**

---

### 3. 📝 JenkinsFile > Pipeline > CLI do Azure Service

#### O que foi configurado:

- ✅ Stage **"Tag and Push to ACR"** adicionado ao Jenkinsfile
- ✅ Instalação automática do Azure CLI (se não estiver instalado)
- ✅ Múltiplos métodos de autenticação (Service Principal, ACR direto, interativo)
- ✅ Tags automáticas: `${BUILD_NUMBER}-${GIT_COMMIT}` e `latest`
- ✅ Script de configuração automática: `03-azure-acr-config.groovy`

#### Variáveis de Ambiente no Pipeline:

```groovy
ACR_NAME = 'apidover'
ACR_LOGIN_SERVER = 'apidover.azurecr.io'
ACR_IMAGE = 'apidover.azurecr.io/sdc-dev-app'
ACR_TAG = "${BUILD_NUMBER}-${GIT_COMMIT.take(7)}"
```

**Status**: ✅ **CONCLUÍDO**

---

### 4. 🔗 Webhook GitHub > Jenkins > AddWebhook

#### Configuração Necessária:

1. **No Jenkins**:

   - Instalar plugins: `GitHub Plugin`, `GitHub Branch Source Plugin`
   - Configurar GitHub Server em **Manage Jenkins** → **Configure System**
   - Habilitar **"GitHub hook trigger for GITScm polling"** no job

2. **No GitHub**:
   - **Settings** → **Webhooks** → **Add webhook**
   - **Payload URL**: `http://seu-jenkins:8080/github-webhook/`
   - **Content type**: `application/json`
   - **Events**: ✅ Push, ✅ Pull request

#### Documentação Completa:

📖 Ver: [`Doc/GITHUB_WEBHOOK_JENKINS.md`](Doc/GITHUB_WEBHOOK_JENKINS.md)

**Status**: ⚠️ **PENDENTE - Configurar webhook no GitHub**

---

## 📋 Próximos Passos

### Passo 1: Criar Service Principal no Azure

```bash
az login
az ad sp create-for-rbac \
  --name "jenkins-sdc-acr" \
  --role acrpush \
  --scopes /subscriptions/{SUBSCRIPTION_ID}/resourceGroups/{RESOURCE_GROUP}/providers/Microsoft.ContainerRegistry/registries/apidover
```

**Guarde as credenciais retornadas!**

### Passo 2: Configurar Credenciais no Jenkins

**Opção A - Via Interface**:

1. Jenkins → **Manage Jenkins** → **Manage Credentials**
2. Adicionar credencial `azure-service-principal` com App ID e Password

**Opção B - Via Variáveis**:
Adicionar ao `docker-compose.yml` do Jenkins:

```yaml
environment:
  - AZURE_CLIENT_ID=xxxx
  - AZURE_CLIENT_SECRET=xxxx
  - AZURE_TENANT_ID=xxxx
  - ACR_NAME=apidover
```

### Passo 3: Instalar Azure CLI no Jenkins (se necessário)

**Opção A - Via Dockerfile**:

```dockerfile
FROM jenkins/jenkins:lts
USER root
RUN curl -sL https://aka.ms/InstallAzureCLIDeb | bash
USER jenkins
```

**Opção B - Via Script**:
O pipeline tentará instalar automaticamente, mas você pode executar:

```bash
docker exec -it jenkins-container bash
curl -sL https://aka.ms/InstallAzureCLIDeb | bash
```

### Passo 4: Configurar Webhook no GitHub

1. Acesse seu repositório no GitHub
2. **Settings** → **Webhooks** → **Add webhook**
3. URL: `http://seu-jenkins:8080/github-webhook/`
4. Events: ✅ Push

📖 Ver guia completo: [`Doc/GITHUB_WEBHOOK_JENKINS.md`](Doc/GITHUB_WEBHOOK_JENKINS.md)

---

## 📚 Documentação Criada

| Documento                                                                                                                    | Descrição                                        |
| ---------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------ |
| [`Doc/JENKINS_ACR_SETUP.md`](Doc/JENKINS_ACR_SETUP.md)                                                                       | Guia completo de configuração Jenkins + ACR      |
| [`Doc/GITHUB_WEBHOOK_JENKINS.md`](Doc/GITHUB_WEBHOOK_JENKINS.md)                                                             | Guia de configuração de webhook GitHub → Jenkins |
| [`SDC/Jenkinsfile`](SDC/Jenkinsfile)                                                                                         | Pipeline atualizado com push para ACR            |
| [`SDC/docker/jenkins/casc.yaml`](SDC/docker/jenkins/casc.yaml)                                                               | Configuração JCasC com credenciais do ACR        |
| [`SDC/docker/jenkins/init.groovy.d/03-azure-acr-config.groovy`](SDC/docker/jenkins/init.groovy.d/03-azure-acr-config.groovy) | Script de configuração automática de credenciais |
| [`SDC/docker/jenkins/install-azure-cli.sh`](SDC/docker/jenkins/install-azure-cli.sh)                                         | Script de instalação do Azure CLI                |

---

## 🔍 Verificação

### Testar Push Manual

```bash
# No servidor do Jenkins
az login --service-principal \
  --username $AZURE_CLIENT_ID \
  --password $AZURE_CLIENT_SECRET \
  --tenant $AZURE_TENANT_ID

az acr login --name apidover
docker tag sdc-dev-app:latest apidover.azurecr.io/sdc-dev-app:test
docker push apidover.azurecr.io/sdc-dev-app:test
```

### Verificar Imagens no ACR

```bash
az acr repository show-tags --name apidover --repository sdc-dev-app --output table
```

### Testar Webhook

1. Fazer um commit e push no GitHub
2. Verificar se o build inicia automaticamente no Jenkins
3. Verificar logs do build

---

## 🐛 Troubleshooting

### Pipeline não faz push para ACR

1. Verificar se credenciais estão configuradas
2. Verificar logs do stage "Tag and Push to ACR"
3. Testar login manualmente

### Webhook não dispara build

1. Verificar URL do webhook no GitHub
2. Verificar se Jenkins está acessível
3. Verificar configuração do job (GitHub hook trigger habilitado)

### Erro de autenticação Azure

1. Verificar se Service Principal tem role `AcrPush`
2. Verificar se credenciais não expiraram
3. Renovar credenciais se necessário

---

## ✅ Status Final

| Componente              | Status         | Ação Necessária                  |
| ----------------------- | -------------- | -------------------------------- |
| **Azure ACR**           | ✅ Configurado | Nenhuma                          |
| **Jenkinsfile**         | ✅ Atualizado  | Nenhuma                          |
| **Credenciais Jenkins** | ⚠️ Pendente    | Configurar Service Principal     |
| **Webhook GitHub**      | ⚠️ Pendente    | Adicionar webhook no GitHub      |
| **Azure CLI**           | ⚠️ Verificar   | Instalar se não estiver presente |

---

<div align="center">

**🚀 Configuração CI/CD - Resumo Executivo**

_Última atualização: 2025-01-21_

</div>



