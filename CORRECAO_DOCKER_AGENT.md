# ✅ Correção Final - Docker Agent para Azure App Service

## 🎯 Problema Identificado

**Erro:**
```
failed to connect to the docker API at unix:///var/run/docker.sock
```

**Causa Raiz:**
- Jenkinsfile usava `agent { docker { ... } }` com mount do Docker socket
- Azure App Service **não expõe** `/var/run/docker.sock` por segurança
- Pipeline não conseguia inicializar o agente Docker

---

## ✅ Solução Aplicada

**Commit:** `a619bd3` - "fix: alterar agent Docker para 'any'"

**Mudança no Jenkinsfile (linhas 1-4):**

**❌ Antes:**
```groovy
pipeline {
    agent {
        docker {
            image 'php:8.2-cli'
            args '-v /var/run/docker.sock:/var/run/docker.sock --network sdc_network'
        }
    }
}
```

**✅ Depois:**
```groovy
pipeline {
    // Usar agente padrão do Jenkins (Azure App Service não expõe Docker socket)
    // O container Jenkins já possui Docker, Docker Compose, Azure CLI e ferramentas necessárias
    agent any
}
```

---

## 🏗️ Arquitetura dos App Services

### 1. Jenkins CI/CD (jenkinssdc)
**URL:** https://jenkinssdc.azurewebsites.net/

**Função:**
- Executar pipeline CI/CD
- Build de imagens Docker
- Push para Azure Container Registry (apidover.azurecr.io)
- Deploy automático para produção

**Container:**
- Imagem custom do Jenkins com Docker, Azure CLI, Node.js
- Possui todas as ferramentas necessárias para o pipeline

### 2. Aplicação SDC - Produção (newsdc2027)
**URL:** https://newsdc2027.azurewebsites.net/login

**Função:**
- Hospedar a aplicação Laravel em produção
- Receber deploy automático do Jenkins
- Servir usuários finais

**Container:**
- Imagem da aplicação Laravel buildada pelo Jenkins
- Vem do ACR: `apidover.azurecr.io/sdc-dev-app:latest`

---

## 🚀 Pipeline Completo Agora

### Fluxo Esperado:

1. **Push GitHub** → Webhook dispara Jenkins
2. **Jenkins (jenkinssdc)** executa pipeline:
   - ✅ Checkout do código
   - ✅ Pre-flight checks
   - ✅ Build da imagem Docker da aplicação
   - ✅ Verify build
   - ✅ Tag e Push para ACR (`apidover.azurecr.io/sdc-dev-app`)
   - ✅ Deploy para App Service (newsdc2027)
3. **Produção (newsdc2027)** atualizada automaticamente

**Tempo estimado:** 10-25 minutos

---

## 📊 Monitorar Execução

### 1. Verificar Build no Jenkins

**Console Output:**
```
https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
```

**Resultado esperado:**
```
Started by GitHub push by MatheusEstrela-dev
Checking out git https://github.com/MatheusEstrela-dev/NewSDC.git
✅ Checking out Revision a619bd3...
✅ Loading Jenkinsfile from SDC/Jenkinsfile
✅ [Pipeline] Start of Pipeline
✅ [Pipeline] node
Running on Jenkins in /var/jenkins_home/workspace/SDC/build-and-deploy
✅ [Pipeline] stage { (Checkout)
✅ [Pipeline] stage { (Pre-flight Checks)
✅ [Pipeline] stage { (Build Docker Images)
    docker build -f docker/Dockerfile.prod -t sdc-dev-app:latest ...
✅ [Pipeline] stage { (Verify Build)
✅ [Pipeline] stage { (Tag and Push to ACR)
    Pushing to apidover.azurecr.io/sdc-dev-app:latest...
✅ [Pipeline] stage { (Deploy to Azure App Service)
    Updating newsdc2027 with new image...
    Restarting App Service...
✅ Finished: SUCCESS
```

### 2. Verificar ACR

```bash
az acr repository show-tags \
  --name apidover \
  --repository sdc-dev-app \
  --output table
```

**Deve mostrar:**
- Tag com número do build: `<build-number>-a619bd3`
- Tag latest: `latest`

### 3. Verificar Produção

**Acesse:** https://newsdc2027.azurewebsites.net/login

**Deve ver:**
- ✅ Página de login carregando
- ✅ Sem erro 503
- ✅ Aplicação funcionando
- ✅ Logs do container mostrando Laravel iniciado

---

## 🔍 Se Algo Der Errado

### Problema: Pipeline falha no stage "Build Docker Images"

**Possíveis causas:**
- Falta de espaço em disco no Jenkins
- Dockerfile.prod com erro

**Verificar:**
```bash
# Ver logs do build no Console Output do Jenkins
# Procurar por erros específicos do Docker build
```

### Problema: Pipeline falha no stage "Tag and Push to ACR"

**Possíveis causas:**
- Credenciais Azure não configuradas
- Service Principal inválido

**Verificar no Jenkins:**
- Variáveis de ambiente: AZURE_CLIENT_ID, AZURE_CLIENT_SECRET, AZURE_TENANT_ID
- Credencial `azure-service-principal` está configurada

### Problema: Pipeline falha no stage "Deploy to Azure App Service"

**Possíveis causas:**
- App Service não encontrado
- Permissões insuficientes do Service Principal

**Verificar:**
```bash
# Confirmar que App Service existe
az webapp show --name newsdc2027 --resource-group DEFESA_CIVIL

# Confirmar que Service Principal tem permissão
az role assignment list \
  --assignee <AZURE_CLIENT_ID> \
  --resource-group DEFESA_CIVIL
```

### Problema: Deploy OK mas site não carrega (503)

**Possíveis causas:**
- Container não iniciou corretamente
- Variáveis de ambiente faltando no newsdc2027

**Verificar logs:**
```bash
az webapp log tail \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL
```

---

## 📋 Checklist Final

- [x] Webhook GitHub configurado e funcionando
- [x] Jenkins detectando pushes automaticamente
- [x] Autenticação GitHub (HTTPS + Token) funcionando
- [x] Script Path corrigido (SDC/Jenkinsfile)
- [x] **Docker agent corrigido (agent any)** ← ÚLTIMA CORREÇÃO
- [ ] Pipeline executando todas as stages
- [ ] Push para ACR funcionando
- [ ] Deploy automático para newsdc2027
- [ ] Produção acessível e funcionando

---

## 🎯 Próximos Passos

1. **Aguardar 2-5 minutos** - Webhook processar e iniciar build
2. **Monitorar Console Output** - Ver pipeline executando
3. **Aguardar 10-25 minutos** - Build completo
4. **Verificar ACR** - Imagem foi enviada
5. **Verificar Produção** - Site funcionando

---

**Status:** 🟢 **Correção aplicada! Pipeline deve executar agora!**

**Commit:** `a619bd3`
**Push:** ✅ Realizado
**Webhook:** ⏳ Processando...
**Jenkins Build:** ⏳ Iniciando...

---

## 💡 Lição Aprendida

**Azure App Service Containers:**
- Não expõem Docker socket (`/var/run/docker.sock`) por segurança
- Não podem executar Docker-in-Docker via socket mount
- Solução: usar `agent any` e confiar nas ferramentas já instaladas no container base

**Configuração correta para Azure:**
- ✅ `agent any` - Usa o Jenkins agent diretamente
- ❌ `agent { docker { ... } }` - Não funciona no Azure App Service
