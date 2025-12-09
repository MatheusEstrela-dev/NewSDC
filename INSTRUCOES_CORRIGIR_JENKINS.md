# 🎯 INSTRUÇÕES - Corrigir Jenkins Script Path

## ⚠️ AÇÃO MANUAL NECESSÁRIA

O Jenkins não consegue encontrar o Jenkinsfile porque o **Script Path está configurado incorretamente**.

Esta correção **NÃO PODE** ser feita via código - você precisa fazer **manualmente** na interface web do Jenkins.

---

## 🔧 PASSO A PASSO (5 minutos)

### 1️⃣ Fazer Login no Jenkins

**URL:** https://jenkinssdc.azurewebsites.net/

**Credenciais:**
- Username: `admin`
- Password: `<sua senha do Jenkins>`

---

### 2️⃣ Acessar a Configuração do Job

**URL Direta:** https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure

**Ou navegue:**
1. Dashboard → SDC → build-and-deploy
2. Clique no menu lateral: **"Configure"**

---

### 3️⃣ Localizar a Seção "Pipeline"

1. Role a página **até o final**
2. Encontre a seção **"Pipeline"**
3. Você verá:

```
Definition: Pipeline script from SCM
SCM: Git
  Repositories
    Repository URL: https://github.com/MatheusEstrela-dev/NewSDC.git
    Credentials: git-ssh-key
  Branches to build
    Branch Specifier: */main
Script Path: ________  ← ESTE CAMPO
```

---

### 4️⃣ Corrigir o Campo "Script Path"

**Localize o campo:** `Script Path`

**Valor ATUAL (errado):**
```
Jenkinsfile
```

**Apague e digite o valor CORRETO:**
```
SDC/Jenkinsfile
```

**IMPORTANTE:** 
- Digite exatamente: `SDC/Jenkinsfile`
- Sem espaços antes ou depois
- Com a barra `/` separando
- Sem ponto no início

---

### 5️⃣ Salvar a Configuração

1. Role até o **final da página**
2. Clique no botão **"Save"** (azul, canto inferior esquerdo)
3. Aguarde a página recarregar

---

### 6️⃣ Disparar Novo Build

**Opção A - Via Interface (Recomendado):**

1. No menu lateral, clique em **"Build Now"**
2. Aguarde alguns segundos
3. Um novo build (#12) aparecerá na lista "Build History"
4. Clique no número do build (#12)
5. Clique em **"Console Output"**

**Opção B - Via URL Direta:**

Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/build

---

## ✅ Como Saber Se Funcionou?

### Console Output do Build #12 - Início:

**❌ ANTES (Build #11 - Errado):**
```
ERROR: /var/jenkins_home/workspace/.../Jenkinsfile not found
Finished: FAILURE
```

**✅ DEPOIS (Build #12 - Correto):**
```
Checking out Revision 744be02...
Commit message: "docs: adicionar guia para corrigir Script Path do Jenkins"

[Pipeline] Start of Pipeline
[Pipeline] node
[Pipeline] {
[Pipeline] stage
[Pipeline] { (Declarative: Checkout SCM)
[Pipeline] checkout
Selected Git installation does not exist. Using Default
...
[Pipeline] stage { (Checkout)
📦 Checking out code...
Commit: docs: adicionar guia para corrigir Script Path do Jenkins
Author: MatheusEstrela-dev
```

Se você ver `[Pipeline] Start of Pipeline`, significa que o Jenkinsfile foi **encontrado com sucesso**! ✅

---

## 🐛 Troubleshooting

### Problema: Não consigo acessar /configure

**Causa:** Usuário não tem permissão de administrador

**Solução:** Use o usuário `admin` para fazer login

---

### Problema: Não vejo o campo "Script Path"

**Causa:** Está na seção errada

**Solução:** 
1. Role até o **final da página**
2. A seção "Pipeline" é a **última seção**
3. Fica depois de "Build Triggers", "Build Environment", etc.

---

### Problema: Depois de salvar, volta ao valor antigo

**Causa:** Possível cache do navegador

**Solução:**
1. Pressione `Ctrl+F5` para refresh completo
2. Acesse `/configure` novamente
3. Verifique se o valor está correto
4. Se ainda estiver errado, limpe cookies do navegador

---

### Problema: Build #12 ainda falha com "Jenkinsfile not found"

**Causa 1:** Script Path não foi salvo corretamente

**Verificar:**
```bash
# Acessar Jenkins via SSH e verificar config.xml
cat /var/jenkins_home/jobs/SDC/jobs/build-and-deploy/config.xml | grep scriptPath
```

**Deve mostrar:**
```xml
<scriptPath>SDC/Jenkinsfile</scriptPath>
```

**Causa 2:** Jenkinsfile não está no repositório

**Verificar:**
```bash
cd "c:\Users\kdes\Documentos\GitHub\New_SDC"
ls SDC/Jenkinsfile
```

Se o arquivo existir, está tudo certo. O problema é apenas configuração do Jenkins.

---

## 📊 Status Esperado Após Correção

### Build #12 deve executar:

```
✅ Stage: Checkout
✅ Stage: Pre-flight Checks
⏳ Stage: Build and Push to ACR  ← Pode falhar se permissões ainda não propagaram
⏳ Stage: Deploy to Azure App Service
```

**Se Build and Push to ACR falhar:**
- ✅ Significa que o Jenkinsfile foi encontrado (problema anterior resolvido!)
- ⏳ Problema agora é permissões ACR (aguardar propagação ou fazer restart Jenkins)

---

## 📋 Checklist Final

Marque conforme executar:

- [ ] 1. Login no Jenkins (https://jenkinssdc.azurewebsites.net/)
- [ ] 2. Acessar /configure do job build-and-deploy
- [ ] 3. Localizar seção "Pipeline" (final da página)
- [ ] 4. Alterar "Script Path" de `Jenkinsfile` para `SDC/Jenkinsfile`
- [ ] 5. Clicar em "Save"
- [ ] 6. Clicar em "Build Now"
- [ ] 7. Acessar Console Output do Build #12
- [ ] 8. Verificar que mostra "[Pipeline] Start of Pipeline"
- [ ] 9. Se Build #12 passar do checkout, problema resolvido!

---

## 🎯 Próximos Passos (Após Correção)

### Se Build #12 falhar no ACR (authorization):

Execute:
```bash
az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
```

Aguarde 2 minutos e dispare Build #13.

### Se Build #12 completar com sucesso:

🎉 **PIPELINE FUNCIONANDO!**

Verificar aplicação em produção:
```
https://newsdc2027.azurewebsites.net/login
```

---

**⚡ EXECUTE AGORA:**

1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
2. Altere Script Path para: `SDC/Jenkinsfile`
3. Save
4. Build Now

**Tempo estimado:** 2 minutos
