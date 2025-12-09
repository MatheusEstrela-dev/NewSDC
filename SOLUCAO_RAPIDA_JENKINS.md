# ⚡ Solução Rápida - Configurar Jenkins para Repositório Real

## 🎯 Objetivo

Configurar o Jenkins para usar o repositório real: `MatheusEstrela-dev/NewSDC`

---

## 📍 Passo 1: Acessar Configuração do Job

**URL:** https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure

1. Abra o link acima no navegador
2. Faça login se necessário
3. Você verá a página de configuração do job

---

## 📍 Passo 2: Configurar Pipeline

Role a página até encontrar a seção **"Pipeline"**

### 2.1. Definition
- ✅ Selecione: **"Pipeline script from SCM"**

### 2.2. SCM
- ✅ Selecione: **"Git"**

### 2.3. Repository URL
**Cole exatamente esta URL:**
```
git@github.com:MatheusEstrela-dev/NewSDC.git
```

### 2.4. Credentials
- Clique no dropdown
- Selecione: **"git-ssh-key"**
- Se não aparecer, veja o **Passo 3** abaixo para criar

### 2.5. Branches to build
- ✅ Deve estar: **"*/main"**
- Se não estiver, altere para: `*/main`

### 2.6. Script Path
- ✅ Deve estar: **"SDC/Jenkinsfile"**
- Se não estiver, altere para: `SDC/Jenkinsfile`

### 2.7. Repository browser
- Deixe em branco ou selecione "Auto"

---

## 📍 Passo 3: Criar Credencial SSH (Se Não Existir)

**Se a credencial `git-ssh-key` não aparecer no dropdown:**

### 3.1. Acessar Credentials

**URL:** https://jenkinssdc.azurewebsites.net/credentials/

1. Clique em **"System"** (no menu lateral)
2. Clique em **"Global credentials (unrestricted)"**
3. Clique em **"Add Credentials"** (ou "Add" → "Jenkins")

### 3.2. Configurar Credencial

**Kind:**
- ✅ Selecione: **"SSH Username with private key"**

**Scope:**
- ✅ Selecione: **"Global"**

**ID:**
- ✅ Digite: `git-ssh-key`

**Description:**
- ✅ Digite: `SSH Key for Git repositories - NewSDC`

**Username:**
- ✅ Digite: `git`

**Private Key:**
- ✅ Selecione: **"Enter directly"**
- ✅ Cole sua chave SSH privada no campo de texto

**Passphrase:**
- Deixe em branco (se sua chave não tiver senha)

### 3.3. Salvar

1. Clique em **"OK"**
2. Aguarde a confirmação

### 3.4. Obter Chave SSH Privada

**No seu computador, execute:**

**Windows (PowerShell):**
```powershell
# Verificar se tem chave SSH
ls ~/.ssh/

# Se tiver id_rsa
cat ~/.ssh/id_rsa

# Se tiver id_ed25519
cat ~/.ssh/id_ed25519
```

**Se não tiver chave SSH, crie uma:**
```powershell
# Gerar nova chave
ssh-keygen -t ed25519 -C "jenkins@jenkinssdc"

# Quando perguntar onde salvar, pressione Enter (usa local padrão)
# Quando perguntar senha, pressione Enter (sem senha)

# Copiar chave privada (para colar no Jenkins)
cat ~/.ssh/id_ed25519

# Copiar chave pública (para adicionar no GitHub)
cat ~/.ssh/id_ed25519.pub
```

**Adicionar chave pública no GitHub:**
1. Acesse: https://github.com/MatheusEstrela-dev/NewSDC/settings/keys
2. Clique em **"Add deploy key"**
3. **Title:** `Jenkins Deploy Key`
4. **Key:** Cole a chave pública (id_ed25519.pub)
5. Marque **"Allow write access"** (se necessário)
6. Clique em **"Add key"**

---

## 📍 Passo 4: Salvar Configuração

1. Role até o final da página de configuração
2. Clique no botão **"Save"** (ou "Salvar")
3. Aguarde a confirmação

---

## 📍 Passo 5: Testar

### 5.1. Disparar Build Manual

1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. Clique no botão **"Build Now"** (ícone de play verde no canto esquerdo)
3. Aguarde alguns segundos

### 5.2. Verificar Build

1. Um novo build aparecerá na lista (ex: #6)
2. Clique no build para ver os logs
3. Clique em **"Console Output"**

### 5.3. Verificar Logs

**Se funcionou, você verá:**
```
Started by user admin
Building in workspace /var/jenkins_home/workspace/...
📦 Checking out code...
Cloning repository git@github.com:MatheusEstrela-dev/NewSDC.git
Commit: test: CI/CD - alteração mínima no footer...
Author: [seu nome]
```

**Se falhou, você verá:**
```
ERROR: Error cloning remote repo 'origin'
fatal: Authentication failed
```

**Se falhar:**
- Verifique se a credencial SSH está correta
- Verifique se a chave pública foi adicionada no GitHub
- Tente usar HTTPS com token (veja alternativa abaixo)

---

## 🔄 Alternativa: Usar HTTPS com Token

Se SSH não funcionar, use HTTPS:

### 1. Criar Token no GitHub

1. Acesse: https://github.com/settings/tokens
2. Clique em **"Generate new token"** → **"Generate new token (classic)"**
3. **Note:** `Jenkins CI/CD`
4. **Expiration:** Escolha um prazo
5. **Scopes:** Marque `repo`
6. Clique em **"Generate token"**
7. **Copie o token** (você só verá uma vez!)

### 2. Criar Credencial no Jenkins

1. Acesse: https://jenkinssdc.azurewebsites.net/credentials/
2. Clique em **"Add Credentials"**
3. Configure:
   - **Kind:** Username with password
   - **Username:** `MatheusEstrela-dev`
   - **Password:** Cole o token
   - **ID:** `github-token`
4. Clique em **"OK"**

### 3. Atualizar Job

1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
2. **Repository URL:** `https://github.com/MatheusEstrela-dev/NewSDC.git`
3. **Credentials:** Selecione `github-token`
4. **Salve**

---

## ✅ Checklist Final

- [ ] URL do repositório configurada: `git@github.com:MatheusEstrela-dev/NewSDC.git`
- [ ] Credencial SSH criada: `git-ssh-key`
- [ ] Chave pública adicionada no GitHub
- [ ] Script Path: `SDC/Jenkinsfile`
- [ ] Branch: `*/main`
- [ ] Configuração salva
- [ ] Build manual disparado
- [ ] Checkout funcionou (sem erros)

---

## 🎯 Próximos Passos

Após o checkout funcionar:

1. **Aguardar build completar** (10-25 minutos)
2. **Verificar deploy** no App Service
3. **Testar aplicação** em: https://newsdc2027.azurewebsites.net/login
4. **Verificar texto** "CI/CD Test - Deploy Automático ✅" no footer

---

## 📞 Resumo

**Repositório:** `MatheusEstrela-dev/NewSDC`  
**URL:** `git@github.com:MatheusEstrela-dev/NewSDC.git`  
**Credencial:** `git-ssh-key` (SSH)  
**Script:** `SDC/Jenkinsfile`  
**Branch:** `main`

**Tempo estimado:** 5-10 minutos para configurar

---

**Status:** ✅ Pronto para aplicar  
**Ação:** Siga os passos acima para configurar o Jenkins

