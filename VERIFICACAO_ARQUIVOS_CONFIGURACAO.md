# ✅ Verificação dos Arquivos de Configuração

## 🎯 Objetivo
Verificar e garantir que todos os arquivos de configuração estão consistentes após a mudança de SSH para HTTPS com GitHub Token.

---

## 📁 Arquivos Verificados e Atualizados

### 1. casc.yaml ✅ Atualizado

**Arquivo:** `SDC/docker/jenkins/casc.yaml`

#### Alterações Realizadas:

**Linha 117-123:** Adicionada nova credencial para GitHub Token
```yaml
# GitHub Personal Access Token
- usernamePassword:
    scope: GLOBAL
    id: "github-token"
    username: "${GITHUB_USERNAME:-MatheusEstrela-dev}"
    password: "${GITHUB_TOKEN:-}"
    description: "GitHub Personal Access Token for HTTPS authentication"
```

**Linha 172-173:** Atualizado URL e credencial do Job
```yaml
url('${GIT_REPO_URL:-https://github.com/MatheusEstrela-dev/NewSDC.git}')
credentials('github-token')
```

**❌ Antes (SSH):**
```yaml
url('${GIT_REPO_URL:-git@github.com:MatheusEstrela-dev/NewSDC.git}')
credentials('git-ssh-key')
```

**✅ Depois (HTTPS):**
```yaml
url('${GIT_REPO_URL:-https://github.com/MatheusEstrela-dev/NewSDC.git}')
credentials('github-token')
```

---

### 2. .env.jenkins ✅ Atualizado

**Arquivo:** `SDC/docker/.env.jenkins`

#### Alterações Realizadas:

**Linhas 24-27:** Atualizado para HTTPS
```bash
# Git Repository (HTTPS com Token)
GIT_REPO_URL=https://github.com/MatheusEstrela-dev/NewSDC.git
GITHUB_USERNAME=MatheusEstrela-dev
GITHUB_TOKEN=
```

**❌ Antes (SSH):**
```bash
# Git Repository
GIT_REPO_URL=git@github.com:MatheusEstrela-dev/NewSDC.git
```

**✅ Depois (HTTPS):**
```bash
# Git Repository (HTTPS com Token)
GIT_REPO_URL=https://github.com/MatheusEstrela-dev/NewSDC.git
GITHUB_USERNAME=MatheusEstrela-dev
GITHUB_TOKEN=
```

**⚠️ Ação Necessária:** Preencher `GITHUB_TOKEN` com o Personal Access Token do GitHub

---

### 3. Azure App Service Environment Variables

**Variável já atualizada via Azure CLI:**
```bash
GIT_REPO_URL=https://github.com/MatheusEstrela-dev/NewSDC.git
```

**Verificar com:**
```bash
az webapp config appsettings list --name jenkinssdc --resource-group DEFESA_CIVIL \
  --query "[?name=='GIT_REPO_URL'].{Name:name, Value:value}" -o table
```

**⚠️ Ações Necessárias:**
Adicionar no Azure App Service:
```bash
az webapp config appsettings set --name jenkinssdc --resource-group DEFESA_CIVIL \
  --settings GITHUB_USERNAME="MatheusEstrela-dev" GITHUB_TOKEN="<seu-token-aqui>"
```

---

## 📊 Resumo das Mudanças

| Arquivo | Status | Mudança Principal |
|---------|--------|-------------------|
| `casc.yaml` | ✅ Atualizado | Adicionada credencial `github-token` e atualizado job para HTTPS |
| `.env.jenkins` | ✅ Atualizado | URL mudada para HTTPS, adicionadas variáveis `GITHUB_USERNAME` e `GITHUB_TOKEN` |
| Azure App Service | 🟡 Parcial | `GIT_REPO_URL` atualizado, falta adicionar `GITHUB_TOKEN` |

---

## 🔍 Consistência Verificada

### ✅ Pontos Positivos

1. **URL consistente em todos os arquivos:** `https://github.com/MatheusEstrela-dev/NewSDC.git`
2. **Credencial definida no JCasC:** `github-token` com variáveis de ambiente
3. **Job configurado para usar HTTPS:** `credentials('github-token')`
4. **Fallback correto:** Se variável não existir, usa URL HTTPS padrão

### ⚠️ Pontos de Atenção

1. **GitHub Token vazio:** Variável `GITHUB_TOKEN` precisa ser preenchida
2. **Variáveis no Azure:** Precisam ser adicionadas no App Service
3. **Jenkins precisa ser reiniciado:** Após adicionar o token no Azure

