# 🧪 Testar Gatilho CI/CD - GitHub → Jenkins → Produção

## 📊 Status Atual dos Serviços

### ✅ Serviços Online
- **Jenkins**: https://jenkinssdc.azurewebsites.net/
- **Produção (newsdc2027)**: https://newsdc2027.azurewebsites.net/
- **Container**: `apidover.azurecr.io/sdc-dev-app:latest`
- **Resource Group**: DEFESA_CIVIL

### ⚠️ Problema Identificado
O site `newsdc2027` está retornando **503 Service Unavailable**.

**Possíveis causas:**
1. Container não está iniciando corretamente
2. Porta não está configurada corretamente
3. Falta variáveis de ambiente
4. AlwaysOn está desabilitado (pode causar cold start lento)

---

## 🔧 Corrigir Erro 503

### Passo 1: Verificar Logs do Container

```bash
# Ver logs em tempo real
az webapp log tail --name newsdc2027 --resource-group DEFESA_CIVIL

# Ou via portal:
# https://portal.azure.com → newsdc2027 → Monitoring → Log stream
```

### Passo 2: Verificar Variáveis de Ambiente

O container Laravel precisa de variáveis de ambiente configuradas:

```bash
# Ver variáveis atuais
az webapp config appsettings list --name newsdc2027 --resource-group DEFESA_CIVIL --query "[].{Name:name, Value:value}" -o table

# Configurar variáveis essenciais
az webapp config appsettings set --name newsdc2027 --resource-group DEFESA_CIVIL --settings \
  APP_NAME="SDC" \
  APP_ENV="production" \
  APP_KEY="base64:SEU_APP_KEY_AQUI" \
  APP_DEBUG="false" \
  APP_URL="https://newsdc2027.azurewebsites.net" \
  DB_CONNECTION="mysql" \
  DB_HOST="seu-db-host" \
  DB_PORT="3306" \
  DB_DATABASE="sdc_db" \
  DB_USERNAME="sdc_user" \
  DB_PASSWORD="sua-senha" \
  WEBSITES_PORT="8000"
```

### Passo 3: Habilitar AlwaysOn (Recomendado)

```bash
# Habilitar AlwaysOn para evitar cold start
az webapp config set --name newsdc2027 --resource-group DEFESA_CIVIL --always-on true
```

### Passo 4: Verificar Porta do Container

O App Service precisa saber em qual porta o container está escutando:

```bash
# Configurar porta (Laravel geralmente usa 8000)
az webapp config appsettings set --name newsdc2027 --resource-group DEFESA_CIVIL --settings WEBSITES_PORT="8000"

# Ou se o Dockerfile usa porta 80:
az webapp config appsettings set --name newsdc2027 --resource-group DEFESA_CIVIL --settings WEBSITES_PORT="80"
```

### Passo 5: Reiniciar

```bash
az webapp restart --name newsdc2027 --resource-group DEFESA_CIVIL
```

---

## 🧪 Testar Gatilho CI/CD

### Pré-requisitos

1. **Webhook configurado no GitHub:**
   - URL: `https://jenkinssdc.azurewebsites.net/github-webhook/`
   - Events: Push
   - Active: ✅

2. **Job configurado no Jenkins:**
   - Nome: "SDC Application" → "build-and-deploy"
   - Build Trigger: GitHub hook trigger
   - Script: SDC/Jenkinsfile

3. **Credenciais no Jenkins:**
   - `azure-service-principal` configurada
   - `AZURE_TENANT_ID` configurada

### Teste 1: Verificar Webhook do GitHub

```bash
# Fazer um commit de teste
cd c:\Users\kdes\Documentos\GitHub\New_SDC
echo "# Test CI/CD - $(date)" >> README.md
git add README.md
git commit -m "test: Verificar gatilho CI/CD automático"
git push origin main
```

**Verificar no GitHub:**
1. Settings → Webhooks → Seu webhook
2. Recent Deliveries
3. Deve ver uma entrega recente com status 200 (✅)

