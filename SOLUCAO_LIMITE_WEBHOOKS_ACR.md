# ⚠️ Solução: Limite de Webhooks ACR Excedido

## 🔴 Problema
```
Quota exceeded for resource type webhooks for the registry SKU Basic.
```

**Causa:** Azure Container Registry SKU Basic permite apenas **2 webhooks** e você já tem 2 em uso:
- `apidover84d242` - habilitado
- `doversite132809` - habilitado

---

## ✅ Soluções Disponíveis

### Opção 1: Usar Webhook Existente (Recomendado - Sem Custo)

Ao invés de criar um novo webhook no ACR, configure o **GitHub webhook** para disparar diretamente o Jenkins, que então fará o push para o ACR.

#### Fluxo:
```
GitHub Push → GitHub Webhook → Jenkins → Build → Push para ACR
```

**Vantagem:** Não precisa de webhook no ACR!

#### Implementação:
1. **Configurar webhook no GitHub** (já preparado):
   - URL: `https://jenkinssdc.azurewebsites.net/github-webhook/`
   - Evento: Push

2. **Jenkins faz o push para ACR** (via Jenkinsfile):
   ```groovy
   stage('Push to ACR') {
       steps {
           sh 'az acr login --name apidover'
           sh 'docker push apidover.azurecr.io/sdc-dev-app:latest'
       }
   }
   ```

✅ **Esta é a solução que já implementamos!**

---

### Opção 2: Remover Webhook Não Usado

Se um dos webhooks existentes não é crítico, você pode removê-lo.

#### Verificar detalhes dos webhooks:
```bash
# Ver detalhes de cada webhook
az acr webhook show --registry apidover --name apidover84d242 --query "{Name:name, ServiceUri:serviceUri, Status:status, Actions:actions}" -o table

az acr webhook show --registry apidover --name doversite132809 --query "{Name:name, ServiceUri:serviceUri, Status:status, Actions:actions}" -o table
```

#### Remover webhook (CUIDADO):
```bash
# Apenas se tiver certeza que não está sendo usado!
az acr webhook delete --registry apidover --name NOME_DO_WEBHOOK --yes
```

⚠️ **Atenção:** Verifique se o webhook não está sendo usado pelo Dover antes de remover!

---

### Opção 3: Upgrade do ACR para Standard (Custo Adicional)

Aumentar o SKU do ACR para **Standard** permite até **10 webhooks**.

#### Custos (aproximados):
- **Basic**: ~$5/mês - 2 webhooks
- **Standard**: ~$20/mês - 10 webhooks
- **Premium**: ~$50/mês - 500 webhooks

#### Comando para upgrade:
```bash
az acr update --name apidover --sku Standard
```

#### Recursos por SKU:

| Feature | Basic | Standard | Premium |
|---------|-------|----------|---------|
| **Webhooks** | 2 | 10 | 500 |
| **Armazenamento** | 10 GB | 100 GB | 500 GB |
| **ReadOps/dia** | 1,000 | 3,000 | 10,000 |
| **WriteOps/dia** | 100 | 500 | 2,000 |
| **Throughput** | 300 Mbps | 600 Mbps | Ilimitado |
| **Preço/mês** | ~$5 | ~$20 | ~$50+ |

---

### Opção 4: Usar GitHub Actions (Alternativa ao Jenkins)

Ao invés de Jenkins + ACR webhook, use GitHub Actions para CI/CD.

#### Vantagens:
- Sem limite de webhooks
- Integrado ao GitHub
- Gratuito para repositórios públicos
- 2000 min/mês grátis para privados

#### Exemplo workflow (`.github/workflows/ci-cd.yml`):
```yaml
name: CI/CD

on:
  push:
    branches: [ main ]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Login to ACR
        uses: azure/docker-login@v1
        with:
          login-server: apidover.azurecr.io
          username: ${{ secrets.AZURE_CLIENT_ID }}
          password: ${{ secrets.AZURE_CLIENT_SECRET }}

      - name: Build and push
        run: |
          docker build -t apidover.azurecr.io/sdc-dev-app:${{ github.sha }} .
          docker push apidover.azurecr.io/sdc-dev-app:${{ github.sha }}
```

---

## 🎯 Recomendação

### ✅ **Opção 1 (Recomendada):** GitHub Webhook → Jenkins → ACR

Esta é a solução **sem custo adicional** e já está implementada!

**Fluxo completo:**
1. Você faz push no GitHub
2. GitHub webhook dispara Jenkins
3. Jenkins:
   - Faz checkout do código
   - Build da imagem Docker
   - Login no ACR (via Service Principal)
   - Push da imagem para ACR
4. Imagem disponível no ACR

**Não precisa de webhook no ACR!**

---

## 📝 Configuração Final (Sem Webhook ACR)

### 1. Verificar Jenkinsfile

Certifique-se que o [Jenkinsfile](SDC/Jenkinsfile) tem o stage de push:

```groovy
stage('Tag and Push to ACR') {
    steps {
        withCredentials([usernamePassword(
            credentialsId: 'azure-service-principal',
            usernameVariable: 'AZURE_CLIENT_ID',
            passwordVariable: 'AZURE_CLIENT_SECRET'
        )]) {
            sh '''
                az login --service-principal \
                    --username $AZURE_CLIENT_ID \
                    --password $AZURE_CLIENT_SECRET \
                    --tenant $AZURE_TENANT_ID

                az acr login --name apidover

                docker tag sdc-dev-app:latest apidover.azurecr.io/sdc-dev-app:${BUILD_NUMBER}
                docker push apidover.azurecr.io/sdc-dev-app:${BUILD_NUMBER}
                docker push apidover.azurecr.io/sdc-dev-app:latest
            '''
        }
    }
}
```

### 2. Configurar GitHub Webhook

```
URL: https://jenkinssdc.azurewebsites.net/github-webhook/
Content type: application/json
Events: Push
```

### 3. Testar

```bash
git commit -m "test: CI/CD via GitHub webhook" --allow-empty
git push origin main
```

**Resultado:**
- ✅ GitHub → Jenkins → Build → Push para ACR
- ✅ Sem necessidade de webhook no ACR!

---

## 🔍 Verificar Webhooks Atuais

```bash
# Ver o que cada webhook faz
az acr webhook show --registry apidover --name apidover84d242
az acr webhook show --registry apidover --name doversite132809

# Ver pings recentes
az acr webhook list-events --registry apidover --name apidover84d242
```

---

## 💡 Decisão

**Você precisa escolher:**

1. **Continuar com GitHub → Jenkins → ACR** (sem custo, sem webhook ACR)
2. **Remover um webhook existente** (se não estiver usando)
3. **Upgrade para Standard** (~$20/mês)

**Recomendo a opção 1**, que já está configurada e não precisa de webhook no ACR!

---

<div align="center">

**⚠️ Solução: Limite de Webhooks ACR**

*Data: 2025-12-08*

</div>
