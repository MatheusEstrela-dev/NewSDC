# 🚀 Deploy do Jenkins no Azure Container Registry (ACR)

Este guia completo explica como buildar, fazer push e deploy do container Jenkins no Azure ACR, garantindo comunicação entre todos os containers na rede `sdc-dev_sdc_network`.

---

## 📋 Pré-requisitos

### 1. Azure CLI instalado e autenticado
```bash
# Instalar Azure CLI
winget install -e --id Microsoft.AzureCLI  # Windows
# ou
curl -sL https://aka.ms/InstallAzureCLIDeb | sudo bash  # Linux

# Login no Azure
az login
```

### 2. Docker Desktop rodando

### 3. Acesso ao Azure Container Registry
- Nome do ACR: `apidover`
- URL: `apidover.azurecr.io`
- Permissões de push/pull

### 4. Rede SDC em execução
```bash
# Verificar se a rede existe
docker network ls | grep sdc

# Deve aparecer: sdc-dev_sdc_network
```

---

## 🏗️ Parte 1: Build e Push do Jenkins para ACR

### Método 1: Script Automatizado (Recomendado)

#### Windows (PowerShell)
```powershell
# Navegar até o diretório
cd C:\Users\kdes\Documentos\GitHub\New_SDC\SDC\docker

# Build e push em um comando
.\push-jenkins-to-acr.ps1 -AcrName "apidover" -Tag "latest" -BuildFirst

# Ou em etapas:
# 1. Apenas login
.\push-jenkins-to-acr.ps1 -AcrName "apidover" -LoginOnly

# 2. Build e push
.\push-jenkins-to-acr.ps1 -AcrName "apidover" -Tag "v1.0.0" -BuildFirst
```

#### Linux/Mac
```bash
# Dar permissão de execução
chmod +x push-jenkins-to-acr.sh

# Build e push
./push-jenkins-to-acr.sh -n "apidover" -t "latest" --build

# Ou apenas push (se já tiver buildado)
./push-jenkins-to-acr.sh -n "apidover" -t "latest"
```

### Método 2: Manual (Passo a Passo)

```bash
# 1. Login no ACR
az acr login --name apidover

# 2. Build da imagem
cd jenkins
docker build -f Dockerfile.acr -t sdc-jenkins:latest .

# 3. Tag para ACR
docker tag sdc-jenkins:latest apidover.azurecr.io/sdc-jenkins:latest
docker tag sdc-jenkins:latest apidover.azurecr.io/sdc-jenkins:v1.0.0

# 4. Push para ACR
docker push apidover.azurecr.io/sdc-jenkins:latest
docker push apidover.azurecr.io/sdc-jenkins:v1.0.0

# 5. Verificar
az acr repository show-tags --name apidover --repository sdc-jenkins --output table
```

---

## 🌐 Parte 2: Deploy Local (Desenvolvimento)

### Garantindo Comunicação entre Containers

O Jenkins deve estar na **mesma rede** que os containers do SDC para permitir comunicação.

#### 1. Verificar Rede SDC
```bash
# Listar redes
docker network ls

# Inspecionar rede SDC
docker network inspect sdc-dev_sdc_network

# Ver quais containers estão conectados
docker network inspect sdc-dev_sdc_network --format='{{range .Containers}}{{.Name}} {{end}}'
```

#### 2. Subir Jenkins na Rede SDC
```bash
cd C:\Users\kdes\Documentos\GitHub\New_SDC\SDC\docker

# Subir Jenkins conectado à rede do SDC
docker-compose -f docker-compose.jenkins-dev.yml up -d

# Verificar status
docker-compose -f docker-compose.jenkins-dev.yml ps

# Ver logs
docker-compose -f docker-compose.jenkins-dev.yml logs -f jenkins
```

#### 3. Testar Comunicação entre Containers

```bash
# Entrar no container do Jenkins
docker exec -it sdc_jenkins_dev bash

# Testar comunicação com containers do SDC
ping sdc-dev-app              # App Laravel
ping sdc-dev-mysql            # MySQL
ping sdc-dev-redis            # Redis
ping sdc-dev-nginx            # Nginx

# Testar acesso HTTP
curl http://sdc-dev-app:9000  # FastCGI
curl http://sdc-dev-nginx:80  # Nginx

# Sair do container
exit
```