**Verificar no Jenkins:**
1. Abrir: https://jenkinssdc.azurewebsites.net/
2. Job: "SDC Application" → "build-and-deploy"
3. Deve ver um novo build iniciado
4. Console Output deve mostrar: "Started by GitHub push"

### Teste 2: Verificar Pipeline

**Stages esperadas:**
1. ✅ Checkout
2. ✅ Pre-flight Checks
3. ✅ Build Docker Images
4. ✅ Tag and Push to ACR
5. ✅ Install Dependencies (PHP e Node)
6. ✅ Generate Application Key
7. ✅ Database Setup
8. ✅ Build Frontend Assets
9. ✅ Code Quality
10. ✅ Run Tests
11. ✅ Security Scan
12. ✅ Cache Optimization
13. ✅ **Deploy to Azure App Service** (apenas em main/master)

**Verificar logs:**
- Stage "Tag and Push to ACR": Deve fazer login e push
- Stage "Deploy to Azure App Service": Deve atualizar newsdc2027

### Teste 3: Verificar Imagem no ACR

```bash
# Ver imagens no ACR
az acr repository show-tags --name apidover --repository sdc-dev-app --output table
```

Deve mostrar tags como:
```
Result
-----------
latest
1-abc1234  ← Build #1
2-def5678  ← Build #2
```

### Teste 4: Verificar Atualização na Produção

```bash
# Verificar qual imagem está rodando
az webapp config container show --name newsdc2027 --resource-group DEFESA_CIVIL --query "linux.dockerImage" -o tsv

# Deve mostrar algo como:
# apidover.azurecr.io/sdc-dev-app:2-def5678
```

**Verificar via web:**
1. Abrir: https://newsdc2027.azurewebsites.net/
2. Deve ver a aplicação rodando
3. Mudanças do commit devem estar visíveis

---

## 🔍 Fluxo Completo do CI/CD

```
┌─────────────┐
│  Você faz   │
│  git push   │
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────┐
│  GitHub Webhook dispara Jenkins │
└──────┬──────────────────────────┘
       │
       ▼
┌────────────────────────────────────┐
│  Jenkins Pipeline (Jenkinsfile)   │
│  1. Checkout código                │
│  2. Build Docker image             │
│  3. Login no ACR                   │
│  4. Push para ACR (tag: BUILD#)    │
│  5. Deploy para Azure App Service │
└──────┬─────────────────────────────┘
       │
       ▼
┌──────────────────────────────────┐
│  ACR: apidover.azurecr.io        │
│  Imagem: sdc-dev-app:BUILD#      │
└──────┬───────────────────────────┘
       │
       ▼
┌────────────────────────────────────┐
│  Azure App Service: newsdc2027     │
│  Atualiza para nova imagem         │
│  Reinicia automaticamente          │
└──────┬─────────────────────────────┘
       │
       ▼
┌──────────────────────────────┐
│  Produção atualizada! 🚀     │
│  https://newsdc2027...       │
└──────────────────────────────┘
```

---

## 📝 Verificar Configuração do Jenkinsfile

O [Jenkinsfile](SDC/Jenkinsfile) já tem a stage de deploy automático:

**Linhas 387-452:**
```groovy
stage('Deploy to Azure App Service') {
    when {
        anyOf {
            branch 'main'
            branch 'master'
        }
    }
    steps {
        // Atualiza App Service com nova imagem
        // Reinicia App Service
        // Health check
    }
}
```

**Variáveis necessárias:**
- `AZURE_APP_SERVICE_NAME` → Configurar como: `newsdc2027`
- `AZURE_RESOURCE_GROUP` → Configurar como: `DEFESA_CIVIL`

### Configurar Variáveis no Jenkins

1. Jenkins → **Manage Jenkins** → **Configure System**
2. **Global properties** → **Environment variables**
3. Adicionar:
   - Name: `AZURE_APP_SERVICE_NAME`, Value: `newsdc2027`
   - Name: `AZURE_RESOURCE_GROUP`, Value: `DEFESA_CIVIL`

