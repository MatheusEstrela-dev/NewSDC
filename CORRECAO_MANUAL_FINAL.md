# 🎯 Correção Manual - 30 Segundos

## ⚠️ CSRF Protection Ativo

A proteção CSRF do Jenkins está bloqueando a atualização automática via API.

**Solução:** Correção manual (mais rápida que bypassing CSRF)

---

## ✅ Passo a Passo (30 segundos)

### 1. Acesse:
```
https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
```

### 2. Na seção "Pipeline":

**Encontre "Credentials":**
- Atual: `git-ssh-key`
- **Altere para:** `github-token` (selecione no dropdown)

**Encontre "Script Path":**
- Atual: `Jenkinsfile`
- **Altere para:** `SDC/Jenkinsfile`

### 3. Clique em "Save" (final da página)

### 4. Teste:
```
Clique em "Build Now"
```

---

## ✅ Verificação

Após salvar, o próximo build deve mostrar:

```
✅ using credential github-token
✅ Cloning repository https://github.com/MatheusEstrela-dev/NewSDC.git
✅ Loading Jenkinsfile from SDC/Jenkinsfile
[Pipeline] Start of Pipeline
```

---

## 📊 Configuração Atual vs Correta

| Campo | Atual | Correto |
|-------|-------|---------|
| **URL** | ✅ https://github.com/MatheusEstrela-dev/NewSDC.git | ✅ OK |
| **Credentials** | ❌ git-ssh-key | ✅ github-token |
| **Script Path** | ❌ Jenkinsfile | ✅ SDC/Jenkinsfile |
| **Branches** | ✅ */main, */develop | ✅ OK |

---

**Tempo estimado:** 30 segundos
**Dificuldade:** Muito fácil
**Resultado:** Pipeline funcionando 100%! 🚀