#### 4. Configurar DNS Resolution no Jenkins

O Jenkins automaticamente resolve os nomes dos containers na mesma rede Docker:

| Container SDC | Hostname | Porta | Descrição |
|---------------|----------|-------|-----------|
| App Laravel | `sdc-dev-app` | 9000 | FastCGI |
| Nginx | `sdc-dev-nginx` | 80 | Web Server |
| MySQL | `sdc-dev-mysql` | 3306 | Banco de Dados |
| Redis | `sdc-dev-redis` | 6379 | Cache |

**Exemplo de uso no Jenkinsfile:**
```groovy
stage('Deploy') {
    steps {
        sh '''
            # Jenkins pode acessar diretamente os containers
            docker exec sdc-dev-app php artisan migrate
            curl http://sdc-dev-nginx/health
        '''
    }
}
```

---

## ☁️ Parte 3: Deploy no Azure (Produção)

### Opção A: Azure Container Instances (ACI)

```bash
# Criar container instance
az container create \
  --resource-group sdc-production \
  --name sdc-jenkins \
  --image apidover.azurecr.io/sdc-jenkins:latest \
  --registry-login-server apidover.azurecr.io \
  --registry-username apidover \
  --registry-password $(az acr credential show --name apidover --query "passwords[0].value" -o tsv) \
  --dns-name-label sdc-jenkins \
  --ports 8080 50000 \
  --cpu 2 \
  --memory 4 \
  --environment-variables \
    ACR_NAME=apidover \
    AZURE_CLIENT_ID=${AZURE_CLIENT_ID} \
    AZURE_CLIENT_SECRET=${AZURE_CLIENT_SECRET} \
  --volume-name jenkins-home \
  --azure-file-volume-account-name mystorageaccount \
  --azure-file-volume-account-key ${STORAGE_KEY} \
  --azure-file-volume-share-name jenkins-data

# Obter IP público
az container show --resource-group sdc-production --name sdc-jenkins --query ipAddress.fqdn -o tsv
```

### Opção B: Azure Kubernetes Service (AKS)

#### 1. Criar deployment manifest
```yaml
# jenkins-deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: jenkins
  namespace: sdc
spec:
  replicas: 1
  selector:
    matchLabels:
      app: jenkins
  template:
    metadata:
      labels:
        app: jenkins
    spec:
      containers:
      - name: jenkins
        image: apidover.azurecr.io/sdc-jenkins:latest
        ports:
        - containerPort: 8080
          name: web
        - containerPort: 50000
          name: agent
        env:
        - name: ACR_NAME
          value: "apidover"
        - name: JAVA_OPTS
          value: "-Xms512m -Xmx2g"
        volumeMounts:
        - name: jenkins-home
          mountPath: /var/jenkins_home
        resources:
          requests:
            memory: "2Gi"
            cpu: "1000m"
          limits:
            memory: "4Gi"
            cpu: "2000m"
      volumes:
      - name: jenkins-home
        persistentVolumeClaim:
          claimName: jenkins-pvc
---
apiVersion: v1
kind: Service
metadata:
  name: jenkins
  namespace: sdc
spec:
  type: LoadBalancer
  ports:
  - port: 80
    targetPort: 8080
    name: web
  - port: 50000
    targetPort: 50000
    name: agent
  selector:
    app: jenkins
```

#### 2. Deploy no AKS
```bash
# Configurar kubectl
az aks get-credentials --resource-group sdc-production --name sdc-cluster

# Deploy
kubectl apply -f jenkins-deployment.yaml

# Verificar status
kubectl get pods -n sdc
kubectl get svc -n sdc

# Ver logs
kubectl logs -f deployment/jenkins -n sdc

# Obter IP externo
kubectl get svc jenkins -n sdc -o jsonpath='{.status.loadBalancer.ingress[0].ip}'
```

---

## 🔐 Configurar Credenciais do Azure no Jenkins

