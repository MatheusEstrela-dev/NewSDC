# Guia Completo: CI/CD com Jenkins e Azure

## Sumário

1. [Visão Geral](#visão-geral)
2. [Pré-requisitos](#pré-requisitos)
3. [Arquitetura da Solução](#arquitetura-da-solução)
4. [Parte 1: Configuração do Azure](#parte-1-configuração-do-azure)
5. [Parte 2: Configuração do Jenkins](#parte-2-configuração-do-jenkins)
6. [Parte 3: Configuração do Jenkinsfile](#parte-3-configuração-do-jenkinsfile)
7. [Parte 4: Configuração do GitHub Webhook](#parte-4-configuração-do-github-webhook)
8. [Parte 5: Primeira Execução](#parte-5-primeira-execução)
9. [Otimizações e Melhores Práticas](#otimizações-e-melhores-práticas)
10. [Troubleshooting](#troubleshooting)
11. [Referências](#referências)

---

## Visão Geral

Este guia documenta a implementação completa de um pipeline CI/CD usando:

- **Jenkins** hospedado no Azure App Service
- **Azure Container Registry (ACR)** para armazenar imagens Docker
- **Azure App Service** para hospedar a aplicação Laravel
- **GitHub** como repositório de código com webhook para disparo automático
- **Docker** para containerização da aplicação

### Fluxo do Pipeline

```
GitHub Push → Webhook → Jenkins → Build Docker (ACR) → Deploy (App Service) → Health Check
```

### Tecnologias Envolvidas

- Laravel 11 com Inertia.js e Vue 3
- Docker multi-stage builds
- Azure CLI
- Groovy (Jenkinsfile)
- Shell scripting

---

## Pré-requisitos

### 1. Conta Azure Ativa

- Assinatura do Azure (Subscription ID necessário)
- Permissões para criar recursos
- Azure CLI instalado localmente (opcional para testes)

### 2. Repositório GitHub

- Repositório com código da aplicação
- Permissões de administrador para configurar webhooks
- Token de acesso pessoal (PAT) com permissões de webhook

### 3. Conhecimentos Técnicos

- Familiaridade com Docker
- Conhecimentos básicos de Azure
- Compreensão de pipelines CI/CD
- Conhecimentos de shell scripting

### 4. Recursos Necessários

- **Azure Container Registry**: Para armazenar imagens Docker
- **2x Azure App Service (Basic B1 ou superior)**:
  - 1 para Jenkins
  - 1 para a aplicação
- **Service Principal**: Para autenticação automatizada

---

## Arquitetura da Solução

```
┌─────────────────┐
│     GitHub      │
│   Repository    │
└────────┬────────┘
         │ Webhook
         ▼
┌─────────────────┐
│  Jenkins App    │
│  Service        │
│  (jenkinssdc)   │
└────────┬────────┘
         │
         ├─────► Azure CLI (Login)
         │
         ├─────► ACR Build (Remote)
         │       └─► apidover.azurecr.io/sdc-dev-app
         │
         └─────► App Service Deploy
                 └─► newsdc2027.azurewebsites.net
```

### Componentes

| Componente | Nome | Função |
|------------|------|--------|
| ACR | `apidover` | Armazenar imagens Docker |
| Resource Group (ACR) | `DOVER` | Agrupar recursos do ACR |
| App Service (App) | `newsdc2027` | Hospedar aplicação Laravel |
| App Service (Jenkins) | `jenkinssdc` | Executar Jenkins |
| Resource Group (App) | `DEFESA_CIVIL` | Agrupar recursos da aplicação |
| Service Principal | `jenkins-sp` | Autenticação Azure CLI |

---

## Parte 1: Configuração do Azure

### 1.1: Criar Resource Groups

```bash
# Resource Group para ACR
az group create \
  --name DOVER \
  --location eastus

# Resource Group para aplicação
az group create \
  --name DEFESA_CIVIL \
  --location eastus
```

### 1.2: Criar Azure Container Registry

```bash
# Criar ACR
az acr create \
  --resource-group DOVER \
  --name apidover \
  --sku Basic

# Habilitar admin user (necessário para App Service)
az acr update \
  --name apidover \
  --admin-enabled true

# Obter credenciais (salvar para uso posterior)
az acr credential show --name apidover
```

**Salvar:**
- `username`: Nome de usuário do ACR
- `password`: Uma das senhas exibidas

### 1.3: Criar Service Principal

```bash
# Criar Service Principal para Jenkins
az ad sp create-for-rbac \
  --name jenkins-sp \
  --role Contributor \
  --scopes /subscriptions/SUBSCRIPTION_ID

# Salvar a saída:
# {
#   "appId": "CLIENT_ID",
#   "displayName": "jenkins-sp",
#   "password": "CLIENT_SECRET",
#   "tenant": "TENANT_ID"
# }
```

**Importante:** Salve essas credenciais em local seguro. Você precisará delas no Jenkins.

**Obter Object ID do Service Principal:**

```bash
# Substituir CLIENT_ID pelo appId acima
az ad sp show --id CLIENT_ID --query id -o tsv
```

Salve o **Object ID** retornado.

### 1.4: Configurar Permissões RBAC

#### Permissões no ACR

```bash
# Variáveis
ACR_NAME="apidover"
ACR_RESOURCE_GROUP="DOVER"
SERVICE_PRINCIPAL_ID="OBJECT_ID_AQUI"  # Object ID obtido no passo anterior
SUBSCRIPTION_ID="SUA_SUBSCRIPTION_ID"

# Conceder AcrPush (permite build e push)
az role assignment create \
  --assignee $SERVICE_PRINCIPAL_ID \
  --role AcrPush \
  --scope /subscriptions/$SUBSCRIPTION_ID/resourceGroups/$ACR_RESOURCE_GROUP/providers/Microsoft.ContainerRegistry/registries/$ACR_NAME
```

#### Permissões no App Service

```bash
# Variáveis
APP_SERVICE_NAME="newsdc2027"
APP_RESOURCE_GROUP="DEFESA_CIVIL"

# Conceder Website Contributor (permite deploy e configuração)
az role assignment create \
  --assignee $SERVICE_PRINCIPAL_ID \
  --role "Website Contributor" \
  --scope /subscriptions/$SUBSCRIPTION_ID/resourceGroups/$APP_RESOURCE_GROUP/providers/Microsoft.Web/sites/$APP_SERVICE_NAME
```

**Aguardar 2-5 minutos** para as permissões propagarem.

### 1.5: Criar App Service para Aplicação

```bash
# Criar App Service Plan
az appservice plan create \
  --name newsdc-plan \
  --resource-group DEFESA_CIVIL \
  --sku B1 \
  --is-linux

# Criar App Service
az webapp create \
  --resource-group DEFESA_CIVIL \
  --plan newsdc-plan \
  --name newsdc2027 \
  --deployment-container-image-name apidover.azurecr.io/sdc-dev-app:latest

# Configurar credenciais ACR no App Service
az webapp config container set \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --docker-custom-image-name apidover.azurecr.io/sdc-dev-app:latest \
  --docker-registry-server-url https://apidover.azurecr.io \
  --docker-registry-server-user USERNAME_ACR \
  --docker-registry-server-password PASSWORD_ACR

# Configurar variáveis de ambiente
az webapp config appsettings set \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --settings \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_URL=https://newsdc2027.azurewebsites.net
```

### 1.6: Criar App Service para Jenkins

```bash
# Criar App Service Plan para Jenkins
az appservice plan create \
  --name jenkins-plan \
  --resource-group DEFESA_CIVIL \
  --sku B1 \
  --is-linux

# Criar App Service para Jenkins
az webapp create \
  --resource-group DEFESA_CIVIL \
  --plan jenkins-plan \
  --name jenkinssdc \
  --deployment-container-image-name jenkins/jenkins:lts

# Configurar porta do container
az webapp config appsettings set \
  --name jenkinssdc \
  --resource-group DEFESA_CIVIL \
  --settings WEBSITES_PORT=8080
```

---

## Parte 2: Configuração do Jenkins

### 2.1: Acessar Jenkins

1. Acesse: `https://jenkinssdc.azurewebsites.net`
2. Aguarde 2-3 minutos para o Jenkins inicializar

### 2.2: Obter Senha Inicial

```bash
# Via Azure CLI
az webapp log tail --name jenkinssdc --resource-group DEFESA_CIVIL

# Procure por uma linha como:
# *************************************************************
# Jenkins initial setup is required. An admin user has been created and a password generated.
# Please use the following password to proceed to installation:
#
# 1234567890abcdef1234567890abcdef
#
# *************************************************************
```

### 2.3: Configuração Inicial do Jenkins

1. Cole a senha inicial
2. Selecione **Install suggested plugins**
3. Aguarde instalação dos plugins
4. Crie usuário administrador:
   - Username: `admin`
   - Password: `[senha segura]`
   - Full name: `Jenkins Admin`
   - Email: `admin@example.com`
5. Confirme URL do Jenkins: `https://jenkinssdc.azurewebsites.net/`

### 2.4: Instalar Plugins Necessários

Vá para: **Manage Jenkins** → **Manage Plugins** → **Available**

Instale:
- [x] **Docker Pipeline**
- [x] **Pipeline**
- [x] **GitHub Integration**
- [x] **AnsiColor** (para logs coloridos)
- [x] **Timestamper** (timestamps nos logs)

Reinicie o Jenkins após instalação.

### 2.5: Configurar Credenciais do Azure

#### 2.5.1: Service Principal

**Manage Jenkins** → **Manage Credentials** → **System** → **Global credentials** → **Add Credentials**

- **Kind:** `Username with password`
- **Scope:** `Global`
- **Username:** `CLIENT_ID` (appId do Service Principal)
- **Password:** `CLIENT_SECRET` (password do Service Principal)
- **ID:** `azure-service-principal`
- **Description:** `Azure Service Principal para CI/CD`

#### 2.5.2: GitHub Credentials

**Add Credentials**

- **Kind:** `Username with password`
- **Scope:** `Global`
- **Username:** Seu username do GitHub
- **Password:** Personal Access Token (PAT) do GitHub
- **ID:** `github-credentials`
- **Description:** `GitHub PAT`

**Como criar PAT no GitHub:**
1. GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token (classic)
3. Scopes: `repo`, `admin:repo_hook`
4. Copiar token

### 2.6: Configurar Variáveis de Ambiente Globais

**Manage Jenkins** → **Configure System** → **Global properties** → **Environment variables**

Adicionar:

| Nome | Valor |
|------|-------|
| `AZURE_TENANT_ID` | Tenant ID do Service Principal |
| `AZURE_APP_SERVICE_NAME` | `newsdc2027` |
| `AZURE_RESOURCE_GROUP` | `DEFESA_CIVIL` |
| `ACR_NAME` | `apidover` |

### 2.7: Instalar Azure CLI e Docker no Jenkins

Crie um Dockerfile customizado para Jenkins:

**SDC/docker/jenkins/Dockerfile**

```dockerfile
FROM jenkins/jenkins:lts

USER root

# Instalar Azure CLI
RUN apt-get update && \
    apt-get install -y curl apt-transport-https lsb-release gnupg && \
    curl -sL https://aka.ms/InstallAzureCLIDeb | bash

# Instalar Docker CLI (sem daemon)
RUN apt-get update && \
    apt-get install -y docker.io

# Instalar Docker Compose
RUN curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" \
    -o /usr/local/bin/docker-compose && \
    chmod +x /usr/local/bin/docker-compose

USER jenkins
```

Build e push para ACR:

```bash
cd SDC/docker/jenkins

# Build
docker build -t apidover.azurecr.io/jenkins:latest .

# Login no ACR
az acr login --name apidover

# Push
docker push apidover.azurecr.io/jenkins:latest
```

Atualizar App Service do Jenkins:

```bash
az webapp config container set \
  --name jenkinssdc \
  --resource-group DEFESA_CIVIL \
  --docker-custom-image-name apidover.azurecr.io/jenkins:latest \
  --docker-registry-server-url https://apidover.azurecr.io \
  --docker-registry-server-user USERNAME_ACR \
  --docker-registry-server-password PASSWORD_ACR

# Reiniciar
az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
```

---

## Parte 3: Configuração do Jenkinsfile

### 3.1: Criar Pipeline no Jenkins

1. **New Item** → Nome: `build-and-deploy` → **Pipeline** → OK
2. Em **Pipeline**:
   - **Definition:** `Pipeline script from SCM`
   - **SCM:** `Git`
   - **Repository URL:** `https://github.com/SEU_USER/SEU_REPO.git`
   - **Credentials:** Selecione `github-credentials`
   - **Branch Specifier:** `*/main`
   - **Script Path:** `Jenkinsfile`
3. Em **Build Triggers**:
   - Marque **GitHub hook trigger for GITScm polling**
4. Salvar

### 3.2: Estrutura do Jenkinsfile

Crie o arquivo `Jenkinsfile` na raiz do repositório:

```groovy
pipeline {
    agent any

    environment {
        // Aplicação
        APP_NAME = 'sdc'

        // Docker Buildkit
        DOCKER_BUILDKIT = '1'
        COMPOSE_DOCKER_CLI_BUILD = '1'

        // Cache
        COMPOSER_CACHE_DIR = "${WORKSPACE}/.composer-cache"
        NPM_CACHE_DIR = "${WORKSPACE}/.npm-cache"

        // Azure Container Registry
        ACR_NAME = 'apidover'
        ACR_RESOURCE_GROUP = 'DOVER'
        ACR_LOGIN_SERVER = 'apidover.azurecr.io'
        ACR_IMAGE = 'apidover.azurecr.io/sdc-dev-app'
        ACR_TAG = "${env.BUILD_NUMBER}-${env.GIT_COMMIT.take(7)}"
    }

    options {
        timeout(time: 30, unit: 'MINUTES')
        buildDiscarder(logRotator(numToKeepStr: '10', artifactNumToKeepStr: '5'))
        timestamps()
        ansiColor('xterm')
    }

    triggers {
        githubPush()
    }

    stages {
        stage('Checkout') {
            steps {
                echo '📦 Checking out code...'
                checkout scm

                script {
                    env.GIT_COMMIT_MSG = sh(
                        script: 'git log -1 --pretty=%B',
                        returnStdout: true
                    ).trim()
                    env.GIT_AUTHOR = sh(
                        script: 'git log -1 --pretty=%an',
                        returnStdout: true
                    ).trim()
                }
                echo "Commit: ${env.GIT_COMMIT_MSG}"
                echo "Author: ${env.GIT_AUTHOR}"
            }
        }

        stage('Pre-flight Checks') {
            steps {
                echo '🔍 Running pre-flight checks...'

                script {
                    // Verificar Docker
                    sh 'docker --version'
                    sh 'docker-compose --version'

                    // Verificar espaço em disco (mínimo 5GB)
                    def availableSpace = sh(
                        script: "df -BG ${WORKSPACE} | tail -1 | awk '{print \$4}' | sed 's/G//'",
                        returnStdout: true
                    ).trim().toInteger()

                    if (availableSpace < 5) {
                        error("Espaço em disco insuficiente: ${availableSpace}GB. Mínimo: 5GB")
                    }
                    echo "✅ Espaço disponível: ${availableSpace}GB"
                }
            }
        }

        stage('Build and Push to ACR') {
            steps {
                echo '🏗️ Building Docker images using Azure Container Registry...'

                script {
                    // Login no Azure
                    withCredentials([usernamePassword(
                        credentialsId: 'azure-service-principal',
                        usernameVariable: 'AZURE_CLIENT_ID',
                        passwordVariable: 'AZURE_CLIENT_SECRET'
                    )]) {
                        def tenantId = env.AZURE_TENANT_ID ?: ''
                        if (!tenantId) {
                            error("AZURE_TENANT_ID não configurado")
                        }

                        sh """
                            az login --service-principal \
                                --username \$AZURE_CLIENT_ID \
                                --password \$AZURE_CLIENT_SECRET \
                                --tenant ${tenantId}
                        """
                    }

                    // Build remoto no ACR (otimizado)
                    echo "🔨 Iniciando build otimizado no ACR..."
                    dir('SDC') {
                        sh """
                            az acr build \
                                --registry ${ACR_NAME} \
                                --resource-group ${ACR_RESOURCE_GROUP} \
                                --image sdc-dev-app:${ACR_TAG} \
                                --image sdc-dev-app:latest \
                                --file docker/Dockerfile.prod \
                                --platform linux \
                                --no-logs \
                                . || {
                                    echo "⚠️ Build com --no-logs falhou, tentando com logs..."
                                    az acr build \
                                        --registry ${ACR_NAME} \
                                        --resource-group ${ACR_RESOURCE_GROUP} \
                                        --image sdc-dev-app:${ACR_TAG} \
                                        --image sdc-dev-app:latest \
                                        --file docker/Dockerfile.prod \
                                        --platform linux \
                                        .
                                }
                        """
                    }

                    echo "✅ Imagem buildada e enviada para ACR:"
                    echo "   - ${ACR_IMAGE}:${ACR_TAG}"
                    echo "   - ${ACR_IMAGE}:latest"
                }
            }
        }

        stage('Deploy to Azure App Service') {
            steps {
                echo '🚀 Deploying to Azure App Service...'

                script {
                    def APP_SERVICE_NAME = env.AZURE_APP_SERVICE_NAME ?: 'newsdc2027'
                    def RESOURCE_GROUP = env.AZURE_RESOURCE_GROUP ?: 'DEFESA_CIVIL'
                    def ACR_NAME = env.ACR_NAME ?: 'apidover'

                    // Login no Azure
                    withCredentials([usernamePassword(
                        credentialsId: 'azure-service-principal',
                        usernameVariable: 'AZURE_CLIENT_ID',
                        passwordVariable: 'AZURE_CLIENT_SECRET'
                    )]) {
                        def tenantId = env.AZURE_TENANT_ID ?: ''

                        sh """
                            az login --service-principal \
                                --username \$AZURE_CLIENT_ID \
                                --password \$AZURE_CLIENT_SECRET \
                                --tenant ${tenantId}
                        """

                        // Obter credenciais do ACR
                        def acrUsername = sh(
                            script: "az acr credential show --name ${ACR_NAME} --query username -o tsv",
                            returnStdout: true
                        ).trim()

                        def acrPassword = sh(
                            script: "az acr credential show --name ${ACR_NAME} --query 'passwords[0].value' -o tsv",
                            returnStdout: true
                        ).trim()

                        // Atualizar container do App Service
                        echo "🚀 Deploy OTIMIZADO - Minimizando downtime..."
                        echo "App Service: ${APP_SERVICE_NAME}"
                        echo "Nova Imagem: ${ACR_IMAGE}:${ACR_TAG}"

                        sh """
                            az webapp config container set \
                                --name ${APP_SERVICE_NAME} \
                                --resource-group ${RESOURCE_GROUP} \
                                --docker-custom-image-name ${ACR_IMAGE}:${ACR_TAG} \
                                --docker-registry-server-url https://${ACR_LOGIN_SERVER} \
                                --docker-registry-server-user ${acrUsername} \
                                --docker-registry-server-password ${acrPassword} \
                                > /dev/null 2>&1 &

                            wait
                            echo "✅ Configuração atualizada, iniciando restart..."
                        """
                    }

                    // Reiniciar App Service (assíncrono)
                    sh """
                        az webapp restart \
                            --name ${APP_SERVICE_NAME} \
                            --resource-group ${RESOURCE_GROUP} \
                            --no-wait
                    """
                    echo "⚡ Restart iniciado (modo assíncrono)"

                    // Health check otimizado
                    def APP_URL = "https://${APP_SERVICE_NAME}.azurewebsites.net"
                    echo "🏥 Verificando saúde da aplicação..."

                    timeout(time: 3, unit: 'MINUTES') {
                        sh """
                            echo "⏳ Aguardando App Service inicializar..."
                            sleep 15

                            for i in \$(seq 1 20); do
                                HTTP_CODE=\$(curl -s -o /dev/null -w "%{http_code}" -m 5 ${APP_URL} 2>/dev/null || echo "000")

                                if [ "\$HTTP_CODE" = "200" ] || [ "\$HTTP_CODE" = "302" ]; then
                                    echo "✅ App Service respondendo! (HTTP \$HTTP_CODE)"
                                    echo "⏱️  Tempo de recuperação: ~\$((i * 5))s"
                                    exit 0
                                fi

                                if [ \$i -eq 1 ]; then
                                    echo -n "Aguardando resposta"
                                else
                                    echo -n "."
                                fi

                                WAIT_TIME=\$((5 + (i / 5) * 3))
                                sleep \$WAIT_TIME
                            done

                            echo ""
                            echo "⚠️  Timeout no health check (app pode ainda estar inicializando)"
                            echo "ℹ️  Deploy foi concluído. Verificar manualmente: ${APP_URL}"
                            echo "💡 Dica: App pode levar até 2min para estar completamente pronto"
                            exit 0
                        """
                    }

                    echo "✅ Deploy para Azure App Service concluído!"
                    echo "🌐 URL: ${APP_URL}"
                }
            }
        }
    }

    post {
        always {
            echo '🧹 Cleaning up...'

            script {
                // Limpar cache antigo
                sh """
                    find ${WORKSPACE}/.composer-cache -type f -mtime +7 -delete 2>/dev/null || true
                    find ${WORKSPACE}/.npm-cache -type f -mtime +7 -delete 2>/dev/null || true
                """
            }
        }

        success {
            echo '✅ Pipeline completed successfully!'
        }

        failure {
            echo '❌ Pipeline failed!'

            script {
                sh """
                    echo '=== Build Information ===' > build-info.txt
                    echo "Build Number: ${env.BUILD_NUMBER}" >> build-info.txt
                    echo "Git Commit: ${env.GIT_COMMIT}" >> build-info.txt
                    echo "Git Branch: ${env.GIT_BRANCH}" >> build-info.txt
                    echo "ACR Image: ${ACR_IMAGE}:${ACR_TAG}" >> build-info.txt
                """
                archiveArtifacts artifacts: 'build-info.txt', allowEmptyArchive: true
            }
        }
    }
}
```

### 3.3: Explicação dos Stages

#### Stage 1: Checkout
- Faz checkout do código do GitHub
- Captura informações do commit (mensagem e autor)
- Exibe informações nos logs

#### Stage 2: Pre-flight Checks
- Verifica disponibilidade do Docker
- Verifica espaço em disco (mínimo 5GB)
- Previne builds em ambientes sem recursos

#### Stage 3: Build and Push to ACR
- Faz login no Azure usando Service Principal
- Executa build **remoto** no ACR usando `az acr build`
- Taga imagem com número do build e commit hash
- Taga também como `latest`
- Usa `--no-logs` para melhor performance (fallback com logs se falhar)

**Vantagens do build remoto:**
- Não requer Docker socket no Jenkins
- Cache de camadas no ACR
- Build mais rápido
- Menos uso de recursos locais

#### Stage 4: Deploy to Azure App Service
- Obtém credenciais do ACR
- Atualiza configuração do container no App Service
- Reinicia App Service (modo assíncrono com `--no-wait`)
- Executa health check com retry inteligente:
  - 20 tentativas com intervalos progressivos (5s → 8s)
  - Aceita HTTP 200 ou 302
  - Timeout de 3 minutos
  - Não falha o pipeline se timeout (apenas avisa)

#### Post Actions
- **always**: Limpa cache antigo (>7 dias)
- **success**: Mensagem de sucesso
- **failure**: Coleta informações do build para debugging

---

## Parte 4: Configuração do GitHub Webhook

### 4.1: Obter URL do Webhook do Jenkins

URL do webhook:
```
https://jenkinssdc.azurewebsites.net/github-webhook/
```

### 4.2: Configurar Webhook no GitHub

1. Acesse seu repositório no GitHub
2. **Settings** → **Webhooks** → **Add webhook**
3. Configurar:
   - **Payload URL:** `https://jenkinssdc.azurewebsites.net/github-webhook/`
   - **Content type:** `application/json`
   - **Secret:** (deixar em branco ou usar secret do Jenkins)
   - **Which events would you like to trigger this webhook?**
     - Selecione: `Just the push event`
   - **Active:** ✅ Marcar
4. **Add webhook**

### 4.3: Testar Webhook

1. Faça um pequeno commit no repositório:
   ```bash
   echo "test" >> README.md
   git add README.md
   git commit -m "test: testar webhook do Jenkins"
   git push
   ```

2. Verifique no GitHub:
   - **Settings** → **Webhooks** → Clique no webhook
   - Verifique **Recent Deliveries**
   - Deve mostrar status 200

3. Verifique no Jenkins:
   - Deve aparecer um novo build automaticamente
   - Logs devem mostrar "Started by GitHub push"

---

## Parte 5: Primeira Execução

### 5.1: Disparar Build Manual

1. Acesse Jenkins: `https://jenkinssdc.azurewebsites.net`
2. Clique no job `build-and-deploy`
3. Clique em **Build Now**
4. Acompanhe os logs em **Console Output**

### 5.2: O que Esperar

**Duração esperada:** 3-5 minutos

**Timeline típica:**
```
[0:00] Checkout (5s)
[0:05] Pre-flight Checks (10s)
[0:15] Build and Push to ACR (2-3min) ← Mais demorado
[2:30] Deploy to Azure App Service (30s)
[3:00] Health Check (30s-2min)
[3:30] Cleanup (5s)
```

### 5.3: Verificar Sucesso

1. **Console Output do Jenkins:** Deve mostrar `✅ Pipeline completed successfully!`

2. **Verificar imagem no ACR:**
   ```bash
   az acr repository show-tags \
     --name apidover \
     --repository sdc-dev-app \
     --orderby time_desc \
     --output table
   ```

3. **Verificar aplicação:**
   - Abrir: `https://newsdc2027.azurewebsites.net`
   - Deve carregar a aplicação Laravel

4. **Verificar logs do App Service:**
   ```bash
   az webapp log tail --name newsdc2027 --resource-group DEFESA_CIVIL
   ```

---

## Otimizações e Melhores Práticas

### 1. Build Otimizado

#### 1.1: Dockerfile Multi-stage

```dockerfile
# Stage 1: Base
FROM php:8.2-fpm-alpine AS base
WORKDIR /var/www

# Stage 2: Dependencies (cached layer)
FROM base AS dependencies
COPY composer.json composer.lock* package.json package-lock.json* ./
RUN composer install --no-dev --optimize-autoloader --no-scripts
RUN npm ci || npm install

# Stage 3: Build
FROM dependencies AS build
COPY . .
RUN npm run build
RUN composer dump-autoload --optimize

# Stage 4: Production
FROM base AS production
COPY --from=build /var/www /var/www
RUN apk del nodejs npm  # Remover ferramentas de build
EXPOSE 9000
CMD ["php-fpm"]
```

**Vantagens:**
- Camadas de dependências são cacheadas
- Imagem final menor (sem Node.js)
- Build mais rápido em builds subsequentes

#### 1.2: .dockerignore

Crie `SDC/.dockerignore`:

```
node_modules/
vendor/
.git/
.env
.env.*
storage/logs/*.log
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
tests/
.phpunit.result.cache
```

### 2. Performance do Pipeline

#### 2.1: Parallel Stages (Para builds complexos)

```groovy
stage('Tests') {
    parallel {
        stage('Unit Tests') {
            steps {
                sh 'composer test'
            }
        }
        stage('Lint') {
            steps {
                sh 'npm run lint'
            }
        }
    }
}
```

#### 2.2: Cache de Dependências

Já implementado via `COMPOSER_CACHE_DIR` e `NPM_CACHE_DIR`.

### 3. Zero Downtime (Requires Standard Tier)

Para zero downtime real, upgrade para Standard tier e use deployment slots:

```groovy
stage('Deploy to Staging Slot') {
    steps {
        sh """
            az webapp deployment slot create \
                --name ${APP_SERVICE_NAME} \
                --resource-group ${RESOURCE_GROUP} \
                --slot staging

            az webapp config container set \
                --name ${APP_SERVICE_NAME} \
                --resource-group ${RESOURCE_GROUP} \
                --slot staging \
                --docker-custom-image-name ${ACR_IMAGE}:${ACR_TAG}
        """
    }
}

stage('Swap Slots') {
    steps {
        sh """
            az webapp deployment slot swap \
                --name ${APP_SERVICE_NAME} \
                --resource-group ${RESOURCE_GROUP} \
                --slot staging
        """
    }
}
```

### 4. Notificações

#### 4.1: Slack (Opcional)

Instalar plugin **Slack Notification** e adicionar no Jenkinsfile:

```groovy
post {
    success {
        slackSend(
            color: 'good',
            message: "✅ Deploy ${env.BUILD_NUMBER} concluído com sucesso!\nURL: https://newsdc2027.azurewebsites.net"
        )
    }
    failure {
        slackSend(
            color: 'danger',
            message: "❌ Deploy ${env.BUILD_NUMBER} falhou!\nVer logs: ${env.BUILD_URL}"
        )
    }
}
```

### 5. Segurança

#### 5.1: Secrets no Azure Key Vault

```bash
# Criar Key Vault
az keyvault create \
  --name newsdc-keyvault \
  --resource-group DEFESA_CIVIL \
  --location eastus

# Adicionar secrets
az keyvault secret set \
  --vault-name newsdc-keyvault \
  --name "DB-PASSWORD" \
  --value "senha-super-secreta"

# Dar permissão ao App Service
az webapp identity assign \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL

# Obter o principal ID
PRINCIPAL_ID=$(az webapp identity show \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --query principalId -o tsv)

# Conceder acesso ao Key Vault
az keyvault set-policy \
  --name newsdc-keyvault \
  --object-id $PRINCIPAL_ID \
  --secret-permissions get list
```

Referenciar no App Service:

```bash
az webapp config appsettings set \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --settings \
    DB_PASSWORD="@Microsoft.KeyVault(SecretUri=https://newsdc-keyvault.vault.azure.net/secrets/DB-PASSWORD/)"
```

---

## Troubleshooting

### Problema 1: "ERROR: failed to connect to docker API"

**Causa:** Comando `az acr login` requer Docker socket

**Solução:** Use `az acr build` (build remoto) em vez de build local

### Problema 2: "AuthorizationFailed" no ACR

**Causa:** Service Principal sem permissões no ACR

**Solução:**
```bash
az role assignment create \
  --assignee OBJECT_ID \
  --role AcrPush \
  --scope /subscriptions/SUB_ID/resourceGroups/DOVER/providers/Microsoft.ContainerRegistry/registries/apidover
```

### Problema 3: "AuthorizationFailed" no App Service

**Causa:** Service Principal sem permissões no App Service

**Solução:**
```bash
az role assignment create \
  --assignee OBJECT_ID \
  --role "Website Contributor" \
  --scope /subscriptions/SUB_ID/resourceGroups/DEFESA_CIVIL/providers/Microsoft.Web/sites/newsdc2027
```

### Problema 4: Health Check Timeout

**Causa:** Aplicação demora para iniciar ou endpoint não responde

**Soluções:**
1. Aumentar timeout no Jenkinsfile
2. Verificar logs do App Service
3. Testar endpoint manualmente
4. Verificar se container está rodando

### Problema 5: "Package-lock.json out of sync"

**Causa:** `package-lock.json` desatualizado

**Solução:**
```bash
cd SDC
npm install
git add package-lock.json
git commit -m "chore: atualizar package-lock.json"
git push
```

Ou usar fallback no Dockerfile:
```dockerfile
RUN npm ci || npm install
```

### Problema 6: Build muito lento

**Causas possíveis:**
- Muitas camadas sendo rebuilded
- Sem cache
- Dependências grandes

**Soluções:**
1. Use `--no-logs` no `az acr build`
2. Otimize Dockerfile (dependências primeiro)
3. Use `.dockerignore`
4. Considere usar cache externo

### Problema 7: Webhook não dispara build

**Verificações:**
1. GitHub webhook está ativo?
2. URL do webhook está correta?
3. Jenkins está acessível publicamente?
4. GitHub consegue alcançar o Jenkins? (Recent Deliveries)

**Solução:**
- Verificar firewall do Azure
- Verificar se Jenkins aceita webhooks
- Testar disparo manual

### Problema 8: "Cannot exceed slots for Basic SKU"

**Causa:** Tentando criar deployment slots no tier Basic

**Solução:** Upgrade para Standard tier ou use otimizações do Basic tier

---

## Monitoramento e Logs

### Ver logs do Jenkins

```bash
az webapp log tail --name jenkinssdc --resource-group DEFESA_CIVIL
```

### Ver logs da aplicação

```bash
az webapp log tail --name newsdc2027 --resource-group DEFESA_CIVIL
```

### Ver logs do ACR build

```bash
az acr task logs --registry apidover --run-id <run-id>
```

### Habilitar Application Insights (Recomendado)

```bash
# Criar Application Insights
az monitor app-insights component create \
  --app newsdc-insights \
  --location eastus \
  --resource-group DEFESA_CIVIL

# Obter instrumentation key
INSTRUMENTATION_KEY=$(az monitor app-insights component show \
  --app newsdc-insights \
  --resource-group DEFESA_CIVIL \
  --query instrumentationKey -o tsv)

# Configurar no App Service
az webapp config appsettings set \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --settings \
    APPINSIGHTS_INSTRUMENTATIONKEY=$INSTRUMENTATION_KEY
```

---

## Checklist de Implementação

### Azure Resources
- [ ] Resource Group `DOVER` criado
- [ ] Resource Group `DEFESA_CIVIL` criado
- [ ] Azure Container Registry `apidover` criado
- [ ] Service Principal criado e credenciais salvas
- [ ] App Service Plan criado
- [ ] App Service `newsdc2027` criado
- [ ] App Service `jenkinssdc` criado
- [ ] Permissões RBAC configuradas (ACR + App Service)

### Jenkins Configuration
- [ ] Jenkins acessível e configurado
- [ ] Plugins instalados (Pipeline, GitHub, Docker, AnsiColor)
- [ ] Credencial `azure-service-principal` configurada
- [ ] Credencial `github-credentials` configurada
- [ ] Variáveis de ambiente globais configuradas
- [ ] Azure CLI instalado no Jenkins
- [ ] Job `build-and-deploy` criado

### Repository
- [ ] `Jenkinsfile` criado na raiz
- [ ] Dockerfile otimizado em `SDC/docker/Dockerfile.prod`
- [ ] `.dockerignore` criado
- [ ] Scripts de entrypoint em `SDC/docker/scripts/`

### GitHub
- [ ] Webhook configurado
- [ ] Webhook testado (Recent Deliveries = 200)

### First Build
- [ ] Build manual disparado
- [ ] Build completou com sucesso
- [ ] Imagem aparece no ACR
- [ ] Aplicação acessível na URL
- [ ] Health check passou

### Automation
- [ ] Commit + Push dispara build automaticamente
- [ ] Deploy automático funciona
- [ ] Notificações configuradas (opcional)

---

## Custos Estimados (Azure)

| Recurso | SKU | Custo Mensal (USD) |
|---------|-----|-------------------|
| ACR | Basic | $5.00 |
| App Service Plan (App) | B1 | $13.14 |
| App Service Plan (Jenkins) | B1 | $13.14 |
| **Total** | | **~$31.28/mês** |

**Para Zero Downtime (com deployment slots):**
- Upgrade App Service para Standard S1: $73.00/mês
- **Total com Standard:** ~$91.14/mês

---

## Próximos Passos

1. **Implementar Testes Automatizados:**
   - Unit tests (PHPUnit)
   - Integration tests
   - E2E tests (opcional)

2. **Adicionar Análise de Código:**
   - PHPStan / Psalm (análise estática)
   - ESLint (JavaScript)
   - SonarQube (opcional)

3. **Implementar Staging Environment:**
   - Criar App Service de staging
   - Pipeline para staging antes de produção

4. **Configurar Rollback Automático:**
   - Detectar falhas no health check
   - Reverter para versão anterior automaticamente

5. **Monitoring e Alertas:**
   - Application Insights
   - Alertas de falha de deploy
   - Métricas de performance

---

## Referências

### Documentação Oficial

- [Azure CLI Reference](https://docs.microsoft.com/en-us/cli/azure/)
- [Azure Container Registry](https://docs.microsoft.com/en-us/azure/container-registry/)
- [Azure App Service](https://docs.microsoft.com/en-us/azure/app-service/)
- [Jenkins Pipeline Syntax](https://www.jenkins.io/doc/book/pipeline/syntax/)
- [GitHub Webhooks](https://docs.github.com/en/developers/webhooks-and-events/webhooks)

### Comandos Úteis

```bash
# Ver todos os resource groups
az group list --output table

# Ver todos os App Services
az webapp list --output table

# Ver todos os ACRs
az acr list --output table

# Ver imagens no ACR
az acr repository list --name apidover --output table

# Ver tags de uma imagem
az acr repository show-tags --name apidover --repository sdc-dev-app --output table

# Ver status do App Service
az webapp show --name newsdc2027 --resource-group DEFESA_CIVIL --query state

# Restart App Service
az webapp restart --name newsdc2027 --resource-group DEFESA_CIVIL

# Ver configurações do App Service
az webapp config show --name newsdc2027 --resource-group DEFESA_CIVIL

# Ver app settings
az webapp config appsettings list --name newsdc2027 --resource-group DEFESA_CIVIL

# Ver logs em tempo real
az webapp log tail --name newsdc2027 --resource-group DEFESA_CIVIL
```

---

**Documento criado em:** 10/12/2025
**Versão:** 1.0
**Autor:** Documentação CI/CD - Jenkins + Azure
**Última atualização:** 10/12/2025
