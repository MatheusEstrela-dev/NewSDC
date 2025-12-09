# 🔧 CORRIGIR - Script Path do Jenkinsfile

## ❌ Erro Identificado - Build #11

### Erro no Console:

```
ERROR: /var/jenkins_home/workspace/SDC/build-and-deploy@script/.../Jenkinsfile not found
Finished: FAILURE
```

### Causa Raiz:

O Jenkins está procurando o Jenkinsfile no **caminho errado**.

✅ **Correto:** `SDC/Jenkinsfile`
❌ **Atual:** `Jenkinsfile` (raiz do repositório)

---

## ✅ SOLUÇÃO - Corrigir Script Path no Jenkins

### Passo a Passo:

#### 1. Acessar Configuração do Job

**URL:** https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure

#### 2. Localizar Seção "Pipeline"

Role a página até encontrar a seção **"Pipeline"** (final da página).

#### 3. Verificar Campo "Script Path"

**Campo atual (ERRADO):**
```
Script Path: Jenkinsfile
```

**Deve estar:**
```
Script Path: SDC/Jenkinsfile
```

#### 4. Corrigir o Valor

**Antes:**
```
Jenkinsfile
```

**Depois:**
```
SDC/Jenkinsfile
```

#### 5. Salvar

Clique em **"Save"** no final da página.

#### 6. Executar Novo Build

Clique em **"Build Now"** para disparar Build #12.

---

## 📊 Configuração Correta

### Seção Pipeline:

```
Pipeline
├─ Definition: Pipeline script from SCM
├─ SCM: Git
│  └─ Repository URL: https://github.com/MatheusEstrela-dev/NewSDC.git
│  └─ Credentials: git-ssh-key
│  └─ Branch: */main
└─ Script Path: SDC/Jenkinsfile  ← CORRETO
```

---

## 🔍 Estrutura do Repositório

```
NewSDC/
├─ README.md
├─ SDC/
│  ├─ Jenkinsfile          ← Arquivo está AQUI
│  ├─ app/
│  ├─ database/
│  ├─ docker/
│  └─ ...
└─ Doc/
```

**Script Path correto:** `SDC/Jenkinsfile`

---

## ✅ Checklist

- [ ] Acessar https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
- [ ] Localizar campo "Script Path"
- [ ] Alterar de `Jenkinsfile` para `SDC/Jenkinsfile`
- [ ] Clicar em "Save"
- [ ] Clicar em "Build Now"
- [ ] Verificar Build #12 carrega o Jenkinsfile corretamente

---

**Status:** 🔴 **Build #11 falhou - Script Path incorreto**

**Próximo passo:** Corrigir Script Path e executar Build #12!
