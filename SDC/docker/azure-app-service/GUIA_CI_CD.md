# 🚀 Guia de CI/CD - SDC

## ✅ Como Garantir que o CI/CD está Funcionando

### 1. **Verificação Inicial**

Execute o script de verificação:

```bash
cd SDC/docker/azure-app-service
chmod +x verificar-cicd.sh
./verificar-cicd.sh
```

O script verifica:
- ✅ Ferramentas instaladas (Docker, Azure CLI, Git)
- ✅ Autenticação no Azure
- ✅ Acesso ao ACR (Azure Container Registry)
- ✅ Status do App Service
- ✅ Jenkins acessível
- ✅ Containers Docker rodando
- ✅ Rede Docker configurada
- ✅ Variáveis de ambiente

### 2. **Configurar Variáveis de Ambiente no Jenkins**

No Jenkins, configure as seguintes variáveis globais:

**Gerenciar Jenkins → Configurar o Sistema → Variáveis de Ambiente Globais**

```bash
# Azure
AZURE_CLIENT_ID=seu-client-id
AZURE_CLIENT_SECRET=seu-client-secret
AZURE_TENANT_ID=seu-tenant-id

# App Service
AZURE_APP_SERVICE_NAME=sdc-app
AZURE_RESOURCE_GROUP=sdc-rg

# ACR
ACR_NAME=apidover
ACR_LOGIN_SERVER=apidover.azurecr.io
```

### 3. **Criar App Service no Azure**

Execute o script de criação:

**Windows (PowerShell):**
```powershell
cd SDC/docker/azure-app-service
.\create-app-service.ps1 `
    -ResourceGroup "sdc-rg" `
    -AppName "sdc-app" `
    -PlanName "sdc-plan"
```

**Linux/Mac:**
```bash
cd SDC/docker/azure-app-service
chmod +x create-app-service.sh
./create-app-service.sh \
    -g sdc-rg \
    -n sdc-app \
    -p sdc-plan
```

### 4. **Testar o Pipeline Jenkins**

#### 4.1. Criar Job no Jenkins

1. **Novo Item** → **Pipeline**
2. Nome: `SDC-CI-CD`
3. **Pipeline** → **Definition**: Pipeline script from SCM
4. **SCM**: Git
5. **Repository URL**: URL do seu repositório
6. **Branch**: `*/main` ou `*/master`
7. **Script Path**: `Jenkinsfile`

#### 4.2. Executar Build Manual

1. Clique em **Build Now**
2. Acompanhe os logs em tempo real
3. Verifique cada stage:
   - ✅ Checkout
   - ✅ Pre-flight Checks
   - ✅ Build Docker Images
   - ✅ Push to ACR
   - ✅ Install Dependencies
   - ✅ Database Setup
   - ✅ Build Frontend Assets
   - ✅ Code Quality
   - ✅ Run Tests
   - ✅ Security Scan
   - ✅ Deploy to Azure App Service

#### 4.3. Verificar Deploy

Após o deploy, verifique:

```bash
# Verificar status do App Service
az webapp show --name sdc-app --resource-group sdc-rg --query state

# Ver logs
az webapp log tail --name sdc-app --resource-group sdc-rg

# Testar URL
curl https://sdc-app.azurewebsites.net/health
```

### 5. **Configurar Webhook do GitHub**

Para CI/CD automático ao fazer push:

1. **GitHub** → **Settings** → **Webhooks** → **Add webhook**
2. **Payload URL**: `http://seu-jenkins:8090/github-webhook/`
3. **Content type**: `application/json`
4. **Events**: `Just the push event`
5. **Active**: ✅

### 6. **Fluxo Completo de CI/CD**

```
┌─────────────┐
│   GitHub    │
│   (Push)    │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Webhook    │
│  (Trigger)  │
└──────┬──────┘
       │
       ▼
┌─────────────┐      ┌──────────────┐
│   Jenkins   │─────▶│  Build Image │
│  Pipeline   │      │  (Docker)    │
└──────┬──────┘      └──────┬───────┘
       │                    │
       │                    ▼
       │            ┌──────────────┐
       │            │  Push to ACR │
       │            │  (Registry)  │
       │            └──────┬───────┘
       │                   │
       │                   ▼
       │            ┌──────────────┐
       └───────────▶│ App Service  │
                    │  (Deploy)    │
                    └──────────────┘
```

