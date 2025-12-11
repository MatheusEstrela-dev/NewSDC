# 🔐 Corrigir Autenticação Git no Jenkins - 2 Opções

## 🎯 Problema

O Jenkins não consegue fazer checkout do repositório:
```
ERROR: Error cloning remote repo 'origin'
fatal: Authentication failed for 'https://github.com/user/repo.git/'
```

---

## ✅ Solução 1: HTTPS com GitHub Token (RECOMENDADO - Mais Fácil)

Esta é a opção **mais simples** e funciona em qualquer ambiente.

### Passo 1: Gerar Personal Access Token no GitHub

1. **Acesse:** https://github.com/settings/tokens
2. Clique em **"Generate new token"** → **"Generate new token (classic)"**
3. Configure:
   - **Note:** `Jenkins CI/CD - NewSDC`
   - **Expiration:** 90 days (ou No expiration se preferir)
   - **Select scopes:**
     - ☑️ `repo` (marque tudo)
     - ☑️ `admin:repo_hook` → `write:repo_hook`
4. Clique em **"Generate token"**
5. **⚠️ COPIE O TOKEN AGORA** (você não verá novamente!)
   - Exemplo: `ghp_abc123xyz...` (70 caracteres)

---

### Passo 2: Adicionar Token no Jenkins

1. **Acesse:** https://jenkinssdc.azurewebsites.net/manage/credentials/store/system/domain/_/newCredentials
2. **Configure:**
   - **Kind:** `Username with password`
   - **Scope:** `Global`
   - **Username:** `MatheusEstrela-dev` (seu username do GitHub)
   - **Password:** Cole o token que você copiou (ex: `ghp_abc123...`)
   - **ID:** `github-token`
   - **Description:** `GitHub Personal Access Token - NewSDC`
3. Clique em **"Create"**

---

### Passo 3: Configurar Job para Usar Token

1. **Acesse:** https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
2. Role até **"Pipeline"** → **"SCM"** → **"Git"**
3. Configure:
   - **Repository URL:** `https://github.com/MatheusEstrela-dev/NewSDC.git`
   - **Credentials:** Selecione `MatheusEstrela-dev/****** (GitHub Personal Access Token - NewSDC)`
   - **Branches to build:** `*/main`
   - **Script Path:** `SDC/Jenkinsfile`
4. Clique em **"Save"**

---

### Passo 4: Testar

1. **Acesse:** https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. Clique em **"Build Now"**
3. Verifique se o checkout funciona

---

## ✅ Solução 2: SSH com Chave SSH (Alternativa)

Use esta opção se você já tem uma chave SSH configurada no GitHub.

### Passo 1: Obter sua Chave SSH Privada

**Windows:**
```bash
# Ver se você tem chave SSH
cat ~/.ssh/id_rsa
# Ou
cat ~/.ssh/id_ed25519
```

**Se você NÃO tiver chave SSH:**
```bash
# Gerar nova chave
ssh-keygen -t ed25519 -C "seu_email@example.com"

# Ver a chave pública (adicione no GitHub)
cat ~/.ssh/id_ed25519.pub

# Ver a chave privada (use no Jenkins)
cat ~/.ssh/id_ed25519
```

---

### Passo 2: Adicionar Chave Pública no GitHub

1. **Acesse:** https://github.com/settings/keys
2. Clique em **"New SSH key"**
3. Configure:
   - **Title:** `Jenkins Azure - NewSDC`
   - **Key:** Cole o conteúdo do arquivo `id_ed25519.pub` (ou `id_rsa.pub`)
4. Clique em **"Add SSH key"**

---

### Passo 3: Adicionar Chave Privada no Jenkins

1. **Acesse:** https://jenkinssdc.azurewebsites.net/manage/credentials/store/system/domain/_/newCredentials
2. **Configure:**
   - **Kind:** `SSH Username with private key`
   - **Scope:** `Global`
   - **ID:** `git-ssh-key`
   - **Description:** `SSH Key for GitHub - NewSDC`
   - **Username:** `git`
   - **Private Key:**
     - Selecione: **"Enter directly"**
     - Clique em **"Add"**
     - Cole o conteúdo COMPLETO do arquivo `id_ed25519` (ou `id_rsa`)
       - Incluindo: `-----BEGIN OPENSSH PRIVATE KEY-----`
       - E: `-----END OPENSSH PRIVATE KEY-----`
   - **Passphrase:** (deixe em branco se não tiver)
