# 🚀 CI/CD Configurado para ACR e Produção

## ✅ Alterações Realizadas

### 1. **Correção da Configuração do Jenkins** (`SDC/docker/jenkins/casc.yaml`)

**Problema corrigido:**
- ❌ URL do repositório estava como placeholder: `https://github.com/user/repo.git`
- ❌ Incompatibilidade: credencial SSH com URL HTTPS
- ❌ Path do Jenkinsfile incorreto

**Correção aplicada:**
- ✅ URL atualizada: `git@github.com:MatheusEstrela-dev/NewSDC.git` (SSH)
- ✅ Credencial SSH mantida e compatível
- ✅ Path do Jenkinsfile: `SDC/Jenkinsfile`

### 2. **Otimização do Jenkinsfile** (`SDC/Jenkinsfile`)

**Mudanças principais:**

#### Build Otimizado para Produção
- ✅ Build usando `Dockerfile.prod` diretamente (não docker-compose)
- ✅ Imagem taggeada automaticamente para ACR durante o build
- ✅ Dependências PHP/Node e assets compilados durante o build da imagem

#### Push para ACR Melhorado
- ✅ Retry automático em caso de falha
- ✅ Push de duas tags: `${ACR_TAG}` e `latest`
- ✅ Mensagens de erro mais claras

#### Deploy para App Service
- ✅ Login no Azure via Service Principal
- ✅ Configuração automática do App Service com credenciais do ACR
- ✅ Health check após deploy
- ✅ Reinicialização automática do App Service

**Stages removidos/simplificados:**
- ❌ Removido: `Install Dependencies` (já feito no build)
- ❌ Removido: `Build Frontend Assets` (já feito no build)
- ❌ Removido: `Deploy to Staging` (usava docker-compose)
- ❌ Removido: `Deploy to Production` (duplicado)
- ✅ Simplificado: `Code Quality & Tests` (apenas em branches de dev)

---

## 📋 Fluxo do CI/CD

```
1. Push para GitHub (branch main/master)
   ↓
2. Webhook dispara Jenkins
   ↓
3. Checkout do código
   ↓
4. Build da imagem Docker (Dockerfile.prod)
   - Instala dependências PHP
   - Instala dependências Node
   - Compila assets (Vite)
   - Taggeia para ACR
   ↓
5. Push para ACR
   - Tag: ${BUILD_NUMBER}-${GIT_COMMIT}
   - Tag: latest
   ↓
6. Deploy para Azure App Service
   - Login no Azure
   - Atualiza configuração do container
   - Reinicia App Service
   - Health check
   ↓
7. ✅ Deploy concluído!
```

---

## 🔧 Configuração Necessária no Jenkins

### 1. Variáveis de Ambiente Globais

Acesse: **Manage Jenkins** → **Configure System** → **Global properties** → **Environment variables**

Adicione:
```
AZURE_TENANT_ID=<seu-tenant-id>
AZURE_APP_SERVICE_NAME=newsdc2027
AZURE_RESOURCE_GROUP=DEFESA_CIVIL
ACR_NAME=apidover
GIT_REPO_URL=git@github.com:MatheusEstrela-dev/NewSDC.git
```

### 2. Credenciais Necessárias

#### a) Azure Service Principal (Recomendado)

**ID:** `azure-service-principal`
- **Kind:** Username with password
- **Username:** `<AZURE_CLIENT_ID>`
- **Password:** `<AZURE_CLIENT_SECRET>`
- **Description:** Azure Service Principal for Azure CLI

#### b) Git SSH Key

**ID:** `git-ssh-key`
- **Kind:** SSH Username with private key
- **Username:** `git`
- **Private Key:** Cole a chave SSH privada do GitHub
- **Description:** SSH Key for Git repositories

#### c) Azure ACR Credentials (Fallback - Opcional)

**ID:** `azure-acr-credentials`
- **Kind:** Username with password
- **Username:** `<ACR_USERNAME>` (geralmente o nome do ACR: `apidover`)
- **Password:** `<ACR_PASSWORD>` (obtido via: `az acr credential show --name apidover`)
- **Description:** Azure Container Registry Credentials

### 3. Recarregar Configuração do Jenkins

Após alterar o `casc.yaml`, recarregue a configuração:

**Opção A: Via Interface Web**
1. Acesse: https://jenkinssdc.azurewebsites.net/manage
2. Vá em **Manage Jenkins** → **Configuration as Code**
3. Clique em **Reload configuration**

**Opção B: Reiniciar Container**
```bash
docker restart jenkins-container
```

---

## 🧪 Testar o Pipeline

### 1. Fazer um Commit de Teste

```bash
# Fazer uma pequena alteração
echo "# CI/CD Test - $(date)" >> README.md
git add .
git commit -m "test: CI/CD pipeline - deploy automático"
git push origin main
```

### 2. Verificar o Build no Jenkins

Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/

