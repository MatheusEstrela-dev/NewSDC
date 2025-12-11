# 🔑 Adicionar GitHub Token no Azure App Service

## ✅ Token GitHub já foi gerado

Você já tem um token com as permissões corretas:
- ☑️ `repo` - Full control of private repositories
- ☑️ `workflow` - Update GitHub Action workflows

**Expira:** Tue, Jan 27 2026

---

## 🎯 Próximo Passo: Adicionar Token no Azure

Agora você precisa copiar o token e executar o comando abaixo.

### 1. Copiar o Token

**No GitHub:**
- O token está na página que você mostrou
- Formato: `ghp_...` (começa com `ghp_`)
- **COPIE O TOKEN AGORA** (você não verá novamente!)

---

### 2. Adicionar no Azure App Service

**Execute no terminal:**

```bash
# Substitua <SEU_TOKEN_AQUI> pelo token que você copiou
az webapp config appsettings set --name jenkinssdc --resource-group DEFESA_CIVIL \
  --settings \
  GITHUB_USERNAME="MatheusEstrela-dev" \
  GITHUB_TOKEN="<SEU_TOKEN_AQUI>"
```

**Exemplo (NÃO use este token, use o seu):**
```bash
az webapp config appsettings set --name jenkinssdc --resource-group DEFESA_CIVIL \
  --settings \
  GITHUB_USERNAME="MatheusEstrela-dev" \
  GITHUB_TOKEN="ghp_abc123xyz..."
```

---

### 3. Verificar se foi adicionado

```bash
az webapp config appsettings list --name jenkinssdc --resource-group DEFESA_CIVIL \
  --query "[?name=='GITHUB_TOKEN' || name=='GITHUB_USERNAME'].{Name:name}" -o table
```

**Resultado esperado:**
```
Name
-----------------
GITHUB_USERNAME
GITHUB_TOKEN
```

---

### 4. Reiniciar Jenkins

```bash
az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
```

**Aguardar:** ~1-2 minutos para reiniciar

---

### 5. Testar Build

**Opção A: Build Manual**
1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. Clique em **"Build Now"**
3. Clique no build que aparecer
4. Clique em **"Console Output"**

**Opção B: Fazer Commit**
```bash
cd c:\Users\kdes\Documentos\GitHub\New_SDC
echo "# Test" >> TESTE_TOKEN.md
git add TESTE_TOKEN.md
git commit -m "test: verificar autenticação HTTPS com GitHub token"
git push origin main
```

---

## 📋 Checklist

- [x] Token gerado no GitHub (com scope `repo` e `workflow`)
- [ ] Token copiado
- [ ] Token adicionado no Azure App Service
- [ ] Verificado que foi adicionado
- [ ] Jenkins reiniciado
- [ ] Build testado
- [ ] Logs verificados

---

## ✅ Logs Esperados (Sucesso)

```
Started by user admin
Checking out git https://github.com/MatheusEstrela-dev/NewSDC.git
 > git fetch --tags --force --progress
✅ Checking out Revision abc123...
✅ SUCCESS
```

---

## ❌ Se Houver Erro

**Erro de autenticação:**
```
ERROR: Authentication failed
fatal: Authentication failed for 'https://github.com/MatheusEstrela-dev/NewSDC.git/'
```

**Soluções:**
1. Verifique se o token foi copiado corretamente (não pode ter espaços)
2. Verifique se o token tem permissão `repo`
3. Verifique se o token não expirou
4. Gere um novo token se necessário

---

**Status:** 🟡 **Aguardando adicionar token no Azure**
**Próxima ação:** Copiar token e executar comando acima
**Tempo estimado:** 2-3 minutos
