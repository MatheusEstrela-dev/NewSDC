# 🔧 Corrigir URL do Repositório no Jenkins - URGENTE

## 🚨 Problema Identificado

O Jenkins ainda está usando a URL antiga:
- ❌ **URL atual:** `https://github.com/user/repo.git`
- ✅ **URL correta:** `git@github.com:MatheusEstrela-dev/NewSDC.git`

**Erro no build:**
```
ERROR: Error cloning remote repo 'origin'
fatal: Authentication failed for 'https://github.com/user/repo.git/'
```

---

## ✅ Solução: Corrigir Manualmente no Jenkins

### Passo 1: Acessar Configuração do Job

**URL:** https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure

1. Abra o link acima no navegador
2. Faça login se necessário (admin/admin123)

---

### Passo 2: Encontrar Seção "Pipeline"

Na página de configuração:

1. **Role a página para baixo** até encontrar a seção **"Pipeline"**
2. Procure por:
   - **Definition:** Pipeline script from SCM
   - **SCM:** Git
   - **Repository URL:** ← **ESTE É O CAMPO QUE PRECISA SER CORRIGIDO**

---

### Passo 3: Corrigir Repository URL

**No campo "Repository URL":**

1. **Selecione todo o texto atual** (provavelmente `https://github.com/user/repo.git`)
2. **Delete**
3. **Cole exatamente esta URL:**
   ```
   git@github.com:MatheusEstrela-dev/NewSDC.git
   ```

**⚠️ IMPORTANTE:**
- Use **SSH** (git@github.com), não HTTPS
- Certifique-se de que não há espaços extras
- A URL deve terminar com `.git`

---

### Passo 4: Verificar Credentials

**No campo "Credentials" (logo abaixo de Repository URL):**

1. Clique no dropdown
2. **Selecione:** `git-ssh-key`
3. **Se não aparecer:**
   - Veja o **Passo 5** abaixo para criar a credencial

---

### Passo 5: Verificar Outros Campos

**Branches to build:**
- Deve estar: `*/main`
- Se não estiver, altere para: `*/main`

**Script Path:**
- Deve estar: `SDC/Jenkinsfile`
- Se não estiver, altere para: `SDC/Jenkinsfile`

---

### Passo 6: Salvar

1. **Role até o final da página**
2. Clique no botão **"Save"** (ou "Salvar")
3. Aguarde a confirmação de que foi salvo

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
   ```

---

## 📋 Checklist Rápido

- [ ] Acessei: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
- [ ] Encontrei a seção "Pipeline"
- [ ] Alterei "Repository URL" para: `git@github.com:MatheusEstrela-dev/NewSDC.git`
- [ ] Verifiquei que "Credentials" está: `git-ssh-key`
- [ ] Verifiquei que "Branches to build" está: `*/main`
- [ ] Verifiquei que "Script Path" está: `SDC/Jenkinsfile`
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

**Status:** 🔴 **URGENTE - Corrigir agora**  
**Tempo estimado:** 2-5 minutos  
**Impacto:** Sem isso, nenhum build funcionará

---

## 🔍 Por Que o casc.yaml Não Foi Aplicado?

O arquivo `SDC/docker/jenkins/casc.yaml` está correto (linha 164), mas o Jenkins pode não ter aplicado a configuração por alguns motivos:

1. **Configuração manual sobrescreveu o JCasC:**
   - Se alguém configurou o job manualmente antes do JCasC ser carregado
   - O JCasC não sobrescreve configurações manuais existentes

2. **JCasC não foi recarregado:**
   - Após alterar o `casc.yaml`, é necessário recarregar a configuração

3. **Ordem de inicialização:**
   - Se o job foi criado manualmente antes do JCasC, ele mantém a configuração manual

### ✅ Após Corrigir Manualmente

Para garantir que a configuração seja mantida:

1. **Recarregar JCasC (Opcional):**
   - Acesse: https://jenkinssdc.azurewebsites.net/manage
   - Procure por **"Configuration as Code"** ou **"JCasC"**
   - Clique em **"Reload Configuration"** ou **"Apply new configuration"**
   - ⚠️ **ATENÇÃO:** Isso pode sobrescrever sua correção manual!

2. **Recomendação:**
   - **Mantenha a correção manual** por enquanto
   - O JCasC será aplicado corretamente na próxima inicialização do Jenkins
   - Ou atualize o `casc.yaml` para refletir a configuração atual

---

## 🛡️ Prevenir Problemas Futuros

Para evitar que isso aconteça novamente:

1. **Sempre use JCasC para configurações:**
   - Evite configurar jobs manualmente
   - Use o arquivo `casc.yaml` como fonte da verdade

2. **Verifique após deploy:**
   - Após fazer deploy do Jenkins, verifique se as configurações foram aplicadas
   - Use o checklist acima para validar

3. **Documente mudanças:**
   - Se precisar fazer mudanças manuais, atualize o `casc.yaml` também