### Via Interface Web (após primeiro login)

1. Acesse Jenkins: `http://localhost:8080` ou `http://jenkins.seudominio.com`

2. **Manage Jenkins → Credentials → System → Global credentials → Add Credentials**

3. **Credenciais do ACR:**
   - **Kind:** Username with password
   - **ID:** `azure-acr-credentials`
   - **Username:** `apidover`
   - **Password:** (obter via `az acr credential show --name apidover --query "passwords[0].value" -o tsv`)
   - **Description:** Azure Container Registry Credentials

4. **Service Principal (recomendado):**
   - **Kind:** Username with password
   - **ID:** `azure-service-principal`
   - **Username:** `${AZURE_CLIENT_ID}`
   - **Password:** `${AZURE_CLIENT_SECRET}`
   - **Description:** Azure Service Principal

### Via Variáveis de Ambiente

```bash
# Editar docker-compose.jenkins-dev.yml
# Adicionar nas environment variables:
environment:
  - AZURE_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
  - AZURE_CLIENT_SECRET=seu-secret-aqui
  - AZURE_TENANT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
  - AZURE_ACR_USERNAME=apidover
  - AZURE_ACR_PASSWORD=senha-do-acr

# Recriar container
docker-compose -f docker-compose.jenkins-dev.yml up -d --force-recreate
```

---

## 🔄 Integração com Pipeline CI/CD

### Atualizar Jenkinsfile para usar ACR

O seu [Jenkinsfile](../SDC/Jenkinsfile:113-210) já está configurado para push no ACR!

**Stage relevante:**
```groovy
stage('Tag and Push to ACR') {
    steps {
        script {
            // Login no ACR via credenciais configuradas
            withCredentials([usernamePassword(
                credentialsId: 'azure-acr-credentials',
                usernameVariable: 'ACR_USERNAME',
                passwordVariable: 'ACR_PASSWORD'
            )]) {
                sh """
                    echo \$ACR_PASSWORD | docker login apidover.azurecr.io \
                        --username \$ACR_USERNAME \
                        --password-stdin
                """
            }

            // Tag e push
            sh """
                docker tag sdc-dev-app:latest apidover.azurecr.io/sdc-dev-app:latest
                docker push apidover.azurecr.io/sdc-dev-app:latest
            """
        }
    }
}
```

---

## 🔍 Verificação e Troubleshooting

### Verificar Conectividade Jenkins ↔ SDC

```bash
# 1. Verificar se Jenkins está na rede correta
docker inspect sdc_jenkins_dev | grep -A 20 Networks

# 2. Listar todos containers na rede SDC
docker network inspect sdc-dev_sdc_network --format='{{range .Containers}}{{.Name}}: {{.IPv4Address}} {{println}}{{end}}'

# 3. Testar ping do Jenkins para SDC
docker exec sdc_jenkins_dev ping -c 3 sdc-dev-app

# 4. Testar acesso HTTP
docker exec sdc_jenkins_dev curl -I http://sdc-dev-nginx
```

### Problemas Comuns

#### ❌ Erro: "network sdc-dev_sdc_network not found"

**Solução:**
```bash
# Subir primeiro o ambiente SDC
cd SDC
docker-compose up -d

# Depois subir o Jenkins
cd docker
docker-compose -f docker-compose.jenkins-dev.yml up -d
```

#### ❌ Jenkins não consegue acessar outros containers

**Solução:**
```bash
# Verificar se estão na mesma rede
docker network inspect sdc-dev_sdc_network

# Reconectar Jenkins à rede
docker network connect sdc-dev_sdc_network sdc_jenkins_dev

# Ou recriar o container
docker-compose -f docker-compose.jenkins-dev.yml down
docker-compose -f docker-compose.jenkins-dev.yml up -d
```

#### ❌ Erro ao fazer push para ACR: "unauthorized"

**Solução:**
```bash
# Re-login no ACR
az acr login --name apidover

# Verificar credenciais
az acr credential show --name apidover

# Testar manualmente
echo "senha-acr" | docker login apidover.azurecr.io -u apidover --password-stdin
```

---

