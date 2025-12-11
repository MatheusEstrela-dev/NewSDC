# 🔗 Configuração Final do Webhook GitHub → Jenkins Azure

## ✅ Status Atual

### ✅ Concluído
- [x] Jenkins online em: https://jenkinssdc.azurewebsites.net/
- [x] Service Principal criado no Azure
- [x] Credenciais Azure configuradas
- [x] Azure CLI instalado
- [x] Imagens no ACR (apidover.azurecr.io)

### 🎯 Próximos Passos
- [ ] Configurar webhook no GitHub
- [ ] Verificar/criar job no Jenkins
- [ ] Testar pipeline completo

---

## 🔧 Passo 1: Acessar Jenkins

### URL do Jenkins
```
https://jenkinssdc.azurewebsites.net/
```

### Primeiro Acesso
Se for o primeiro acesso, você pode precisar:
1. Obter a senha inicial do administrador
2. Ou usar as credenciais configuradas

**Para obter a senha inicial (se necessário):**
```bash
# Se o Jenkins está em um App Service
az webapp log download --name jenkinssdc --resource-group DOVER

# Ou via Azure Portal:
# jenkinssdc → Development Tools → SSH → Console
# cat /var/jenkins_home/secrets/initialAdminPassword
```

---

## 🔗 Passo 2: Configurar Webhook no GitHub

### URL do Webhook Jenkins
```
https://jenkinssdc.azurewebsites.net/github-webhook/
```

### Configuração no GitHub

1. **Acesse seu repositório:**
   ```
   https://github.com/SEU_USUARIO/New_SDC/settings/hooks
   ```

2. **Clique em "Add webhook"**

3. **Configure:**
   - **Payload URL**: `https://jenkinssdc.azurewebsites.net/github-webhook/`
   - **Content type**: `application/json`
   - **Secret**: (deixe vazio por enquanto, ou configure um token)
   - **Which events would you like to trigger this webhook?**
     - ✅ Selecione: **Just the push event**
   - **Active**: ✅ Marcado

4. **Clique em "Add webhook"**

5. **Verificar se funcionou:**
   - Após salvar, o GitHub faz um ping de teste
   - Você verá um ✅ verde se a entrega foi bem-sucedida
   - Ou um ❌ vermelho com detalhes do erro

---

## 🔧 Passo 3: Verificar Job no Jenkins

### Opção A: Acessar via Interface Web

1. **Acesse:** https://jenkinssdc.azurewebsites.net/
2. **Login** (use as credenciais configuradas)
3. **Verificar se existe o job:**
   - Procure por `SDC/build-and-deploy` ou similar
   - Se não existir, crie um novo

### Opção B: Criar Job Manualmente (se não existir)

1. **New Item**
2. **Nome**: `sdc-dev-app-cicd` (ou outro nome)
3. **Tipo**: **Pipeline**
4. **Configure:**

#### Build Triggers:
- ✅ **GitHub hook trigger for GITScm polling**

#### Pipeline:
- **Definition**: Pipeline script from SCM
- **SCM**: Git
- **Repository URL**: `https://github.com/SEU_USUARIO/New_SDC.git`
  - Ou use SSH: `git@github.com:SEU_USUARIO/New_SDC.git`
  - Se privado, adicione credenciais do GitHub
- **Branches to build**: `*/main` (ou `*/master`)
- **Script Path**: `SDC/Jenkinsfile`

5. **Salvar**

---

## 📝 Passo 4: Verificar Jenkinsfile

O Jenkinsfile deve estar em: `SDC/Jenkinsfile`

**Verificar se contém:**
- Stage de Build
- Stage de Push para ACR
- Credenciais do Azure configuradas

**Exemplo do Jenkinsfile:**
```groovy
pipeline {
    agent any

    environment {
        ACR_NAME = 'apidover'
        ACR_LOGIN_SERVER = 'apidover.azurecr.io'
        ACR_IMAGE = 'apidover.azurecr.io/sdc-dev-app'
        ACR_TAG = "${BUILD_NUMBER}-${GIT_COMMIT.take(7)}"
    }

    stages {
        stage('Build') {
            steps {
                script {
                    sh 'docker build -t sdc-dev-app:latest -f SDC/docker/Dockerfile.dev SDC'
                }
            }
        }

        stage('Tag and Push to ACR') {
            steps {
                script {
                    withCredentials([usernamePassword(
                        credentialsId: 'azure-service-principal',
                        usernameVariable: 'AZURE_CLIENT_ID',
                        passwordVariable: 'AZURE_CLIENT_SECRET'
                    )]) {
                        sh '''
                            az login --service-principal \
                                --username $AZURE_CLIENT_ID \
                                --password $AZURE_CLIENT_SECRET \
                                --tenant ${AZURE_TENANT_ID}

                            az acr login --name ${ACR_NAME}

                            docker tag sdc-dev-app:latest ${ACR_IMAGE}:${ACR_TAG}
                            docker tag sdc-dev-app:latest ${ACR_IMAGE}:latest

                            docker push ${ACR_IMAGE}:${ACR_TAG}
                            docker push ${ACR_IMAGE}:latest
                        '''
                    }
                }
            }
        }
    }
}
```

