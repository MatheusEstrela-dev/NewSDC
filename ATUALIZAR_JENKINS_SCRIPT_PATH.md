# 🎉 Jenkinsfile na Raiz - SUCESSO!

## ✅ Status Atual (Build #1)

**🎉 JENKINSFILE FUNCIONANDO PERFEITAMENTE!**

O Jenkins agora está encontrando o arquivo corretamente na raiz:

```
Checking out git ... to read Jenkinsfile
[Pipeline] Start of Pipeline
✅ Stage: Checkout - FUNCIONANDO
✅ Stage: Pre-flight Checks - FUNCIONANDO
❌ Stage: Build and Push to ACR - FALHA (Permissões ACR)
```

## ✅ O que foi feito

1. ✅ Jenkinsfile movido para a raiz do repositório
2. ✅ Commit e push realizados com sucesso
3. ✅ Commit hash: `a14d306`
4. ✅ Configuração do Jenkins atualizada (Script Path: `Jenkinsfile`)
5. ✅ Build #1 executado com sucesso até o estágio ACR

## 🔧 Próximo Passo: Atualizar Configuração do Jenkins

Agora você precisa atualizar a configuração do Jenkins para usar o Jenkinsfile da raiz.

### Passo 1: Acessar Configuração do Job

**URL:** https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure

### Passo 2: Atualizar Script Path

1. Role até a seção **"Pipeline"** (final da página)
2. Localize o campo **"Script Path"**
3. **Altere de:** `SDC/Jenkinsfile`
4. **Para:** `Jenkinsfile`
5. Clique em **"Save"**

### Passo 3: Verificar Trigger Automático

O webhook do GitHub deve disparar automaticamente um novo build após o push.

**Verificar:**
- Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
- Um novo build deve aparecer na lista "Build History"
- O build deve usar o Jenkinsfile da raiz

### Passo 4: Se o Trigger não Disparar

Se o build não disparar automaticamente:

1. Clique em **"Build Now"** manualmente (recomendado)
2. **NÃO acesse a URL `/build` diretamente no navegador** - isso causa erro de POST

### ⚠️ Erro: "É obrigatório utilizar POST no formulário"

**Causa do erro:**
- Você tentou acessar a URL de build diretamente no navegador: `/build?delay=0sec`
- O navegador usa método **GET**, mas o Jenkins exige **POST** por segurança
- Isso é uma proteção do Jenkins contra builds acidentais

**Solução:**
1. **Clique no botão "Prosseguir"** na página de erro (se aparecer)
2. **Ou melhor:** Vá para a página principal do job e clique em **"Build Now"**
   - URL: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
   - Clique no botão **"Build Now"** no menu lateral esquerdo

**Por que o webhook pode não ter disparado:**
- O webhook do GitHub precisa estar configurado corretamente
- O webhook deve enviar uma requisição POST para o Jenkins
- Verifique se o webhook está ativo no GitHub: Settings → Webhooks

## ✅ Verificação - FUNCIONANDO!

No console do build #1, você pode ver:

```
Checking out git ... to read Jenkinsfile
[Pipeline] Start of Pipeline
✅ Stage: Checkout - FUNCIONANDO
✅ Stage: Pre-flight Checks - FUNCIONANDO
```

**✅ CONFIRMADO: Jenkinsfile está sendo encontrado corretamente na raiz!**

## ❌ Próximo Problema: Permissões ACR

O pipeline está falhando no estágio "Build and Push to ACR" por falta de permissões:

```
ERROR: (AuthorizationFailed) The client does not have authorization to perform action 
'Microsoft.ContainerRegistry/registries/read' over scope '.../registries/APIDOVER'
```

**Solução:** Mesmo que a role "AcrPush" já esteja atribuída ao "jenkins-sdc-acr", pode ser necessário:

1. **Reiniciar o Jenkins** para limpar cache de credenciais:
   ```bash
   az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
   ```

2. **Aguardar 5-10 minutos** após atribuir a role (propagação de permissões)

3. **Verificar se o Service Principal correto tem a role:**
   - Object ID do erro: `36582784-e2a0-4b8d-980a-13bebee16c56`
   - Verifique se este corresponde ao "jenkins-sdc-acr" no Azure Portal

**Veja:** `INSTRUCOES_CORRIGIR_JENKINS.md` - Seção "PRÓXIMO PASSO: Corrigir Permissões do Azure Container Registry"

## 📋 Checklist - Jenkinsfile (COMPLETO ✅)

- [x] 1. Jenkinsfile movido para raiz do repositório
- [x] 2. Commit e push realizados
- [x] 3. Acessar /configure do job build-and-deploy
- [x] 4. Alterar "Script Path" de `SDC/Jenkinsfile` para `Jenkinsfile`
- [x] 5. Clicar em "Save"
- [x] 6. Build #1 executado
- [x] 7. Console output mostra `to read Jenkinsfile` ✅

**✅ PROBLEMA DO JENKINSFILE RESOLVIDO!**

**⏳ Próximo passo:** Corrigir permissões do ACR (veja seção acima)

---

**⚡ EXECUTE AGORA:**

1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
2. Altere Script Path para: `Jenkinsfile`
3. Save
4. Aguarde o build automático ou clique "Build Now"

**Tempo estimado:** 1 minuto

