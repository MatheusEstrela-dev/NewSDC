# 🔧 Correção Manual do Jenkins - Passo a Passo

## ⚠️ Status Atual

O Jenkins está iniciando. Aguarde até que a página carregue completamente antes de seguir os passos abaixo.

---

## 📋 Passos para Correção

### Passo 1: Acessar Configuração

1. **Aguarde o Jenkins iniciar completamente**
2. **Acesse:** https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
3. **Faça login se necessário:**
   - Usuário: `admin`
   - Senha: `admin123`

---

### Passo 2: Encontrar Seção Pipeline

1. **Role a página para baixo** até encontrar a seção **"Pipeline"**
2. Procure por:
   - **Definition:** Pipeline script from SCM
   - **SCM:** Git
   - **Repository URL:** ← **ESTE É O CAMPO QUE PRECISA SER CORRIGIDO**

---

### Passo 3: Corrigir Repository URL

**No campo "Repository URL":**

1. **Selecione todo o texto atual** (provavelmente `https://github.com/user/repo.git` ou `http://github.com/user/repo.git`)
2. **Delete** (pressione Delete ou Backspace)
3. **Cole exatamente esta URL:**
   ```
   git@github.com:MatheusEstrela-dev/NewSDC.git
   ```

**⚠️ IMPORTANTE:**
- Use **SSH** (git@github.com), não HTTPS
- Certifique-se de que não há espaços extras
- A URL deve terminar com `.git`

---

### Passo 4: Verificar Script Path

**No campo "Script Path" (logo abaixo de Repository URL):**

1. **Verifique o valor atual**
2. **Se não estiver correto, altere para:**
   ```
   SDC/Jenkinsfile
   ```

**⚠️ IMPORTANTE:**
- Deve ser `SDC/Jenkinsfile` (com barra)
- Não deve ser apenas `Jenkinsfile`

---

### Passo 5: Verificar Credentials

**No campo "Credentials" (dropdown logo abaixo de Repository URL):**

1. Clique no dropdown
2. **Selecione:** `git-ssh-key`
3. **Se não aparecer:**
   - Veja o **Passo 6** abaixo para criar a credencial

---

### Passo 6: Verificar Branches

**No campo "Branches to build":**

- Deve estar: `*/main` (e possivelmente `*/develop`)
- Se não estiver, adicione: `*/main`

---

### Passo 7: Salvar Configuração

1. **Role até o final da página**
2. Clique no botão **"Save"** (ou "Salvar")
3. Aguarde a confirmação de que foi salvo
4. Você será redirecionado para a página do job

---

## 🔑 Se a Credencial `git-ssh-key` Não Existir

### Criar Credencial SSH:

1. **Acesse:** https://jenkinssdc.azurewebsites.net/credentials/
2. Clique em **"System"** (menu lateral)
3. Clique em **"Global credentials (unrestricted)"**
4. Clique em **"Add Credentials"** (ou "Add" → "Jenkins")

**Configure:**

- **Kind:** `SSH Username with private key`
- **Scope:** `Global`
- **ID:** `git-ssh-key`
- **Description:** `SSH Key for Git repositories - NewSDC`
- **Username:** `git`
- **Private Key:**
  - Selecione: **"Enter directly"**
  - Cole sua chave SSH privada do GitHub
- **Passphrase:** (deixe em branco se não tiver)

**Salve**

---

## ✅ Verificar se Funcionou

Após salvar:

1. **Volte para:** https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. Clique em **"Build Now"** para testar
3. Clique no build que aparecer
4. Clique em **"Console Output"**
5. **Verifique se aparece:**
   ```
   Checking out git git@github.com:MatheusEstrela-dev/NewSDC.git
   ```
   **E NÃO:**
   ```
   Checking out git https://github.com/user/repo.git
   ERROR: Error cloning remote repo 'origin'
   ```

---

## 📋 Checklist Rápido

- [ ] Jenkins iniciou completamente
- [ ] Acessei: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
- [ ] Encontrei a seção "Pipeline"
- [ ] Alterei "Repository URL" para: `git@github.com:MatheusEstrela-dev/NewSDC.git`
- [ ] Verifiquei que "Script Path" está: `SDC/Jenkinsfile`
- [ ] Verifiquei que "Credentials" está: `git-ssh-key`
- [ ] Verifiquei que "Branches to build" está: `*/main`
- [ ] Cliquei em "Save"
- [ ] Testei com "Build Now"
- [ ] Verifiquei que o checkout funciona

---

## 🎯 Resultado Esperado

Após corrigir, o build deve:

1. ✅ Fazer checkout do repositório corretamente
2. ✅ Encontrar o `SDC/Jenkinsfile`
3. ✅ Executar o pipeline
4. ✅ Build, push para ACR e deploy para produção

---

## 🚨 Se o Jenkins Estiver Iniciando

Se você ver a mensagem "Starting Jenkins":

1. **Aguarde 2-5 minutos** para o Jenkins iniciar completamente
2. **Atualize a página** (F5)
3. **Tente novamente** acessar a URL de configuração

---

**Status:** 🔴 **URGENTE - Corrigir assim que o Jenkins estiver pronto**  
**Tempo estimado:** 2-5 minutos  
**Impacto:** Sem isso, nenhum build funcionará



