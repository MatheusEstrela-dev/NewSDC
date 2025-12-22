# 🔍 Problema: Jenkins Build Concluído mas Deploy Não Aplicado

## 📋 Diagnóstico

O Jenkins **concluiu o build com sucesso**, mas o **deploy não foi executado** porque o stage "Deploy to Azure App Service" foi pulado.

### Log do Jenkins:
```
Stage "Deploy to Azure App Service" skipped due to when conditional
```

## 🔍 Causa Raiz

No `Jenkinsfile` (linhas 163-169), o deploy só executa para branches `main` ou `master`:

```groovy
stage('Deploy to Azure App Service') {
    when {
        anyOf {
            branch 'main'
            branch 'master'
        }
    }
```

**Se você está em outra branch** (como `feat/rat-api`, `develop`, etc.), o deploy não será executado automaticamente.

## ✅ Soluções

### Solução 1: Fazer Merge para Main/Master (Recomendado)

1. Fazer merge da sua branch para `main` ou `master`:
   ```bash
   git checkout main
   git merge sua-branch
   git push origin main
   ```

2. O Jenkins detectará o push em `main` e executará o deploy automaticamente.

### Solução 2: Modificar Jenkinsfile para Permitir Deploy de Qualquer Branch

Modifique o `Jenkinsfile` para permitir deploy de qualquer branch (útil para desenvolvimento):

```groovy
stage('Deploy to Azure App Service') {
    when {
        // Permitir deploy de qualquer branch (remover restrição)
        // anyOf {
        //     branch 'main'
        //     branch 'master'
        // }
        // OU adicionar sua branch específica:
        anyOf {
            branch 'main'
            branch 'master'
            branch 'feat/rat-api'  // Adicione sua branch aqui
        }
    }
```

### Solução 3: Deploy Manual via Azure CLI

Execute o deploy manualmente usando Azure CLI:

```bash
# Login no Azure
az login

# Fazer login no ACR
az acr login --name apidover

# Atualizar App Service com a imagem mais recente
az webapp config container set \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL \
    --docker-custom-image-name apidover.azurecr.io/sdc-dev-app:latest \
    --docker-registry-server-url https://apidover.azurecr.io \
    --docker-registry-server-user <usuario-acr> \
    --docker-registry-server-password <senha-acr>

# Reiniciar App Service
az webapp restart \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL
```

## 📊 Status Atual

✅ **Build:** Concluído com sucesso  
✅ **Imagem Docker:** Buildada e enviada para ACR  
✅ **Assets:** Compilados com sucesso (Vite)  
❌ **Deploy:** Não executado (branch não é main/master)  

## 🔧 Verificação Rápida

Para verificar qual branch você está usando:

```bash
git branch
# ou
git status
```

## 🎯 Próximos Passos

1. **Verificar branch atual**
2. **Escolher uma solução** (merge para main OU modificar Jenkinsfile)
3. **Aguardar deploy** (2-5 minutos após push)
4. **Verificar alterações visuais** no dashboard e login

---

**Data:** {{ date('d/m/Y H:i:s') }}  
**Status:** ⚠️ Deploy não executado - Branch não é main/master