---

## 🧪 Passo 5: Testar o Pipeline

### Teste Manual (via Jenkins UI)

1. Acesse o job no Jenkins
2. Clique em **"Build Now"**
3. Veja o Console Output
4. Verifique se todas as stages executaram com sucesso

### Teste Automático (via Webhook)

```bash
# Fazer um commit de teste
cd c:\Users\kdes\Documentos\GitHub\New_SDC
echo "# Test CI/CD webhook" >> README.md
git add README.md
git commit -m "test: Trigger Jenkins CI/CD via webhook"
git push origin main
```

**Resultado esperado:**
1. GitHub envia webhook para Jenkins
2. Jenkins recebe o webhook
3. Build inicia automaticamente
4. Logs mostram: "Started by GitHub push"
5. Docker build é executado
6. Imagem é enviada para ACR

---

## 🔍 Passo 6: Verificar Resultado

### No GitHub
1. **Settings** → **Webhooks** → Seu webhook
2. Clique no webhook
3. **Recent Deliveries**
4. Verifique se há entrega com status **200** (✅ verde)
5. Clique na entrega para ver detalhes

### No Jenkins
1. Acesse: https://jenkinssdc.azurewebsites.net/
2. Vá para o job
3. Verifique se um novo build foi criado
4. Clique no build (#1, #2, etc.)
5. **Console Output** para ver logs detalhados

### No Azure ACR
```bash
# Verificar se a imagem foi enviada
az acr repository show-tags --name apidover --repository sdc-dev-app --output table
```

**Deve mostrar:**
```
Result
--------
latest
1-abc1234
```

---

## 🐛 Troubleshooting

### Webhook retorna 403 Forbidden

**Problema:** Jenkins está bloqueando requisições do GitHub

**Solução 1: Verificar CSRF Protection**
1. Jenkins → **Manage Jenkins** → **Configure Global Security**
2. Em **CSRF Protection**, verifique se não está muito restritivo
3. Ou adicione GitHub IPs à whitelist

**Solução 2: Verificar GitHub IP ranges**
```bash
# GitHub webhook IPs:
# https://api.github.com/meta
curl https://api.github.com/meta | jq .hooks
```

### Webhook não dispara build

**Problema:** Job não está configurado para receber webhooks

**Solução:**
1. Job → **Configure**
2. **Build Triggers** → ✅ **GitHub hook trigger for GITScm polling**
3. **Salvar**

### Build falha no push para ACR

**Problema:** Credenciais Azure não estão configuradas

**Solução:**
1. Jenkins → **Manage Jenkins** → **Manage Credentials**
2. Verificar se existe credencial: `azure-service-principal`
3. Se não existe, adicionar:
   - **ID**: `azure-service-principal`
   - **Username**: `74596f5b-5c73-4256-9719-b52e7f978985`
   - **Password**: (senha do Service Principal)

### Jenkins não está acessível

**Problema:** App Service parado ou com erro

**Solução:**
```bash
# Verificar status
az webapp show --name jenkinssdc --resource-group DOVER --query state -o tsv

# Reiniciar
az webapp restart --name jenkinssdc --resource-group DOVER

# Ver logs
az webapp log tail --name jenkinssdc --resource-group DOVER
```

---

## 📊 Resumo dos Endpoints

| Serviço | URL |
|---------|-----|
| **Jenkins Web** | https://jenkinssdc.azurewebsites.net/ |
| **Jenkins Webhook** | https://jenkinssdc.azurewebsites.net/github-webhook/ |
| **ACR** | apidover.azurecr.io |
| **GitHub Repo** | https://github.com/SEU_USUARIO/New_SDC |

---

## 🎯 Checklist Final

- [ ] Jenkins acessível em https://jenkinssdc.azurewebsites.net/
- [ ] Login no Jenkins funcionando
- [ ] Job criado/configurado no Jenkins
- [ ] Webhook configurado no GitHub
- [ ] Teste manual do build funcionando
- [ ] Webhook dispara build automaticamente
- [ ] Imagem é enviada para ACR com sucesso

---

## 📚 Próximos Passos Após CI/CD

1. **Configurar Deploy Automático**
   - Adicionar stage de deploy no Jenkinsfile
   - Deploy para Azure App Service ou ACI

2. **Configurar Notificações**
   - Email notifications
   - Slack/Discord webhooks

3. **Adicionar Testes Automatizados**
   - Unit tests
   - Integration tests
   - E2E tests

---

<div align="center">

**🚀 Jenkins Azure CI/CD - Setup Final**

*Data: 2025-12-08*

</div>