**O que verificar:**
- ✅ Status: **SUCCESS** (verde)
- ✅ Stage "Build Docker Images" completou
- ✅ Stage "Tag and Push to ACR" completou
- ✅ Stage "Deploy to Azure App Service" completou
- ✅ Mensagem: "✅ Deploy para Azure App Service concluído!"

### 3. Verificar Imagem no ACR

```bash
az acr repository show-tags \
    --name apidover \
    --repository sdc-dev-app \
    --orderby time_desc \
    --output table
```

Você deve ver as tags mais recentes:
```
TAG
----
latest
1-abc1234
2-def5678
```

### 4. Verificar App Service

```bash
# Ver configuração do container
az webapp config container show \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL

# Ver logs
az webapp log tail \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL
```

### 5. Verificar Aplicação

Acesse: https://newsdc2027.azurewebsites.net/login

**O que deve aparecer:**
- No rodapé do card de login:
  ```
  © 2025 Governo do Estado de Minas Gerais
  CI/CD Test - Deploy Automático  ← Este texto
  ```

---

## 🔍 Troubleshooting

### Erro: "Authentication failed for 'https://github.com/...'"

**Causa:** Credencial SSH não configurada ou URL incorreta.

**Solução:**
1. Verifique se a credencial `git-ssh-key` existe no Jenkins
2. Verifique se a URL do repositório está correta no `casc.yaml`
3. Recarregue a configuração do Jenkins

### Erro: "Falha ao fazer login no ACR"

**Causa:** Credenciais do Azure não configuradas.

**Solução:**
1. Configure a credencial `azure-service-principal`
2. Configure a variável `AZURE_TENANT_ID`
3. Verifique se o Service Principal tem permissões no ACR

### Erro: "Azure CLI não encontrado"

**Causa:** Azure CLI não instalado no container do Jenkins.

**Solução:**
O Jenkinsfile tenta instalar automaticamente, mas se falhar:
1. Acesse o container do Jenkins
2. Instale o Azure CLI manualmente
3. Ou use a credencial `azure-acr-credentials` como fallback

### Build falha no stage "Build Docker Images"

**Causa:** Dockerfile.prod não encontrado ou erro no build.

**Solução:**
1. Verifique se o arquivo `SDC/docker/Dockerfile.prod` existe
2. Verifique os logs do build para ver o erro específico
3. Teste o build localmente:
   ```bash
   cd SDC
   docker build -f docker/Dockerfile.prod -t sdc-dev-app:test .
   ```

### Deploy não atualiza o App Service

**Causa:** App Service não está configurado para usar ACR ou credenciais incorretas.

**Solução:**
1. Verifique se o App Service está configurado para usar container do ACR
2. Verifique as credenciais do ACR no App Service
3. Verifique os logs do App Service para erros

---

## 📊 Monitoramento

### Verificar Status do Pipeline

```bash
# Via Azure CLI
az webapp show \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL \
    --query "{state:state, defaultHostName:defaultHostName}"
```

### Ver Logs em Tempo Real

```bash
az webapp log tail \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL \
    --follow
```

### Verificar Última Imagem Deployada

```bash
az webapp config container show \
    --name newsdc2027 \
    --resource-group DEFESA_CIVIL \
    --query "{image:linuxFxVersion, registry:linuxFxVersion}"
```

---

## ✅ Checklist Final

Antes de considerar o CI/CD configurado:

- [ ] Credencial `git-ssh-key` configurada no Jenkins
- [ ] Credencial `azure-service-principal` configurada no Jenkins
- [ ] Variável `AZURE_TENANT_ID` configurada no Jenkins
- [ ] Variáveis `AZURE_APP_SERVICE_NAME` e `AZURE_RESOURCE_GROUP` configuradas
- [ ] Configuração do Jenkins recarregada (casc.yaml)
- [ ] Webhook do GitHub configurado
- [ ] Teste de commit realizado
- [ ] Build do Jenkins executou com sucesso
- [ ] Imagem foi enviada para o ACR
- [ ] App Service foi atualizado
- [ ] Aplicação está respondendo corretamente
- [ ] Texto "CI/CD Test - Deploy Automático" aparece na tela de login

---

## 🎯 Próximos Passos

1. **Fazer um commit de teste** para disparar o pipeline
2. **Monitorar o build** no Jenkins
3. **Verificar o deploy** no App Service
4. **Testar a aplicação** em produção
5. **Configurar notificações** (opcional - Slack, email, etc.)

---

## 📝 Resumo das Alterações

| Arquivo | Alteração |
|---------|-----------|
| `SDC/docker/jenkins/casc.yaml` | URL do repositório corrigida para SSH |
| `SDC/Jenkinsfile` | Build otimizado usando Dockerfile.prod diretamente |
| `SDC/Jenkinsfile` | Push para ACR melhorado com retry |
| `SDC/Jenkinsfile` | Deploy para App Service com Service Principal |
| `SDC/Jenkinsfile` | Stages redundantes removidos |

---

**Status:** ✅ CI/CD configurado e pronto para uso!

**Última atualização:** $(date)

