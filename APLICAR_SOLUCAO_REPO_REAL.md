# ✅ Aplicar Solução para Repositório Real

## 📋 Informações do Repositório

**Repositório:** `MatheusEstrela-dev/NewSDC`  
**URL HTTPS:** `https://github.com/MatheusEstrela-dev/NewSDC.git`  
**URL SSH:** `git@github.com:MatheusEstrela-dev/NewSDC.git`  
**Branch:** `main`

---

## 🔧 Solução: Configurar Job no Jenkins

### Opção 1: Via Interface Web (Recomendado - Mais Rápido)

#### Passo 1: Acessar Configuração do Job

1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
2. Faça login se necessário

#### Passo 2: Configurar Pipeline

Role até a seção **"Pipeline"** e configure:

**Definition:**
- ✅ Selecione: **Pipeline script from SCM**

**SCM:**
- ✅ Selecione: **Git**

**Repository URL:**
- ✅ Cole uma das opções abaixo:

**Opção A - SSH (Recomendado se tiver chave SSH):**
```
git@github.com:MatheusEstrela-dev/NewSDC.git
```

**Opção B - HTTPS (Mais simples, requer token):**
```
https://github.com/MatheusEstrela-dev/NewSDC.git
```

**Credentials:**
- Se usar **SSH**: Selecione `git-ssh-key` (ou crie se não existir)
- Se usar **HTTPS**: Selecione credencial com token do GitHub (ou crie)

**Branches to build:**
- ✅ `*/main`

**Script Path:**
- ✅ `SDC/Jenkinsfile`

**Repository browser:**
- ✅ Deixe em branco ou selecione "Auto"

#### Passo 3: Salvar

1. Clique em **"Save"** (Salvar) no final da página
2. Aguarde a confirmação

---

### Opção 2: Configurar Credencial SSH

Se escolheu usar SSH, configure a credencial:

#### Passo 1: Acessar Credentials

1. Acesse: https://jenkinssdc.azurewebsites.net/credentials/
2. Clique em **"System"** → **"Global credentials"**

#### Passo 2: Adicionar Credencial SSH

1. Clique em **"Add Credentials"** (ou "Add" → "Jenkins")

2. Configure:
   - **Kind:** SSH Username with private key
   - **Scope:** Global
   - **ID:** `git-ssh-key`
   - **Description:** "SSH Key for Git repositories - NewSDC"
   - **Username:** `git`
   - **Private Key:**
     - Selecione **"Enter directly"**
     - Cole sua chave SSH privada

3. Clique em **"OK"**

#### Como Obter Chave SSH Privada

**Se você já tem uma chave SSH:**
```bash
# Windows (PowerShell)
cat ~/.ssh/id_rsa
# ou
cat ~/.ssh/id_ed25519

# Linux/Mac
cat ~/.ssh/id_rsa
cat ~/.ssh/id_ed25519
```

**Se não tem, crie uma:**
```bash
# Gerar nova chave SSH
ssh-keygen -t ed25519 -C "jenkins@jenkinssdc"

# Copiar chave privada (para colar no Jenkins)
cat ~/.ssh/id_ed25519

# Copiar chave pública (para adicionar no GitHub)
cat ~/.ssh/id_ed25519.pub
```

**Adicionar chave pública no GitHub:**
1. Acesse: https://github.com/MatheusEstrela-dev/NewSDC/settings/keys
2. Clique em **"Add deploy key"**
3. Cole a chave pública
4. Dê um nome: "Jenkins Deploy Key"
5. Marque **"Allow write access"** (se necessário)
6. Clique em **"Add key"**

---

### Opção 3: Configurar Credencial HTTPS (Alternativa)

Se preferir usar HTTPS com token:

#### Passo 1: Criar Personal Access Token no GitHub

1. Acesse: https://github.com/settings/tokens
2. Clique em **"Generate new token"** → **"Generate new token (classic)"**
3. Configure:
   - **Note:** "Jenkins CI/CD"
   - **Expiration:** Escolha um prazo (ex: 90 dias)
   - **Scopes:** Marque `repo` (acesso completo aos repositórios)
4. Clique em **"Generate token"**
5. **Copie o token** (você só verá uma vez!)

#### Passo 2: Adicionar Credencial no Jenkins

1. Acesse: https://jenkinssdc.azurewebsites.net/credentials/
2. Clique em **"Add Credentials"**

3. Configure:
   - **Kind:** Username with password
   - **Scope:** Global
   - **Username:** `MatheusEstrela-dev` (seu usuário do GitHub)
   - **Password:** Cole o token gerado
   - **ID:** `github-token`
   - **Description:** "GitHub Personal Access Token for NewSDC"

4. Clique em **"OK"**

#### Passo 3: Atualizar Job para Usar HTTPS

1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
2. Na seção Pipeline:
   - **Repository URL:** `https://github.com/MatheusEstrela-dev/NewSDC.git`
   - **Credentials:** Selecione `github-token`
3. **Salve**

---

## 🧪 Testar Configuração

### 1. Disparar Build Manual

1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. Clique no botão **"Build Now"** (ícone de play verde)
3. Aguarde alguns segundos e clique no build que aparecer

### 2. Verificar Logs do Checkout

**Se funcionou, você verá:**
```
Started by user admin
Building in workspace /var/jenkins_home/workspace/SDC/build-and-deploy@script/...
📦 Checking out code...
Cloning repository git@github.com:MatheusEstrela-dev/NewSDC.git
# ou
Cloning repository https://github.com/MatheusEstrela-dev/NewSDC.git
Commit: test: CI/CD - alteração mínima no footer...
Author: [seu nome]
```

**Se falhou, você verá:**
```
ERROR: Error cloning remote repo 'origin'
fatal: Authentication failed
# ou
ERROR: Error cloning remote repo 'origin'
Host key verification failed
```

---

## 🔄 Recarregar Configuração JCasC (Opcional)

Se quiser que o Jenkins use automaticamente o `casc.yaml`:

1. Acesse: https://jenkinssdc.azurewebsites.net/manage
2. Procure por **"Configuration as Code"** ou **"JCasC"**
3. Clique em **"Reload configuration"**
4. Aguarde a confirmação

**Nota:** O `casc.yaml` já está configurado corretamente, mas o Jenkins precisa recarregar para aplicar.

---

## ✅ Checklist de Verificação

Após configurar, verifique:

- [ ] URL do repositório está correta no job
- [ ] Credencial configurada (SSH ou HTTPS)
- [ ] Script Path está como `SDC/Jenkinsfile`
- [ ] Branch está como `*/main`
- [ ] Build manual disparado
- [ ] Checkout funcionou (sem erros de autenticação)
- [ ] Logs mostram o commit correto

---

## 🚀 Após Configurar

1. **Aguardar build completar** (10-25 minutos)
2. **Verificar deploy** no App Service
3. **Testar aplicação** em: https://newsdc2027.azurewebsites.net/login
4. **Verificar texto** "CI/CD Test - Deploy Automático ✅" no footer

---

## 📝 Resumo Rápido

**Repositório:** `MatheusEstrela-dev/NewSDC`  
**URL SSH:** `git@github.com:MatheusEstrela-dev/NewSDC.git`  
**URL HTTPS:** `https://github.com/MatheusEstrela-dev/NewSDC.git`  
**Script Path:** `SDC/Jenkinsfile`  
**Branch:** `main`

**Ação:** Configurar job no Jenkins com URL e credencial corretas

---

**Última atualização:** $(date)

