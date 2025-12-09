# 🧪 Teste de CI/CD - Deploy Automático

## ✅ Alteração Realizada

**Arquivo modificado:** `SDC/resources/js/Pages/Auth/Login.vue`

**Mudança:**
- Adicionado ✅ no final do texto "CI/CD Test - Deploy Automático"
- Texto anterior: `CI/CD Test - Deploy Automático`
- Texto novo: `CI/CD Test - Deploy Automático ✅`

**Commit:** `269d7b9`
**Mensagem:** `test: CI/CD - alteração mínima no footer do login para verificar deploy automático`

**Arquivos incluídos no commit:**
- `SDC/resources/js/Pages/Auth/Login.vue` (alteração no footer)
- `SDC/Jenkinsfile` (otimizações do pipeline)
- `SDC/docker/jenkins/casc.yaml` (correção da URL do repositório)

---

## 🚀 Status do Push

```
✅ Commit criado: 269d7b9
✅ Push realizado para: origin/main
✅ Branch: main
```

O webhook do GitHub deve ter disparado o Jenkins automaticamente.

---

## 🔍 Como Verificar se o CI/CD Funcionou

### 1. Verificar Build no Jenkins

**URL:** https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/

**O que verificar:**
- ✅ Deve haver um build novo (número mais alto)
- ✅ Status deve ser **SUCCESS** (verde) ou **IN PROGRESS** (azul)
- ✅ Se estiver em progresso, aguarde a conclusão

**Stages esperadas:**
1. ✅ Checkout
2. ✅ Pre-flight Checks
3. ✅ Build Docker Images
4. ✅ Verify Build
5. ✅ Tag and Push to ACR
6. ✅ Code Quality & Tests (pode ser pulado em main)
7. ✅ Deploy to Azure App Service

### 2. Verificar Logs do Build

Clique no build mais recente e verifique os logs:

**Checkout:**
```
✅ Deve mostrar: "Checking out code..."
✅ Deve mostrar: "Commit: test: CI/CD - alteração mínima..."
```

**Build Docker Images:**
```
✅ Deve mostrar: "Building Docker images for production..."
✅ Deve mostrar: "Imagem buildada: sdc-dev-app:latest"
✅ Deve mostrar: "Imagem taggeada: apidover.azurecr.io/sdc-dev-app:..."
```

**Tag and Push to ACR:**
```
✅ Deve mostrar: "Login no Azure via Service Principal realizado"
✅ Deve mostrar: "Imagens enviadas para ACR:"
✅ Deve mostrar: "  - apidover.azurecr.io/sdc-dev-app:..."
```

**Deploy to Azure App Service:**
```
✅ Deve mostrar: "Deploying to Azure App Service AUTOMATICALLY..."
✅ Deve mostrar: "Atualizando App Service: newsdc2027"
✅ Deve mostrar: "Reiniciando App Service..."
✅ Deve mostrar: "✅ App Service está respondendo!"
✅ Deve mostrar: "✅ Deploy para Azure App Service concluído!"
```

### 3. Verificar Imagem no ACR

```bash
az acr repository show-tags \
    --name apidover \
    --repository sdc-dev-app \
    --orderby time_desc \
    --output table
```

**O que esperar:**
- Deve aparecer uma nova tag com o número do build
- Exemplo: `1-269d7b9` (build #1, commit 269d7b9)

### 4. Verificar App Service

```bash
# Ver configuração atual do container
az webapp config container show \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL

# Ver logs recentes
az webapp log tail \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL
```

**O que verificar:**
- A imagem deve estar atualizada com a nova tag
- Deve haver logs de reinicialização recente

### 5. Verificar na Tela de Login

**URL:** https://newsdc2027.azurewebsites.net/login

**O que verificar:**
1. Acesse a URL acima
2. Role até o final do card de login
3. Procure o texto no rodapé:
   ```
   © 2025 Governo do Estado de Minas Gerais
   CI/CD Test - Deploy Automático ✅  ← Deve aparecer o ✅
   ```

**Se não aparecer:**
- Aguarde 5-10 minutos (deploy pode levar tempo)
- Limpe o cache do navegador (Ctrl+F5 ou Cmd+Shift+R)
- Verifique se o build do Jenkins foi bem-sucedido

---

## ⏱️ Tempo Esperado

- **Build no Jenkins:** 5-15 minutos
- **Push para ACR:** 2-5 minutos
- **Deploy no App Service:** 2-5 minutos
- **Total:** ~10-25 minutos

---

## 🚨 Se o Build Falhar

### Erro no Checkout

**Sintoma:** "Authentication failed for 'https://github.com/...'"

**Solução:**
1. Verifique se a credencial `git-ssh-key` está configurada no Jenkins
2. Verifique se a URL do repositório está correta no `casc.yaml`
3. Recarregue a configuração do Jenkins

### Erro no Build

**Sintoma:** "Build Docker Images" falha

**Solução:**
1. Verifique os logs do build para ver o erro específico
2. Verifique se o `Dockerfile.prod` existe e está correto
3. Teste o build localmente:
   ```bash
   cd SDC
   docker build -f docker/Dockerfile.prod -t sdc-dev-app:test .
   ```

### Erro no Push para ACR

**Sintoma:** "Falha ao fazer login no ACR"

**Solução:**
1. Verifique se a credencial `azure-service-principal` está configurada
2. Verifique se `AZURE_TENANT_ID` está configurado
3. Verifique se o Service Principal tem permissões no ACR

### Erro no Deploy

**Sintoma:** "Deploy to Azure App Service" falha

**Solução:**
1. Verifique se o App Service existe e está configurado
2. Verifique as credenciais do ACR no App Service
3. Verifique os logs do App Service

---

## 📊 Monitoramento em Tempo Real

### Via Jenkins

1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. Clique no build mais recente
3. Clique em "Console Output" para ver os logs em tempo real

### Via Azure CLI

```bash
# Ver logs do App Service em tempo real
az webapp log tail \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL \
    --follow
```

---

## ✅ Checklist de Verificação

Após o push, verifique:

- [ ] Build apareceu no Jenkins (novo número de build)
- [ ] Build está em progresso ou completou
- [ ] Todos os stages completaram com sucesso
- [ ] Imagem foi enviada para o ACR (nova tag visível)
- [ ] App Service foi atualizado (nova imagem configurada)
- [ ] App Service reiniciou (logs mostram reinicialização)
- [ ] Aplicação está respondendo (health check passou)
- [ ] Texto "CI/CD Test - Deploy Automático ✅" aparece na tela de login

---

## 🎯 Próximos Passos

1. **Aguardar conclusão do build** (10-25 minutos)
2. **Verificar logs** se houver algum erro
3. **Testar a aplicação** em produção
4. **Confirmar que o texto aparece** na tela de login

---

## 📝 Resumo

**Commit:** `269d7b9`  
**Alteração:** Adicionado ✅ no footer do login  
**Status:** ✅ Push realizado com sucesso  
**Próximo:** Aguardar build do Jenkins e verificar deploy

**Tempo estimado para deploy:** 10-25 minutos

---

**Última atualização:** $(date)

