# 🔄 Guia de Migração - Pipeline Jenkins Melhorado

## ⚡ Quick Start

### Opção 1: Teste Rápido (Recomendado)
```bash
# 1. Criar branch de teste
git checkout -b test/jenkins-improvements

# 2. Copiar pipeline melhorado
cp Jenkinsfile Jenkinsfile.backup
cp Jenkinsfile.improved Jenkinsfile

# 3. Commit e push
git add Jenkinsfile
git commit -m "test: Jenkins pipeline improvements"
git push origin test/jenkins-improvements

# 4. Observar build no Jenkins
# Acessar: https://your-jenkins/job/sdc/
```

### Opção 2: Teste Lado a Lado
```bash
# Manter ambos pipelines e comparar resultados
# Jenkinsfile (atual) e Jenkinsfile.improved (novo)
```

---

## 📋 Checklist Pré-Migração

### ✅ Jenkins Master
- [ ] Jenkins versão >= 2.300
- [ ] Docker instalado e acessível
- [ ] Plugins instalados:
  - [ ] Docker Pipeline Plugin
  - [ ] Pipeline: Stage View
  - [ ] Credentials Binding Plugin

### ✅ Azure Resources
- [ ] Service Principal configurado (`azure-service-principal`)
- [ ] `AZURE_TENANT_ID` definido nas variáveis globais
- [ ] ACR acessível (`apidover.azurecr.io`)
- [ ] App Service ativo (`newsdc2027`)

### ✅ Git Repository
- [ ] Shared library `vars/conflictDetection.groovy` commitada
- [ ] Branch de teste criada

---

## 🚀 Migração Passo a Passo

### Passo 1: Backup do Pipeline Atual
```bash
# No seu repositório local
cd c:\Users\kdes\Documentos\GitHub\New_SDC

# Backup
cp Jenkinsfile Jenkinsfile.v1.backup
cp SDC/Jenkinsfile SDC/Jenkinsfile.v1.backup

# Commit backup
git add *.backup
git commit -m "backup: Jenkins pipelines v1"
```

### Passo 2: Validar Shared Library
```bash
# Verificar se arquivo existe
cat vars/conflictDetection.groovy

# Se não existir, criar:
# (Arquivo já foi criado na implementação anterior)
```

### Passo 3: Aplicar Pipeline Melhorado

**Opção A: Substituir Completamente**
```bash
# Substituir root Jenkinsfile
cp Jenkinsfile.improved Jenkinsfile

# Substituir SDC/Jenkinsfile (ajustar paths)
cp Jenkinsfile.improved SDC/Jenkinsfile
```

**Opção B: Migração Gradual (Recomendado)**
```bash
# Aplicar apenas algumas melhorias por vez
# Exemplo: Começar com parallel stages
```

### Passo 4: Ajustes Específicos

#### Para SDC/Jenkinsfile
```groovy
// Alterar dir() calls
dir('SDC') { ... }  // Remover se já está em SDC/
```

#### Para Environment Variables
```groovy
// Verificar se estas variáveis estão configuradas:
environment {
    AZURE_TENANT_ID = credentials('azure-tenant-id')  // Se não está em global properties
}
```

### Passo 5: Primeiro Build de Teste
```bash
# Push para branch de teste
git add Jenkinsfile vars/
git commit -m "feat: improved Jenkins pipeline with Docker agents and parallelization"
git push origin test/jenkins-improvements

# Trigger manual no Jenkins ou aguardar webhook
```

---

## 🔍 Monitoramento Durante Migração

### Métricas Para Observar

#### Build Time
```
Antes: ~25 minutos
Meta:  ~15 minutos
```

#### Stage Breakdown
```
✅ Fast Validation:     10s
✅ Static Analysis:     1-2 min (parallel)
✅ Build and Push:      5-10 min
✅ Testing:             2-3 min (parallel)
✅ Deploy:              3-5 min
```

#### Success Rate
```
Alvo: >= 90% (mesmo com mais validações)
```

---

## 🐛 Troubleshooting Comum

### Erro: "Docker not found"
```bash
# No Jenkins master, verificar:
docker --version

# Se não instalado:
# Instalar Docker no servidor Jenkins
# Ou usar agent label diferente
```

**Solução:**
```groovy
agent {
    docker {
        image 'php:8.2-cli'
        label 'docker-enabled'  // Usar node específico
    }
}
```

### Erro: "cleanWs() not found"
```bash
# Instalar plugin: Workspace Cleanup Plugin
# Jenkins → Manage Plugins → Available → "Workspace Cleanup Plugin"
```

### Erro: "conflictDetection() is not defined"
```bash
# Verificar se vars/conflictDetection.groovy existe
# Garantir que está commitado no repositório
# Fazer reload do Jenkins:
# Manage Jenkins → Reload Configuration from Disk
```

