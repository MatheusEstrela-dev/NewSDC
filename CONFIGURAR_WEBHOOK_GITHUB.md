# 🔗 Como Configurar Webhook GitHub para Jenkins Local

## 🎯 Problema
O Jenkins está rodando em `http://localhost:8080`, mas o GitHub precisa de uma URL pública para enviar webhooks.

## ✅ Solução: Usar ngrok

### Opção 1: ngrok (Recomendado)

#### Passo 1: Instalar ngrok

**Windows (PowerShell como Administrador):**
```powershell
# Opção A: Chocolatey
choco install ngrok

# Opção B: Download manual
# 1. Baixe de: https://ngrok.com/download
# 2. Extraia o arquivo
# 3. Mova ngrok.exe para C:\Windows\System32
```

**Verificar instalação:**
```bash
ngrok version
```

#### Passo 2: Criar conta no ngrok (Gratuito)

1. Acesse: https://dashboard.ngrok.com/signup
2. Crie uma conta gratuita
3. Obtenha seu authtoken em: https://dashboard.ngrok.com/get-started/your-authtoken

#### Passo 3: Configurar authtoken

```bash
ngrok config add-authtoken SEU_TOKEN_AQUI
```

#### Passo 4: Expor Jenkins

```bash
ngrok http 8080
```

Você verá algo como:
```
Forwarding   https://abc123.ngrok.io -> http://localhost:8080
```

**⚠️ IMPORTANTE:** Mantenha esta janela aberta enquanto usar o webhook!

#### Passo 5: Configurar Webhook no GitHub

1. Acesse seu repositório: https://github.com/SEU_USUARIO/New_SDC
2. Vá em: **Settings** → **Webhooks** → **Add webhook**
3. Configure:

```
Payload URL: https://abc123.ngrok.io/github-webhook/
Content type: application/json
Secret: (deixe vazio por enquanto)
Which events: Just the push event
Active: ✅ Marcado
```

4. Clique em **Add webhook**

#### Passo 6: Verificar Jenkins

1. Acesse Jenkins: http://localhost:8080
2. Login: `admin` / `admin123`
3. Vá em: **Manage Jenkins** → **Manage Credentials**
4. Verifique se as credenciais Azure foram carregadas

#### Passo 7: Criar/Configurar Job

**Opção A: Criar Job Manualmente**

1. Jenkins → **New Item**
2. Nome: `sdc-cicd-pipeline`
3. Tipo: **Pipeline**
4. Configure:
   - **Build Triggers**: ✅ GitHub hook trigger for GITScm polling
   - **Pipeline**:
     - Definition: Pipeline script from SCM
     - SCM: Git
     - Repository URL: `https://github.com/SEU_USUARIO/New_SDC.git`
     - Branch: `*/main`
     - Script Path: `SDC/Jenkinsfile`

**Opção B: Usar Job Automático (JCasC)**

O job já deve estar criado como `SDC/build-and-deploy` se o JCasC estiver funcionando.

#### Passo 8: Testar Webhook

```bash
# Fazer um commit de teste
cd c:\Users\kdes\Documentos\GitHub\New_SDC
echo "# Test webhook" >> README.md
git add README.md
git commit -m "test: Trigger Jenkins webhook"
git push origin main
```

**Verificar no GitHub:**
1. Settings → Webhooks → Seu webhook
2. Clique no webhook
3. Role até **Recent Deliveries**
4. Verifique se há uma entrega com status 200 (verde)

**Verificar no Jenkins:**
1. Vá em Jenkins → sdc-cicd-pipeline (ou SDC/build-and-deploy)
2. Verifique se um novo build foi iniciado
3. Logs devem mostrar: "Started by GitHub push"

---

## 🔄 Opção 2: Cloudflare Tunnel (Alternativa)

Se preferir uma solução mais estável:

```bash
# Instalar cloudflared
# Windows: Download de https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation

# Criar túnel
cloudflared tunnel --url http://localhost:8080
```

---

## 🐛 Troubleshooting

### Webhook retorna erro 403/404

**Problema:** URL incorreta ou Jenkins não está acessível

**Solução:**
```bash
# Testar se ngrok está funcionando
curl https://sua-url.ngrok.io/login

# Deve retornar o HTML da página de login do Jenkins
```

### Webhook não dispara build

**Problema:** Job não está configurado para receber webhooks

**Solução:**
1. Job → Configure
2. Build Triggers → ✅ GitHub hook trigger for GITScm polling
3. Salvar

### ngrok está pedindo upgrade (erro 429)

**Problema:** Limite de requisições do plano gratuito

**Solução:**
- Usar conta autenticada do ngrok
- Ou: Reduzir frequência de pushes
- Ou: Usar Cloudflare Tunnel

---

## 📝 Resumo das URLs

```
Jenkins Local:  http://localhost:8080
Jenkins Público: https://abc123.ngrok.io  (muda a cada reinício do ngrok)
Webhook URL:    https://abc123.ngrok.io/github-webhook/
```

---

## ⚠️ Notas Importantes

1. **URL do ngrok muda**: Na versão gratuita, a URL muda toda vez que você reinicia o ngrok
   - Solução: Use domínio estático (plano pago) ou atualize o webhook cada vez

2. **Segurança**: ngrok expõe seu Jenkins publicamente
   - Use credenciais fortes
   - Configure um Secret no webhook (opcional mas recomendado)

3. **Jenkins deve estar rodando**: Mantenha o container do Jenkins ativo
   ```bash
   docker ps | grep jenkins
   ```

---

## 🎯 Checklist Final

- [ ] ngrok instalado
- [ ] ngrok autenticado com authtoken
- [ ] ngrok rodando (`ngrok http 8080`)
- [ ] Webhook configurado no GitHub com URL do ngrok
- [ ] Job Jenkins configurado para receber webhooks
- [ ] Teste realizado com commit

---

## 📚 Próximos Passos

Após configurar o webhook:

1. **Testar Pipeline Completo**:
   - Push → Jenkins build → Docker build → Push to ACR

2. **Verificar Logs**:
   ```bash
   # Logs do Jenkins
   docker logs sdc_jenkins_dev -f

   # Verificar imagem no ACR
   az acr repository show-tags --name apidover --repository sdc-dev-app
   ```

3. **Configurar Deploy Automático** (opcional):
   - Adicionar stage de deploy no Jenkinsfile
   - Deploy para Azure App Service

---

<div align="center">

**🔗 Webhook Configuration Guide**

*Última atualização: 2025-12-08*

</div>