3. Clique em **"Create"**

---

### Passo 4: Configurar Job para Usar SSH

1. **Acesse:** https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
2. Role até **"Pipeline"** → **"SCM"** → **"Git"**
3. Configure:
   - **Repository URL:** `git@github.com:MatheusEstrela-dev/NewSDC.git`
   - **Credentials:** Selecione `git (SSH Key for GitHub - NewSDC)`
   - **Branches to build:** `*/main`
   - **Script Path:** `SDC/Jenkinsfile`
4. Clique em **"Save"**

---

### Passo 5: Testar

1. **Acesse:** https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. Clique em **"Build Now"**
3. Verifique se o checkout funciona

---

## 🔄 Comparação das Opções

| Aspecto | HTTPS + Token | SSH + Chave |
|---------|---------------|-------------|
| Configuração | ⭐⭐⭐ Muito fácil | ⭐⭐ Médio |
| Segurança | ⭐⭐⭐ Boa | ⭐⭐⭐ Ótima |
| Compatibilidade | ⭐⭐⭐ Funciona em qualquer lugar | ⭐⭐ Pode ter problemas de rede |
| Manutenção | Token expira (renovar) | Chave não expira |
| Recomendado | ✅ **SIM** (mais fácil) | Para usuários avançados |

---

## 📋 Checklist - Opção 1 (HTTPS + Token)

- [ ] Gerei Personal Access Token no GitHub
- [ ] Copiei o token (começando com `ghp_...`)
- [ ] Adicionei credencial no Jenkins com ID `github-token`
- [ ] Configurei job com URL HTTPS: `https://github.com/MatheusEstrela-dev/NewSDC.git`
- [ ] Selecionei credencial do token
- [ ] Salvei a configuração
- [ ] Testei com "Build Now"
- [ ] Checkout funcionou ✅

---

## 📋 Checklist - Opção 2 (SSH)

- [ ] Tenho/gerei chave SSH (`id_ed25519` ou `id_rsa`)
- [ ] Adicionei chave pública no GitHub
- [ ] Adicionei chave privada no Jenkins com ID `git-ssh-key`
- [ ] Configurei job com URL SSH: `git@github.com:MatheusEstrela-dev/NewSDC.git`
- [ ] Selecionei credencial SSH
- [ ] Salvei a configuração
- [ ] Testei com "Build Now"
- [ ] Checkout funcionou ✅

---

## 🐛 Troubleshooting

### Token não funciona (Opção 1)

**Erro:** `Authentication failed`

**Soluções:**
- Verifique se o token tem permissão `repo`
- Verifique se copiou o token completo (começa com `ghp_`)
- Verifique se o username está correto (`MatheusEstrela-dev`)
- Gere um novo token se necessário

---

### SSH não funciona (Opção 2)

**Erro:** `Permission denied (publickey)`

**Soluções:**
- Verifique se a chave pública foi adicionada no GitHub
- Verifique se copiou a chave privada COMPLETA no Jenkins
  - Incluindo `-----BEGIN...-----` e `-----END...-----`
- Teste a conexão SSH manualmente:
  ```bash
  ssh -T git@github.com
  ```

---

## 🎯 Resultado Esperado

Após corrigir, no Console Output você deve ver:

```
Cloning the remote Git repository
Cloning repository https://github.com/MatheusEstrela-dev/NewSDC.git
 > git init /var/jenkins_home/workspace/...
 > git fetch --tags --force --progress
Checking out Revision abc123... (origin/main)
 > git checkout -f abc123...
✅ SUCCESS
```

---

## 💡 Dica

Recomendo usar **Opção 1 (HTTPS + Token)** por ser:
- Mais simples de configurar
- Funciona em qualquer ambiente
- Não precisa gerenciar chaves SSH
- Fácil de renovar se expirar

---

**Status:** 🔴 **URGENTE - Escolha uma opção e execute**
**Tempo estimado:** 3-7 minutos
**Recomendação:** ⭐ **Opção 1 (HTTPS + Token)**