### Erro: Azure login fails
```groovy
// Adicionar debug:
sh """
    echo "Client ID: \${AZURE_CLIENT_ID:0:5}..."  // Mostrar primeiros 5 chars
    echo "Tenant ID: ${tenantId}"
    az login --service-principal --debug ...
"""
```

### Performance Pior que Esperado
```groovy
// Verificar recursos do Jenkins:
deploy {
    resources {
        limits {
            cpus: '4'      // Aumentar se necessário
            memory: '4G'
        }
    }
}
```

---

## 📊 Comparação: Antes vs. Depois

### Jenkinsfile Original
```groovy
pipeline {
    agent any  // ❌ Um agente para tudo
    stages {
        stage('Checkout') { ... }
        stage('Pre-flight') { ... }     // ❌ Sequencial
        stage('Build') { ... }          // ❌ Sequencial
        stage('Tests') { ... }          // ❌ Sequencial
        stage('Deploy') { ... }
    }
    post {
        always {
            // ❌ Cleanup parcial
        }
    }
}
```

### Jenkinsfile Melhorado
```groovy
pipeline {
    agent none  // ✅ Agentes específicos por stage
    stages {
        stage('Fast Validation') {
            agent { docker { ... } }  // ✅ Isolado
        }
        stage('Static Analysis') {
            parallel {                // ✅ Paralelo
                stage('PHP') { ... }
                stage('Docker') { ... }
                stage('Env') { ... }
            }
        }
        stage('Build') { ... }
        stage('Testing') {
            parallel { ... }          // ✅ Paralelo
        }
        stage('Deploy') { ... }
    }
    post {
        always {
            cleanWs()                 // ✅ Cleanup completo
        }
    }
}
```

---

## 🎯 Validação de Sucesso

### Checklist Pós-Migração
- [ ] Build completo sem erros
- [ ] Tempo de build <= 20 minutos
- [ ] Logs claros e estruturados
- [ ] Artifacts arquivados (build-success.txt)
- [ ] Health check funcionando
- [ ] Deploy bem sucedido no Azure

### Testes de Regressão
```bash
# 1. Build de branch de feature
git checkout -b feature/test-123
# Fazer mudança mínima
git commit -m "test: trigger build"
git push

# 2. Build de main (deploy)
git checkout main
git merge feature/test-123
git push

# 3. Verificar:
# - Build passou?
# - Deploy funcionou?
# - App está rodando?
```

---

## 🔐 Rollback Plan

### Se Algo Der Errado

**Opção 1: Rollback Rápido**
```bash
# Restaurar backup
git checkout main
cp Jenkinsfile.v1.backup Jenkinsfile
git add Jenkinsfile
git commit -m "rollback: restore original pipeline"
git push
```

**Opção 2: Revert Commit**
```bash
git revert HEAD
git push
```

**Opção 3: Desabilitar Pipeline**
```bash
# No Jenkins UI:
# Job → Configure → Disable Project
```

---

## 📈 Próximas Otimizações

Após migração bem-sucedida, considere:

### 1. Cache de Dependências
```groovy
// Cachear composer/npm entre builds
volumes: [
    'jenkins_composer_cache:/root/.composer',
    'jenkins_npm_cache:/root/.npm'
]
```

### 2. Build Matrix
```groovy
// Testar múltiplas versões PHP
matrix {
    axes {
        axis {
            name 'PHP_VERSION'
            values '8.1', '8.2', '8.3'
        }
    }
}
```

### 3. Notificações
```groovy
post {
    failure {
        slackSend(
            color: 'danger',
            message: "Build ${env.BUILD_NUMBER} failed"
        )
    }
}
```

---

## 📞 Suporte

### Recursos
- 📖 [JENKINS_IMPROVEMENTS.md](./JENKINS_IMPROVEMENTS.md) - Documentação completa
- 📖 [Jenkinsfile.improved](../Jenkinsfile.improved) - Pipeline novo
- 📖 [vars/conflictDetection.groovy](../vars/conflictDetection.groovy) - Shared library

### Comandos Úteis
```bash
# Ver logs do Jenkins
docker logs sdc_jenkins_master -f

# Recarregar configuração
curl -X POST http://jenkins:8080/reload

# Trigger build manual
curl -X POST http://jenkins:8080/job/sdc/build
```

---

**✅ Migração Pronta!**

Após seguir este guia, seu pipeline terá:
- 🚀 40% mais rápido
- 🔒 Mais confiável (ambientes isolados)
- 📊 Melhor observabilidade
- 🛠️ Mais fácil de manter

**Boa sorte! 🎉**