Ou configurar no job específico:
1. Job → **Configure**
2. **Pipeline** → **Environment**
3. Adicionar variáveis

---

## 🐛 Troubleshooting

### Erro 503 no newsdc2027

**Sintoma:** Site retorna Service Unavailable

**Soluções:**

1. **Verificar logs:**
   ```bash
   az webapp log tail --name newsdc2027 --resource-group DEFESA_CIVIL
   ```

2. **Verificar porta:**
   ```bash
   az webapp config appsettings set --name newsdc2027 --resource-group DEFESA_CIVIL --settings WEBSITES_PORT="8000"
   ```

3. **Verificar variáveis de ambiente:**
   ```bash
   # APP_KEY é obrigatório no Laravel
   az webapp config appsettings list --name newsdc2027 --resource-group DEFESA_CIVIL | grep APP_KEY
   ```

4. **Habilitar AlwaysOn:**
   ```bash
   az webapp config set --name newsdc2027 --resource-group DEFESA_CIVIL --always-on true
   ```

5. **Verificar health endpoint:**
   ```bash
   # O Jenkinsfile verifica /health
   curl https://newsdc2027.azurewebsites.net/health
   ```

### Webhook não dispara build

**Solução:**
1. GitHub → Settings → Webhooks → Recent Deliveries
2. Verificar se status é 200
3. Se 403/404, verificar URL: `https://jenkinssdc.azurewebsites.net/github-webhook/`

### Build não faz deploy

**Causa:** Variáveis `AZURE_APP_SERVICE_NAME` ou `AZURE_RESOURCE_GROUP` não configuradas

**Solução:**
1. Configurar variáveis no Jenkins (ver acima)
2. Ou editar Jenkinsfile para usar valores fixos:
   ```groovy
   def APP_SERVICE_NAME = 'newsdc2027'
   def RESOURCE_GROUP = 'DEFESA_CIVIL'
   ```

---

## ✅ Checklist de Validação

### Webhook GitHub → Jenkins
- [ ] Webhook configurado no GitHub
- [ ] URL: `https://jenkinssdc.azurewebsites.net/github-webhook/`
- [ ] Recent Deliveries mostra status 200
- [ ] Build inicia automaticamente no Jenkins

### Jenkins Pipeline
- [ ] Credencial `azure-service-principal` configurada
- [ ] Variável `AZURE_TENANT_ID` configurada
- [ ] Variável `AZURE_APP_SERVICE_NAME` = `newsdc2027`
- [ ] Variável `AZURE_RESOURCE_GROUP` = `DEFESA_CIVIL`
- [ ] Build completa todas as stages
- [ ] Stage "Tag and Push to ACR" funciona
- [ ] Stage "Deploy to Azure App Service" executa (apenas em main)

### ACR
- [ ] Imagem `sdc-dev-app` existe
- [ ] Nova tag criada a cada build
- [ ] Tag `latest` sempre aponta para última build

### Produção (newsdc2027)
- [ ] App Service está "Running"
- [ ] AlwaysOn habilitado
- [ ] Variáveis de ambiente configuradas
- [ ] WEBSITES_PORT correto
- [ ] Site responde (não 503)
- [ ] Mudanças do commit estão visíveis

---

## 🎯 Resultado Esperado

**Após fazer um push no GitHub:**
1. ⏱️ ~30 segundos: Webhook dispara Jenkins
2. ⏱️ ~5-10 minutos: Pipeline executa
3. ⏱️ ~2-3 minutos: Deploy para produção
4. ✅ Site atualizado automaticamente!

**Total: ~8-15 minutos do push até produção**

---

<div align="center">

**🧪 Teste do Gatilho CI/CD Completo**

*Data: 2025-12-08*

**Push → Jenkins → ACR → Produção**

</div>