### 7. **Troubleshooting**

#### Problema: Build falha no Jenkins

**Solução:**
```bash
# Verificar logs do container Jenkins
docker logs sdc_jenkins_dev

# Verificar espaço em disco
df -h

# Limpar imagens antigas
docker image prune -a
```

#### Problema: Push para ACR falha

**Solução:**
```bash
# Fazer login manual no ACR
az acr login --name apidover

# Verificar credenciais
az acr credential show --name apidover
```

#### Problema: App Service não atualiza

**Solução:**
```bash
# Forçar restart
az webapp restart --name sdc-app --resource-group sdc-rg

# Verificar imagem atual
az webapp config container show \
    --name sdc-app \
    --resource-group sdc-rg
```

#### Problema: Pipeline não é acionado pelo webhook

**Solução:**
1. Verificar se o webhook está ativo no GitHub
2. Verificar logs do Jenkins: `docker logs sdc_jenkins_dev | grep webhook`
3. Testar webhook manualmente:
   ```bash
   curl -X POST http://localhost:8090/github-webhook/ \
     -H "Content-Type: application/json" \
     -d '{"ref":"refs/heads/main"}'
   ```

### 8. **Monitoramento**

#### 8.1. Status do Pipeline

- Acesse: `http://localhost:8090/job/SDC-CI-CD/`
- Veja histórico de builds
- Verifique duração e status de cada stage

#### 8.2. Logs do App Service

```bash
# Logs em tempo real
az webapp log tail --name sdc-app --resource-group sdc-rg

# Logs de deploy
az webapp deployment list --name sdc-app --resource-group sdc-rg
```

#### 8.3. Métricas do App Service

```bash
# CPU e Memória
az monitor metrics list \
    --resource /subscriptions/{sub-id}/resourceGroups/sdc-rg/providers/Microsoft.Web/sites/sdc-app \
    --metric "CpuPercentage,MemoryPercentage"
```

### 9. **Checklist de Verificação**

Antes de considerar o CI/CD funcionando, verifique:

- [ ] Jenkins está acessível
- [ ] Pipeline configurado no Jenkins
- [ ] Build manual executado com sucesso
- [ ] Imagens sendo enviadas para o ACR
- [ ] App Service criado e configurado
- [ ] Deploy automático funcionando
- [ ] Webhook do GitHub configurado
- [ ] Teste de push no GitHub aciona o pipeline
- [ ] App Service atualiza após deploy
- [ ] Health check do App Service passando
- [ ] Logs sendo coletados corretamente

### 10. **Comandos Úteis**

```bash
# Verificar status completo
./verificar-cicd.sh

# Criar App Service
./create-app-service.sh -g sdc-rg -n sdc-app -p sdc-plan

# Atualizar App Service manualmente
az webapp config container set \
    --name sdc-app \
    --resource-group sdc-rg \
    --docker-custom-image-name apidover.azurecr.io/sdc-dev-app:latest

# Ver logs do Jenkins
docker logs -f sdc_jenkins_dev

# Ver logs do App Service
az webapp log tail --name sdc-app --resource-group sdc-rg

# Testar pipeline localmente
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock \
    -v $(pwd):/workspace \
    -w /workspace \
    jenkins/jenkins:lts \
    sh -c "jenkinsfile-runner"
```

---

## 🎯 Resumo

Para garantir que o CI/CD está funcionando:

1. ✅ Execute `verificar-cicd.sh` para diagnóstico
2. ✅ Configure variáveis de ambiente no Jenkins
3. ✅ Crie o App Service no Azure
4. ✅ Execute um build manual no Jenkins
5. ✅ Configure webhook do GitHub
6. ✅ Faça um push de teste
7. ✅ Verifique se o App Service atualizou

**Status esperado:** Pipeline executa automaticamente a cada push, faz build, testa, envia para ACR e faz deploy no App Service! 🚀




