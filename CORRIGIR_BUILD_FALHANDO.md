# 🔧 Corrigir Build Falhando no Jenkins

## 🚨 Problema Identificado

O build #5 falhou e o job nunca teve sucesso. Possíveis causas:

1. **Configuração do Jenkins não foi recarregada** (casc.yaml não aplicado)
2. **Credencial SSH não configurada** (`git-ssh-key` não existe)
3. **URL do repositório incorreta** no job

---

## ✅ Solução Passo a Passo

### Opção 1: Recarregar Configuração do Jenkins (Recomendado)

#### Via Interface Web:

1. **Acesse o Jenkins:**
   ```
   https://jenkinssdc.azurewebsites.net/manage
   ```

2. **Vá em Configuration as Code:**
   - Clique em **Manage Jenkins**
   - Procure por **Configuration as Code** ou **JCasC**
   - Clique em **Reload configuration**

3. **Verifique se o job foi atualizado:**
   - Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
   - Verifique se a URL do repositório está: `git@github.com:MatheusEstrela-dev/NewSDC.git`
   - Verifique se o Script Path está: `SDC/Jenkinsfile`

#### Via Azure CLI (se tiver acesso):

```bash
# Reiniciar o App Service do Jenkins
az webapp restart \
    --name jenkinssdc \
    --resource-group DEFESA_CIVIL
```

---

### Opção 2: Configurar Job Manualmente

Se o casc.yaml não estiver sendo aplicado, configure manualmente:

1. **Acesse o job:**
   ```
   https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
   ```

2. **Na seção "Pipeline":**
   - **Definition:** Pipeline script from SCM
   - **SCM:** Git
   - **Repository URL:** `git@github.com:MatheusEstrela-dev/NewSDC.git`
   - **Credentials:** Selecione `git-ssh-key` (ou crie se não existir)
   - **Branches to build:** `*/main`
   - **Script Path:** `SDC/Jenkinsfile`

3. **Salve** (Save)

---

### Opção 3: Configurar Credencial SSH

Se a credencial `git-ssh-key` não existir:

1. **Acesse Credentials:**
   ```
   https://jenkinssdc.azurewebsites.net/credentials/
   ```

2. **Clique em "Add Credentials"**

3. **Configure:**
   - **Kind:** SSH Username with private key
   - **Scope:** Global
   - **ID:** `git-ssh-key`
   - **Username:** `git`
   - **Private Key:** 
     - Selecione "Enter directly"
     - Cole a chave SSH privada do GitHub
     - Ou selecione "From the Jenkins master ~/.ssh" se já existir

4. **Description:** "SSH Key for Git repositories"

5. **Salve**

#### Como obter a chave SSH privada:

**Se você já tem uma chave SSH:**
```bash
# No seu computador
cat ~/.ssh/id_rsa
# ou
cat ~/.ssh/id_ed25519
```

**Se não tem, crie uma:**
```bash
# Gerar nova chave SSH
ssh-keygen -t ed25519 -C "jenkins@jenkinssdc"

# Copiar chave privada
cat ~/.ssh/id_ed25519

# Adicionar chave pública no GitHub
cat ~/.ssh/id_ed25519.pub
# Depois adicione em: https://github.com/MatheusEstrela-dev/NewSDC/settings/keys
```

---

### Opção 4: Usar HTTPS com Token (Alternativa)

Se SSH não funcionar, use HTTPS com Personal Access Token:

1. **Criar Personal Access Token no GitHub:**
   - Acesse: https://github.com/settings/tokens
   - Clique em "Generate new token (classic)"
   - Permissões: `repo` (acesso completo aos repositórios)
   - Copie o token

2. **Configurar Credencial no Jenkins:**
   - **Kind:** Username with password
   - **Username:** Seu usuário do GitHub (`MatheusEstrela-dev`)
   - **Password:** O token gerado
   - **ID:** `github-token`

3. **Atualizar casc.yaml:**
   ```yaml
   remote {
     url('https://github.com/MatheusEstrela-dev/NewSDC.git')
     credentials('github-token')
   }
   ```

4. **Recarregar configuração**

---

## 🔍 Verificar se Está Funcionando

### 1. Verificar Configuração do Job

Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure

**Verifique:**
- ✅ Repository URL: `git@github.com:MatheusEstrela-dev/NewSDC.git`
- ✅ Credentials: `git-ssh-key` (ou outra credencial)
- ✅ Script Path: `SDC/Jenkinsfile`
- ✅ Branches: `*/main`

### 2. Testar Checkout Manualmente

No Jenkins, vá em:
- **SDC/build-and-deploy** → **Pipeline Syntax**
- Teste o checkout com as credenciais

### 3. Disparar Build Manual

1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. Clique no botão **"Build Now"** (ícone de play verde)
3. Monitore os logs em tempo real

### 4. Verificar Logs do Build

Clique no build e veja o "Console Output":

**Se funcionar, você verá:**
```
📦 Checking out code...
Cloning repository git@github.com:MatheusEstrela-dev/NewSDC.git
Commit: test: CI/CD - alteração mínima no footer...
```

**Se falhar, você verá:**
```
ERROR: Error cloning remote repo 'origin'
fatal: Authentication failed
```

---

## 🚀 Após Corrigir

1. **Disparar novo build:**
   - Clique em **"Build Now"** no job
   - Ou faça um novo commit (o webhook disparará automaticamente)

2. **Monitorar:**
   - Acompanhe os logs em tempo real
   - Verifique cada stage

3. **Aguardar deploy:**
   - Build: 5-15 minutos
   - Deploy: 5-10 minutos
   - Total: ~10-25 minutos

---

## 📋 Checklist de Verificação

- [ ] Configuração do Jenkins recarregada (ou job configurado manualmente)
- [ ] Credencial SSH configurada (`git-ssh-key`)
- [ ] URL do repositório correta no job
- [ ] Script Path correto (`SDC/Jenkinsfile`)
- [ ] Build manual disparado
- [ ] Checkout funcionou (sem erros de autenticação)
- [ ] Build completou com sucesso

---

## 🆘 Se Ainda Não Funcionar

### Verificar Logs Detalhados

1. Acesse o build que falhou
2. Veja o "Console Output" completo
3. Procure por mensagens de erro específicas

### Verificar Conectividade do Jenkins

O Jenkins precisa conseguir acessar:
- GitHub (porta 22 para SSH ou 443 para HTTPS)
- Azure (para push no ACR e deploy)

### Verificar Permissões

- A chave SSH precisa ter permissão de leitura no repositório
- O Service Principal precisa ter permissões no ACR e App Service

---

## 📝 Resumo Rápido

**Problema:** Build falhando no checkout

**Solução mais rápida:**
1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
2. Verifique/configure:
   - Repository URL: `git@github.com:MatheusEstrela-dev/NewSDC.git`
   - Credentials: `git-ssh-key`
   - Script Path: `SDC/Jenkinsfile`
3. Salve
4. Clique em "Build Now"

**Se a credencial não existir:**
1. Acesse: https://jenkinssdc.azurewebsites.net/credentials/
2. Adicione credencial SSH com sua chave privada
3. Volte e configure o job

---

**Última atualização:** $(date)

