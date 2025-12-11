# 🚀 CI/CD Totalmente Automático - SDC

## ✅ Configuração Completa para Deploy Automático

### Fluxo Automático Completo

```
┌─────────────┐
│   GitHub    │
│   (Push)    │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Webhook    │ ────▶ Dispara automaticamente
│  (GitHub)   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Jenkins   │ ────▶ Build automático
│  Pipeline   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Build Image │ ────▶ Docker build
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Push to ACR │ ────▶ apidover.azurecr.io
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ App Service │ ────▶ Deploy automático
│  (Azure)    │       SEM aprovação manual!
└─────────────┘
```

## 🔧 Passo 1: Configurar Webhook do GitHub

### Opção A: Script Automático (Recomendado)

**Windows (PowerShell):**
```powershell
cd SDC/docker/azure-app-service

# Obter token do GitHub:
# GitHub → Settings → Developer settings → Personal access tokens → Generate new token
# Permissões: repo, admin:repo_hook

.\configurar-webhook-github.ps1 `
    -Repo "seu-usuario/New_SDC" `
    -Token "ghp_seu_token_aqui" `
    -JenkinsUrl "http://localhost:8090"
```

**Linux/Mac:**
```bash
cd SDC/docker/azure-app-service
chmod +x configurar-webhook-github.sh

./configurar-webhook-github.sh \
    -r "seu-usuario/New_SDC" \
    -t "ghp_seu_token_aqui" \
    -j "http://localhost:8090"
```

### Opção B: Manual no GitHub

1. Acesse: `https://github.com/seu-usuario/New_SDC/settings/hooks`
2. Clique em **Add webhook**
3. Configure:
   - **Payload URL**: `http://seu-ip:8090/github-webhook/`
   - **Content type**: `application/json`
   - **Events**: ✅ **Just the push event**
   - **Active**: ✅

## 🔧 Passo 2: Configurar Jenkins para Receber Webhooks

### 2.1. Instalar Plugins

1. Acesse: `http://localhost:8090`
2. **Manage Jenkins** → **Manage Plugins**
3. Instale:
   - ✅ **GitHub Plugin**
   - ✅ **GitHub Branch Source Plugin**

### 2.2. Configurar GitHub Server

1. **Manage Jenkins** → **Configure System**
2. Role até **GitHub**
3. Clique em **Add GitHub Server**
4. Configure:
   - **Name**: `GitHub`
   - **API URL**: `https://api.github.com`
   - **Credentials**: Adicione token do GitHub
5. **Advanced** → **Shared secret**: Cole o secret gerado pelo script
6. **Test connection**

### 2.3. Configurar Job no Jenkins

1. Crie um novo **Pipeline** job
2. Nome: `SDC-CI-CD`
3. **Pipeline** → **Definition**: Pipeline script from SCM
4. **SCM**: Git
5. **Repository URL**: URL do seu repositório
6. **Credentials**: Adicione credenciais do GitHub
7. **Branch**: `*/main` ou `*/master`
8. **Script Path**: `Jenkinsfile`
9. **Build Triggers**:
   - ✅ **GitHub hook trigger for GITScm polling**

## 🔧 Passo 3: Verificar que Está Funcionando

### 3.1. Teste Manual

```bash
# Fazer um commit vazio para testar
git commit --allow-empty -m "test: Trigger CI/CD automático"
git push origin main
```

### 3.2. Verificar no Jenkins

1. Acesse: `http://localhost:8090/job/SDC-CI-CD/`
2. Você deve ver um build iniciando automaticamente
3. Nos logs, deve aparecer: `Started by GitHub push by usuario`

### 3.3. Verificar Deploy

```bash
# Ver status do App Service
az webapp show --name sdc-app --resource-group sdc-rg --query state

# Ver logs
az webapp log tail --name sdc-app --resource-group sdc-rg

# Testar URL
curl https://sdc-app.azurewebsites.net/health
```

## ✅ Checklist de Verificação

- [ ] Webhook configurado no GitHub
- [ ] Plugins GitHub instalados no Jenkins
- [ ] GitHub Server configurado no Jenkins
- [ ] Job configurado com "GitHub hook trigger"
- [ ] Jenkinsfile sem aprovações manuais
- [ ] Variáveis de ambiente configuradas no Jenkins
- [ ] App Service criado no Azure
- [ ] Teste de push funcionando
- [ ] Build inicia automaticamente
- [ ] Deploy acontece automaticamente

## 🎯 Resultado Esperado

**Ao fazer um push no GitHub:**

1. ✅ Webhook dispara automaticamente
2. ✅ Jenkins inicia build automaticamente
3. ✅ Build da imagem Docker
4. ✅ Push para ACR automaticamente
5. ✅ Deploy no App Service automaticamente
6. ✅ **TUDO SEM INTERVENÇÃO MANUAL!**

## 🐛 Troubleshooting

### Webhook não dispara

```bash
# Verificar se Jenkins está acessível
curl http://localhost:8090/github-webhook/

# Ver logs do Jenkins
docker logs sdc_jenkins_dev | grep webhook

# Verificar webhooks no GitHub
# GitHub → Settings → Webhooks → Ver "Recent Deliveries"
```

### Build não inicia automaticamente

1. Verifique se o job tem "GitHub hook trigger" habilitado
2. Verifique se o branch está correto (main/master)
3. Verifique logs do Jenkins: `docker logs sdc_jenkins_dev`

### Deploy falha

```bash
# Verificar credenciais do Azure
az account show

# Verificar acesso ao ACR
az acr login --name apidover

# Ver logs do App Service
az webapp log tail --name sdc-app --resource-group sdc-rg
```

---

## 🚀 Pronto!

Agora seu CI/CD está **100% AUTOMÁTICO**! 

Faça um push e veja a mágica acontecer! ✨




