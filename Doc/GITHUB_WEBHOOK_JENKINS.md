# 🔗 Configuração de Webhook GitHub → Jenkins

Este guia explica como configurar o webhook do GitHub para disparar automaticamente o pipeline do Jenkins quando houver push ou pull request.

---

## 📋 Pré-requisitos

1. **Jenkins rodando e acessível**

   - URL pública ou com túnel (ngrok, etc.)
   - Porta padrão: `8080`

2. **Plugin GitHub instalado no Jenkins**

   - Plugin: `GitHub Plugin`
   - Plugin: `GitHub Branch Source Plugin`

3. **Repositório GitHub configurado**
   - Acesso ao repositório
   - Permissões de administrador ou webhook

---

## 🔧 Passo 1: Configurar Jenkins para Receber Webhooks

### 1.1. Instalar Plugins Necessários

1. Acesse Jenkins: `http://seu-jenkins:8080`
2. Vá em **Manage Jenkins** → **Manage Plugins**
3. Na aba **Available**, procure e instale:
   - ✅ **GitHub Plugin**
   - ✅ **GitHub Branch Source Plugin**
   - ✅ **GitHub API Plugin**

### 1.2. Configurar GitHub Server

1. **Manage Jenkins** → **Configure System**
2. Role até a seção **GitHub**
3. Clique em **Add GitHub Server**
4. Configure:

   - **Name**: `GitHub` (ou nome personalizado)
   - **API URL**: `https://api.github.com` (padrão)
   - **Credentials**: Adicione credenciais do GitHub
     - Tipo: **Secret text** (Personal Access Token)
     - Ou: **Username with password**

5. **Test connection** para verificar

### 1.3. Habilitar Webhook no Job

1. Acesse o job: **SDC/build-and-deploy**
2. Clique em **Configure**
3. Em **Build Triggers**, marque:
   - ✅ **GitHub hook trigger for GITScm polling**
   - ✅ **Build when a change is pushed to GitHub**

---

## 🔗 Passo 2: Configurar Webhook no GitHub

### 2.1. Obter URL do Webhook

A URL do webhook do Jenkins segue o padrão:

```
http://seu-jenkins:8080/github-webhook/
```

**⚠️ Importante**:

- Se o Jenkins estiver em rede local, você precisará de uma URL pública
- Opções: ngrok, Cloudflare Tunnel, ou IP público

### 2.2. Criar Webhook no GitHub

1. Acesse seu repositório no GitHub
2. Vá em **Settings** → **Webhooks**
3. Clique em **Add webhook**

4. Configure o webhook:

   | Campo            | Valor                                     |
   | ---------------- | ----------------------------------------- | --------------- |
   | **Payload URL**  | `http://seu-jenkins:8080/github-webhook/` |
   | **Content type** | `application/json`                        |
   | **Secret**       | (Opcional) Token secreto para segurança   |
   | **Which events** | Selecione:                                |
   |                  | ✅ **Just the push event** (recomendado)  |
   |                  | Ou: **Let me select individual events**   |
   |                  |                                           | ✅ Push         |
   |                  |                                           | ✅ Pull request |

5. Clique em **Add webhook**

### 2.3. Eventos Recomendados

Para pipeline completo, selecione:

- ✅ **Push** - Dispara em push para qualquer branch
- ✅ **Pull request** - Dispara em abertura/fechamento de PR
- ✅ **Pull request review** - (Opcional) Para aprovações

---

## 🔐 Passo 3: Configurar Segurança (Opcional mas Recomendado)

### 3.1. Criar Secret no GitHub

1. No webhook, adicione um **Secret**
2. Gere um token seguro:

   ```bash
   openssl rand -hex 32
   ```

3. Copie o token gerado

### 3.2. Configurar Secret no Jenkins

1. **Manage Jenkins** → **Configure System**
2. Em **GitHub** → **Advanced**
3. Configure:
   - **Shared secret**: Cole o token gerado
   - **Override Hook URL**: (Deixe vazio para usar padrão)

### 3.3. Atualizar Webhook no GitHub

1. Edite o webhook criado
2. Cole o mesmo **Secret** no campo correspondente
3. Salve

---

## ✅ Passo 4: Testar Webhook

### 4.1. Teste Manual

1. No GitHub, vá em **Settings** → **Webhooks**
2. Clique no webhook criado
3. Role até **Recent Deliveries**
4. Clique em **Redeliver** → **Redeliver**