---

## 🎯 Próximos Passos (Em Ordem)

### 1. Gerar GitHub Personal Access Token

**Acesse:** https://github.com/settings/tokens

1. Clique em **"Generate new token"** → **"Generate new token (classic)"**
2. Configure:
   - **Note:** `Jenkins CI/CD - NewSDC`
   - **Expiration:** 90 days (ou No expiration)
   - **Select scopes:** ☑️ `repo` (marque tudo)
3. Clique em **"Generate token"**
4. **⚠️ COPIE O TOKEN AGORA!** (formato: `ghp_...`)

---

### 2. Adicionar Token no Azure App Service

```bash
# Substitua <SEU_TOKEN> pelo token copiado
az webapp config appsettings set --name jenkinssdc --resource-group DEFESA_CIVIL \
  --settings \
  GITHUB_USERNAME="MatheusEstrela-dev" \
  GITHUB_TOKEN="<SEU_TOKEN>"
```

**Verificar:**
```bash
az webapp config appsettings list --name jenkinssdc --resource-group DEFESA_CIVIL \
  --query "[?name=='GITHUB_TOKEN' || name=='GITHUB_USERNAME'].{Name:name}" -o table
```

---

### 3. Reiniciar Jenkins

```bash
az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
```

**Aguardar:** ~1-2 minutos para o Jenkins reiniciar

---

### 4. Testar Build

**Opção A: Via Web UI**
1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. Clique em **"Build Now"**
3. Clique no build que aparecer
4. Clique em **"Console Output"**

**Opção B: Via Commit**
```bash
cd c:\Users\kdes\Documentos\GitHub\New_SDC
echo "# Test HTTPS" >> TESTE.md
git add TESTE.md
git commit -m "test: verificar autenticação HTTPS com token"
git push origin main
```

---

### 5. Verificar Logs do Build

**Logs esperados (sucesso):**
```
Started by user admin (ou GitHub push)
Checking out git https://github.com/MatheusEstrela-dev/NewSDC.git
 > git fetch --tags --force --progress
✅ SUCCESS - Checking out Revision abc123...
```

**Se houver erro:**
```
❌ ERROR: Authentication failed
```
→ Verifique se o token tem permissões corretas (`repo`)

---

## 📋 Checklist de Verificação

- [x] **casc.yaml atualizado** - Credencial `github-token` adicionada
- [x] **casc.yaml job atualizado** - Usa HTTPS e `credentials('github-token')`
- [x] **.env.jenkins atualizado** - URL HTTPS e variáveis `GITHUB_USERNAME`/`GITHUB_TOKEN`
- [x] **Azure GIT_REPO_URL** - Configurado para HTTPS
- [ ] **Azure GITHUB_TOKEN** - Precisa adicionar (ação manual)
- [ ] **Azure GITHUB_USERNAME** - Precisa adicionar (ação manual)
- [ ] **Jenkins reiniciado** - Após adicionar token
- [ ] **Build testado** - Via "Build Now" ou commit
- [ ] **Logs verificados** - Checkout do Git funcionando

---

## 🔐 Segurança

**⚠️ IMPORTANTE:**

1. **Nunca commite o token no Git**
   - O `.env.jenkins` está no `.gitignore`
   - Variáveis no Azure são seguras

2. **Rotação de token**
   - Tokens podem expirar
   - Configure expiração apropriada (90 dias recomendado)
   - Renove antes de expirar

3. **Permissões mínimas**
   - Use apenas scope `repo` (acesso ao código)
   - Não dê permissões admin desnecessárias

---

## 📊 Diferenças: SSH vs HTTPS

| Aspecto | SSH | HTTPS + Token |
|---------|-----|---------------|
| **Configuração** | Complexa (chave pública/privada) | Simples (token) |
| **Manutenção** | Chave não expira | Token expira periodicamente |
| **Compatibilidade** | Pode ter problemas em cloud | ✅ Funciona em qualquer ambiente |
| **Segurança** | ⭐⭐⭐ Muito boa | ⭐⭐⭐ Boa (com rotação) |
| **Recomendado para** | Ambientes locais/fixos | ✅ Cloud/Azure App Service |

---

**Status:** ✅ **Arquivos atualizados e consistentes**
**Próxima ação:** Adicionar `GITHUB_TOKEN` no Azure App Service
**Tempo estimado:** 3-5 minutos