## 📊 Monitoramento

### Verificar Status dos Containers

```bash
# Status geral
docker-compose -f docker-compose.jenkins-dev.yml ps

# Logs em tempo real
docker-compose -f docker-compose.jenkins-dev.yml logs -f

# Health check
docker inspect sdc_jenkins_dev --format='{{.State.Health.Status}}'

# Uso de recursos
docker stats sdc_jenkins_dev
```

### Acessar Jenkins

- **Local:** http://localhost:8080
- **Senha inicial:**
  ```bash
  docker exec sdc_jenkins_dev cat /var/jenkins_home/secrets/initialAdminPassword
  ```

---

## 🚀 Comandos Rápidos

### Build e Deploy Local
```bash
cd C:\Users\kdes\Documentos\GitHub\New_SDC\SDC\docker

# Build e push para ACR
.\push-jenkins-to-acr.ps1 -AcrName "apidover" -Tag "latest" -BuildFirst

# Subir Jenkins localmente
docker-compose -f docker-compose.jenkins-dev.yml up -d

# Ver logs
docker-compose -f docker-compose.jenkins-dev.yml logs -f jenkins
```

### Atualizar Jenkins
```bash
# Pull nova versão do ACR
docker pull apidover.azurecr.io/sdc-jenkins:latest

# Atualizar tag local
docker tag apidover.azurecr.io/sdc-jenkins:latest sdc-jenkins:dev

# Recriar container
docker-compose -f docker-compose.jenkins-dev.yml up -d --force-recreate
```

### Limpar e Reconstruir
```bash
# Parar e remover
docker-compose -f docker-compose.jenkins-dev.yml down -v

# Rebuild completo
docker-compose -f docker-compose.jenkins-dev.yml build --no-cache

# Subir novamente
docker-compose -f docker-compose.jenkins-dev.yml up -d
```

---

## 📝 Checklist de Deploy

### Pré-Deploy
- [ ] Azure CLI instalado e autenticado
- [ ] Docker Desktop rodando
- [ ] Rede `sdc-dev_sdc_network` criada
- [ ] Acesso ao ACR `apidover` configurado

### Build
- [ ] Dockerfile.acr criado em `docker/jenkins/`
- [ ] Script de push testado
- [ ] Imagem buildada localmente: `sdc-jenkins:latest`

### Push para ACR
- [ ] Login no ACR realizado
- [ ] Imagem taggeada: `apidover.azurecr.io/sdc-jenkins:latest`
- [ ] Push concluído com sucesso
- [ ] Tags verificadas no ACR

### Deploy Local
- [ ] `docker-compose.jenkins-dev.yml` configurado
- [ ] Jenkins subiu na rede `sdc-dev_sdc_network`
- [ ] Health check passou
- [ ] Acesso web funcional (http://localhost:8080)
- [ ] Comunicação com containers SDC testada

### Configuração Jenkins
- [ ] Senha inicial obtida
- [ ] Usuário admin criado
- [ ] Plugins instalados
- [ ] Credenciais Azure configuradas
- [ ] Pipeline SDC importado

### Webhook GitHub
- [ ] Webhook configurado no GitHub
- [ ] URL: `http://seu-jenkins:8080/github-webhook/`
- [ ] Secret configurado (opcional)
- [ ] Teste de push realizado

---

## 🔗 Referências

- [Dockerfile Jenkins ACR](../SDC/docker/jenkins/Dockerfile.acr)
- [Docker Compose Jenkins Dev](../SDC/docker/docker-compose.jenkins-dev.yml)
- [Script Push Windows](../SDC/docker/push-jenkins-to-acr.ps1)
- [Script Push Linux](../SDC/docker/push-jenkins-to-acr.sh)
- [Jenkinsfile Principal](../SDC/Jenkinsfile)
- [Configuração Webhook GitHub](./GITHUB_WEBHOOK_JENKINS.md)
- [Arquitetura Docker SDC](./DOCKER_ARCHITECTURE.md)

---

<div align="center">

**🚀 Jenkins → Azure ACR → CI/CD Completo**

*Última atualização: 2025-12-08*

</div>