### 4.2. Verificar no Jenkins

1. Acesse Jenkins → **SDC/build-and-deploy**
2. Verifique se um novo build foi disparado
3. Veja os logs do build

### 4.3. Teste com Push Real

```bash
# Fazer um pequeno commit
echo "# Test webhook" >> README.md
git add README.md
git commit -m "test: Trigger Jenkins webhook"
git push origin main
```

**Resultado esperado**:

- ✅ Build inicia automaticamente no Jenkins
- ✅ Logs mostram: `Started by GitHub push`

---

## 🐛 Troubleshooting

### ❌ Webhook não dispara build

**Problema**: Push no GitHub não inicia build no Jenkins

**Soluções**:

1. **Verificar URL do webhook**:

   ```bash
   # Testar se Jenkins está acessível
   curl http://seu-jenkins:8080/github-webhook/
   ```

2. **Verificar logs do Jenkins**:

   - **Manage Jenkins** → **System Log**
   - Procure por erros relacionados a webhook

3. **Verificar configuração do job**:

   - Job deve ter **GitHub hook trigger** habilitado
   - Branch deve estar configurado corretamente

4. **Verificar permissões do GitHub**:
   - Token deve ter permissão `repo` (para repositórios privados)
   - Webhook deve estar ativo (verificar em GitHub → Settings → Webhooks)

### ❌ Erro 403 Forbidden

**Problema**: GitHub retorna 403 ao tentar entregar webhook

**Soluções**:

1. **Verificar Secret**:

   - Secret no GitHub deve corresponder ao do Jenkins
   - Ou remover secret se não configurado

2. **Verificar IP Whitelist**:
   - Se GitHub tem IP whitelist, adicionar IP do Jenkins

### ❌ Jenkins não recebe webhook

**Problema**: GitHub mostra "Failed to deliver" no webhook

**Soluções**:

1. **Verificar conectividade**:

   ```bash
   # Do servidor do Jenkins, testar acesso ao GitHub
   curl https://api.github.com
   ```

2. **Verificar firewall**:

   - Porta 8080 deve estar aberta
   - Ou usar túnel (ngrok)

3. **Verificar CSRF Protection**:
   - **Manage Jenkins** → **Configure Global Security**
   - Em **CSRF Protection**, verificar configurações

---

## 🔄 Usando Túnel (ngrok) para Jenkins Local

Se o Jenkins está rodando localmente, use ngrok para expor:

### 1. Instalar ngrok

```bash
# Download ngrok
wget https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-linux-amd64.tgz
tar xvzf ngrok-v3-stable-linux-amd64.tgz
sudo mv ngrok /usr/local/bin
```

### 2. Criar túnel

```bash
# Expor porta 8080
ngrok http 8080
```

### 3. Usar URL do ngrok no Webhook

```
https://seu-id.ngrok.io/github-webhook/
```

**⚠️ Nota**: URL do ngrok muda a cada reinicialização (versão gratuita)

---

## 📊 Monitoramento

### Verificar Status do Webhook

1. **GitHub** → **Settings** → **Webhooks**
2. Veja **Recent Deliveries**:
   - ✅ Verde: Entregue com sucesso
   - ❌ Vermelho: Falha na entrega
   - Clique para ver detalhes do erro

### Logs do Jenkins

```bash
# Ver logs do Jenkins
docker-compose -f docker-compose.jenkins.yml logs jenkins --tail=100 | grep -i webhook
```

---

## 📝 Exemplo de Payload do Webhook

GitHub envia JSON no seguinte formato:

```json
{
  "ref": "refs/heads/main",
  "repository": {
    "name": "New_SDC",
    "full_name": "usuario/New_SDC"
  },
  "pusher": {
    "name": "usuario",
    "email": "usuario@example.com"
  },
  "head_commit": {
    "id": "abc123...",
    "message": "feat: Adiciona nova feature",
    "timestamp": "2025-01-21T10:30:00Z"
  }
}
```

---

## 🔗 Referências

- [GitHub Webhooks Documentation](https://docs.github.com/en/developers/webhooks-and-events/webhooks)
- [Jenkins GitHub Plugin](https://plugins.jenkins.io/github/)
- [Jenkins Webhook Configuration](https://www.jenkins.io/doc/book/using/using-webhooks/)

---

<div align="center">

**🔗 Webhook GitHub → Jenkins - Configuração Completa**

_Última atualização: 2025-01-21_

</div>



