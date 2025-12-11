# 🚀 Guia de Configuração do Webhook GitHub → Jenkins

## ✅ Status da Configuração

### Concluído
- [x] Service Principal criado no Azure
- [x] Credenciais configuradas no Jenkins (.env.jenkins)
- [x] Azure CLI instalado no Jenkins
- [x] Jenkins rodando com credenciais configuradas

### Pendente
- [ ] Configurar webhook no GitHub
- [ ] Testar pipeline completo

---

## 📋 Credenciais Criadas

### Service Principal Azure
- **App ID**: `74596f5b-5c73-4256-9719-b52e7f978985`
- **Tenant ID**: `14cbd5a7-ec94-46ba-b314-cc0fc972a161`
- **Role**: `acrpush` no ACR `apidover`
- **Password**: Armazenado em `.env.jenkins` (NÃO commitado no Git)

---

## 🔧 Próximos Passos

### 1. Acessar Jenkins
```
URL: http://localhost:8080
Usuário: admin
Senha: admin123
```

### 2. Verificar Credenciais Azure
1. Acesse Jenkins → **Manage Jenkins** → **Manage Credentials**
2. Verifique se as credenciais foram carregadas:
   - `azure-service-principal`
   - `azure-acr-credentials`

### 3. Configurar Webhook no GitHub

#### Opção A: Jenkins Público (Recomendado para Produção)
Se o Jenkins tem IP público:
```
Payload URL: http://seu-ip-publico:8080/github-webhook/
```

#### Opção B: Usar ngrok (Para Desenvolvimento Local)
```bash
# Instalar ngrok (Windows)
choco install ngrok

# Criar túnel
ngrok http 8080

# Use a URL fornecida (ex: https://abc123.ngrok.io/github-webhook/)
```

#### Configuração no GitHub:
1. Acesse: https://github.com/SEU_USUARIO/New_SDC/settings/hooks
2. Clique em **Add webhook**
3. Configure:
   - **Payload URL**: `http://seu-jenkins:8080/github-webhook/`
   - **Content type**: `application/json`
   - **Events**: Selecione "Just the push event"
   - **Active**: ✅ Marcado
4. Clique em **Add webhook**

### 4. Habilitar GitHub Hook no Job Jenkins

1. Acesse Jenkins → **SDC/build-and-deploy**
2. Clique em **Configure**
3. Em **Build Triggers**, marque:
   - ✅ **GitHub hook trigger for GITScm polling**
4. Salve

### 5. Testar o Pipeline

```bash
# Fazer um commit de teste
echo "# Test CI/CD" >> README.md
git add README.md
git commit -m "test: Trigger Jenkins CI/CD pipeline"
git push origin main
```

**Resultado esperado**:
- ✅ Build inicia automaticamente no Jenkins
- ✅ Logs mostram: "Started by GitHub push"
- ✅ Imagem é buildada
- ✅ Imagem é enviada para ACR (apidover.azurecr.io)

---

## 🔍 Verificação do Pipeline

### Verificar se o build foi executado:
```bash
# Ver logs do Jenkins
docker logs sdc_jenkins_dev --tail=100

# Verificar se a imagem foi enviada ao ACR
az acr repository show-tags --name apidover --repository sdc-dev-app --output table
```

### Acessar Console do Build:
1. Jenkins → **SDC/build-and-deploy**
2. Clique no último build (#X)
3. Clique em **Console Output**
4. Verifique se todas as stages foram executadas com sucesso

---

## 🐛 Troubleshooting

### Jenkins não recebe webhook do GitHub
```bash
# 1. Verificar se Jenkins está acessível
curl http://localhost:8080/github-webhook/

# 2. Ver logs do GitHub webhook
# GitHub → Settings → Webhooks → Seu webhook → Recent Deliveries
```

### Erro de autenticação no Azure
```bash
# Testar login manualmente no container
docker exec -it sdc_jenkins_dev bash
az login --service-principal \
  --username $AZURE_CLIENT_ID \
  --password $AZURE_CLIENT_SECRET \
  --tenant $AZURE_TENANT_ID

az acr login --name apidover
```

### Pipeline falha no push para ACR
```bash
# Verificar credenciais no Jenkins
docker exec -it sdc_jenkins_dev bash
echo $AZURE_CLIENT_ID
echo $AZURE_TENANT_ID
env | grep AZURE
```

---

## 📚 Documentação Relacionada

- [SETUP_CI_CD_RESUMO.md](Doc/SETUP_CI_CD_RESUMO.md) - Resumo completo da configuração
- [GITHUB_WEBHOOK_JENKINS.md](Doc/GITHUB_WEBHOOK_JENKINS.md) - Guia detalhado de webhook
- [JENKINS_ACR_SETUP.md](Doc/JENKINS_ACR_SETUP.md) - Configuração Jenkins + ACR
- [JENKINS_ACR_DEPLOY.md](Doc/JENKINS_ACR_DEPLOY.md) - Deploy no Azure

---

## ⚠️ Segurança

### Arquivos NÃO commitados no Git:
- ✅ `.env.jenkins` - Contém credenciais sensíveis
- ✅ Service Principal password

### Arquivo commitado (template):
- ✅ `.env.jenkins.example` - Template sem credenciais

### Verificar .gitignore:
```bash
cat SDC/docker/.gitignore | grep .env.jenkins
```

---

## 🎯 Status Final

| Componente | Status | Próxima Ação |
|-----------|--------|--------------|
| **Azure Service Principal** | ✅ Criado | Nenhuma |
| **Credenciais Jenkins** | ✅ Configuradas | Verificar no Jenkins UI |
| **Jenkins Container** | ✅ Rodando | Nenhuma |
| **Azure CLI** | ✅ Instalado | Nenhuma |
| **Webhook GitHub** | ⚠️ Pendente | Configurar manualmente |
| **Pipeline Test** | ⚠️ Pendente | Executar após webhook |

---

<div align="center">

**🚀 CI/CD Configuration Guide**

*Data: 2025-12-08*

</div>
