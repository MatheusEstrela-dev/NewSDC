# 🔍 Resumo do Diagnóstico via MCP Zen Debug Tool

## 🎯 Problema Original

**Build Jenkins falhando** ao tentar fazer checkout do repositório Git.

---

## 🔬 Investigação via MCP Debug (3 Etapas)

### Etapa 1: Diagnóstico Inicial
**Sintoma:** Build #1 usou URL incorreta: `http://github.com/user/repo.git`

**Hipótese Inicial:**
- Variáveis de ambiente não estavam sendo carregadas no Azure App Service
- JCasC não foi aplicado corretamente

**Evidência:**
```bash
az webapp config appsettings list --name jenkinssdc
# Resultado: Apenas DOCKER_ENABLE_CI=true
```

---

### Etapa 2: Primeira Correção
**Ação:** Adicionar 12 variáveis de ambiente no Azure App Service

**Variáveis Configuradas:**
- `GIT_REPO_URL=git@github.com:MatheusEstrela-dev/NewSDC.git` ✅
- `AZURE_CLIENT_ID, AZURE_CLIENT_SECRET, AZURE_TENANT_ID`
- `ACR_NAME, AZURE_ACR_USERNAME, AZURE_ACR_PASSWORD`
- `JENKINS_ADMIN_USER, JENKINS_ADMIN_PASSWORD, JENKINS_URL, JENKINS_ADMIN_EMAIL`

**Resultado:**
- ✅ URL corrigida - Jenkins passou a usar `git@github.com:MatheusEstrela-dev/NewSDC.git`
- ❌ Novo erro descoberto

---

### Etapa 3: Segundo Problema Descoberto
**Novo Erro:**
```
Load key "...": error in libcrypto
git@github.com: Permission denied (publickey).
```

**Diagnóstico Final:**
- Chave SSH `git-ssh-key` está vazia ou mal formatada
- Variável `GIT_SSH_PRIVATE_KEY` não foi definida no App Service
- JCasC criou a credencial mas sem conteúdo

**Solução Recomendada pelo MCP:**
Mudar de SSH para HTTPS + GitHub Personal Access Token
- ✅ Mais simples
- ✅ Não precisa gerenciar chaves SSH
- ✅ Mais confiável em ambientes cloud

---

## ✅ Correção Final Aplicada

### 1. Mudar URL para HTTPS
```bash
az webapp config appsettings set --name jenkinssdc --resource-group DEFESA_CIVIL \
  --settings GIT_REPO_URL="https://github.com/MatheusEstrela-dev/NewSDC.git"
```

### 2. Reiniciar Jenkins
```bash
az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
```

---

## 📝 Próximo Passo: Configurar GitHub Token

### Agora você precisa:

1. **Gerar Personal Access Token no GitHub:**
   - Acesse: https://github.com/settings/tokens
   - Generate new token (classic)
   - Permissions: ☑️ `repo` (full control)
   - Copie o token (ex: `ghp_abc123...`)

2. **Adicionar no Jenkins:**
   - Acesse: https://jenkinssdc.azurewebsites.net/manage/credentials/store/system/domain/_/newCredentials
   - Kind: `Username with password`
   - Username: `MatheusEstrela-dev`
   - Password: Cole o token do GitHub
   - ID: `github-token`
   - Description: `GitHub Personal Access Token`
   - Clique em "Create"

3. **Atualizar Job:**
   - Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
   - Repository URL: `https://github.com/MatheusEstrela-dev/NewSDC.git` (já deve estar correto)
   - Credentials: Selecione `MatheusEstrela-dev/****** (GitHub Personal Access Token)`
   - Save

4. **Testar:**
   - Clique em "Build Now"
   - Verifique Console Output

---

## 📊 Resumo da Investigação MCP

| Etapa | Problema | Solução | Status |
|-------|----------|---------|--------|
| 1 | URL incorreta | Adicionar variáveis de ambiente | ✅ Resolvido |
| 2 | Chave SSH inválida | Mudar para HTTPS | ✅ Aplicado |
| 3 | Falta credencial GitHub | Adicionar token no Jenkins | ⏳ Pendente (manual) |

---

## 🎯 Confiança do Diagnóstico

**Nível de Confiança:** Very High ✅

**Evidências:**
- ✅ Variáveis de ambiente verificadas via Azure CLI
- ✅ Logs do Jenkins analisados
- ✅ Arquivos de configuração JCasC revisados
- ✅ Erro SSH identificado e diagnosticado

**Arquivos Investigados:**
1. `SDC/docker/jenkins/casc.yaml` (JCasC)
2. `SDC/docker/.env.jenkins` (Template variáveis)
3. `SDC/docker/docker-compose.jenkins-dev.yml` (Docker Compose)
4. `CORRIGIR_AUTENTICACAO_GIT_JENKINS.md` (Documentação)

---

## 🔬 Ferramentas Utilizadas

- **MCP Zen Debug Tool** - Diagnóstico sistemático
- **Azure CLI** - Verificação e configuração de variáveis
- **Jenkins Console Logs** - Análise de erros
- **Git** - Teste de commits

---

## 📚 Documentação Criada

- [CORRIGIR_AUTENTICACAO_GIT_JENKINS.md](CORRIGIR_AUTENTICACAO_GIT_JENKINS.md) - Guia completo (2 opções)
- [CORRECAO_VARIAVEIS_AMBIENTE_JENKINS.md](CORRECAO_VARIAVEIS_AMBIENTE_JENKINS.md) - Correção aplicada
- [VERIFICAR_BUILD_JENKINS.md](VERIFICAR_BUILD_JENKINS.md) - Como verificar builds
- [RESUMO_DIAGNOSTICO_MCP.md](RESUMO_DIAGNOSTICO_MCP.md) - Este arquivo

---

**Status Final:** 🟡 **Aguardando configuração manual do GitHub Token**
**Tempo de Diagnóstico:** ~15-20 minutos via MCP
**Próxima Ação:** Configurar GitHub Token no Jenkins (3-5 minutos)
