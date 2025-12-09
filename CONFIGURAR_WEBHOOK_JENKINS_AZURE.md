# 🔗 Configurar Webhook GitHub → Jenkins Azure

## 🎯 Objetivo
Configurar webhook para que commits no GitHub disparem automaticamente o build no Jenkins hospedado no Azure.

---

## ✅ Informações do Setup

- **Jenkins URL:** https://jenkinssdc.azurewebsites.net/
- **Job Path:** `SDC/build-and-deploy`
- **Webhook URL:** `https://jenkinssdc.azurewebsites.net/github-webhook/`
- **Repositório:** https://github.com/MatheusEstrela-dev/NewSDC

---

## 📝 Passos para Configurar

### Passo 1: Acessar Settings do Repositório GitHub

1. Acesse: https://github.com/MatheusEstrela-dev/NewSDC/settings/hooks
2. Clique em **"Add webhook"** (botão verde no canto superior direito)

### Passo 2: Configurar Webhook

Preencha os campos:

```
Payload URL: https://jenkinssdc.azurewebsites.net/github-webhook/
Content type: application/json
Secret: (deixe vazio por enquanto)
```

**Which events would you like to trigger this webhook?**
- Selecione: ☑️ **Just the push event**

**Active**
- Marque: ☑️ **Active**

### Passo 3: Salvar

Clique em **"Add webhook"**

---

## 🔍 Verificar Configuração

### No GitHub:

1. Vá em: https://github.com/MatheusEstrela-dev/NewSDC/settings/hooks
2. Clique no webhook que você criou
3. Role até **"Recent Deliveries"**
4. Procure por um evento de teste (GitHub envia automaticamente)
5. Verifique se:
   - ✅ Status: **200 OK** (verde) = funcionando
   - ❌ Status: **4xx/5xx** (vermelho) = erro

**Se houver erro:**
- Verifique se o Jenkins está acessível: https://jenkinssdc.azurewebsites.net/
- Verifique se a URL do webhook está correta (tem a barra no final: `/github-webhook/`)

---

## 🧪 Testar o Webhook

### Teste 1: Redeliver no GitHub

1. No webhook, vá em "Recent Deliveries"
2. Clique em um delivery
3. Clique em **"Redeliver"**
4. Verifique se o status é 200 OK

### Teste 2: Commit de Teste

```bash
cd c:\Users\kdes\Documentos\GitHub\New_SDC

# Fazer uma alteração mínima
echo "" >> README.md

# Commit e push
git add README.md
git commit -m "test: verificar webhook GitHub -> Jenkins Azure"
git push origin main
```

**Verificar no Jenkins:**
1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. Em 30 segundos a 2 minutos, um novo build deve aparecer
3. Clique no build para ver os logs
4. Logs devem mostrar: **"Started by GitHub push"**

---

## 🔐 Verificar Credenciais no Jenkins (Opcional)

Se o build falhar por falta de credenciais:

1. **Acesse:** https://jenkinssdc.azurewebsites.net/manage
2. **Login:**
   - User: `admin`
   - Password: `admin123` (ou a senha configurada)
3. **Vá em:** Manage Jenkins → Credentials → System → Global credentials
4. **Verifique se existem:**
   - `azure-service-principal` (para ACR)
   - `git-ssh-key` (para GitHub, se usar SSH)

**Se não existirem, adicione:**

### Adicionar azure-service-principal:

1. Add Credentials
2. Kind: **Secret text**
3. Scope: **Global**
4. Secret: (copie de `SDC/docker/.env.jenkins`)
5. ID: `azure-service-principal`
6. Description: `Azure Service Principal for ACR`

---

## 🐛 Troubleshooting

### Webhook retorna 404

**Problema:** URL incorreta

**Solução:**
```
✅ Correto:   https://jenkinssdc.azurewebsites.net/github-webhook/
❌ Incorreto: https://jenkinssdc.azurewebsites.net/github-webhook
❌ Incorreto: https://jenkinssdc.azurewebsites.net/webhook/
```

### Webhook retorna 403

**Problema:** Jenkins requer autenticação

**Solução:**
- Desabilitar CSRF protection para webhooks (não recomendado)
- Ou: Configurar GitHub App integration
- Ou: Usar token de autenticação na URL

### Build não dispara automaticamente

**Problema:** Job não está configurado para webhooks

**Solução:**
1. Acesse o job: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/configure
2. Verifique **Build Triggers**
3. Marque: ☑️ **GitHub hook trigger for GITScm polling**
4. Salvar

### Jenkins está offline

**Problema:** App Service do Jenkins parou

**Solução:**
```bash
# Verificar status
az webapp show --name jenkinssdc --resource-group DEFESA_CIVIL --query state

# Reiniciar se necessário
az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL
```

---

## 📊 Fluxo Completo

```
┌─────────────┐
│  Developer  │
│  git push   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   GitHub    │ (webhook trigger)
│   main      │
└──────┬──────┘
       │
       ▼
┌─────────────────────┐
│   Jenkins Azure     │
│ jenkinssdc.azure... │
└──────┬──────────────┘
       │
       ├─► Checkout código
       ├─► Build Docker images
       ├─► Push para ACR (apidover.azurecr.io)
       └─► Deploy para App Service (newsdc2027)
```

---

## ✅ Checklist

- [ ] Webhook configurado no GitHub
- [ ] URL correta: `https://jenkinssdc.azurewebsites.net/github-webhook/`
- [ ] Content type: `application/json`
- [ ] Event: "Just the push event"
- [ ] Active: marcado
- [ ] Teste de delivery retorna 200 OK
- [ ] Commit de teste dispara build automaticamente
- [ ] Job configurado para "GitHub hook trigger"
- [ ] Credenciais Azure configuradas no Jenkins

---

## 🎯 Próximos Passos

Após configurar o webhook:

1. **Fazer commit de teste** para verificar se o pipeline dispara
2. **Monitorar build** no Jenkins
3. **Verificar deploy** em https://newsdc2027.azurewebsites.net/
4. **Verificar logs** se houver falha

---

<div align="center">

**🔗 Webhook Configuration Complete**

*Jenkins Azure + GitHub Integration*

</div>
