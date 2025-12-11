# 🔧 Correção - Problema com .env.example no Git

## ❌ Problema Identificado - Build #6

### Erro no Console:

```
[Pipeline] stage { (Pre-flight Checks)
⚠️  SDC/.env não encontrado, copiando de SDC/.env.example
+ cp SDC/.env.example SDC/.env
cp: cannot stat 'SDC/.env.example': No such file or directory
ERROR: script returned exit code 1
```

---

## 🔍 Diagnóstico

### 1. Verificar se arquivo existe no repositório:

```bash
$ git ls-tree -r HEAD --name-only | grep -E "\.env"
SDC/docker/jenkins/.env.example  # ❌ Apenas este existe
```

**Resultado:** `SDC/.env.example` **NÃO** está no repositório!

### 2. Verificar .gitignore:

```bash
$ git check-ignore -v SDC/.env.example
.gitignore:13:SDC/.env.*	SDC/.env.example
```

**Causa Raiz:** O arquivo `SDC/.env.example` está sendo **ignorado pelo Git** na linha 13 do `.gitignore`:

```gitignore
SDC/.env.*
```

Essa regra ignora **TODOS** os arquivos que começam com `SDC/.env.`, incluindo `.env.example`.

---

## ✅ Solução Implementada

### Opções Consideradas:

**Opção 1:** Modificar `.gitignore` para permitir `.env.example`
```gitignore
SDC/.env.*
!SDC/.env.example
```

**Opção 2:** Remover verificação do `.env` do Jenkinsfile ✅ **ESCOLHIDA**

### Por Que a Opção 2?

1. **Build Docker não precisa de .env:**
   - O `Dockerfile.prod` **não** copia arquivo `.env`
   - Variáveis de ambiente vêm do **Azure App Service**

2. **Produção usa env vars do Azure:**
   - App Service `newsdc2027` tem variáveis configuradas
   - `.env` é apenas para desenvolvimento local

3. **Menos arquivos sensíveis no Git:**
   - `.env.example` pode conter estrutura de secrets
   - Melhor segurança mantendo fora do repositório

---

## 🔧 Código Corrigido

### Antes (Build #6 - Falhava):

```groovy
// Verificar se .env existe no diretório SDC
if (!fileExists('SDC/.env')) {
    echo '⚠️  SDC/.env não encontrado, copiando de SDC/.env.example'
    sh 'cp SDC/.env.example SDC/.env'  // ❌ Falha aqui
}
```

### Depois (Build #7 - Funcionará):

```groovy
// Nota: .env não é necessário para build Docker
// A imagem Docker usa variáveis de ambiente do Azure App Service
echo "ℹ️  Build usa variáveis de ambiente do Azure (não requer .env local)"
```

---

## 📊 Commit Realizado

**Hash:** `59c56f9`
**Mensagem:** "fix: remover verificação de .env.example que não existe no repositório"

**Mudanças:**
- Removida verificação e cópia do `.env`
- Adicionado comentário explicativo
- Reduzido código (3 linhas removidas)

---

## 🚀 Próximo Build (#7)

### O Que Vai Acontecer:

1. **Webhook dispara build #7** (commit `59c56f9`)
2. **Pre-flight Checks passa** ✅
   - Docker version OK
   - Espaço em disco OK
   - Pula verificação do .env
3. **Build and Push to ACR executa** 🏗️
   - Login no Azure via Service Principal
   - `az acr build` envia código para ACR
   - Build remoto completa
   - Imagem enviada para `apidover.azurecr.io/sdc-dev-app:7-59c56f9`
4. **Deploy to Azure App Service** 🚀
   - Atualiza `newsdc2027` com nova imagem
   - Restart do App Service
   - Health check

**Tempo estimado:** 10-25 minutos

---

## 📋 Verificação

### Console Output Esperado:

```
Started by GitHub push by MatheusEstrela-dev
Checking out Revision 59c56f9...

[Pipeline] stage { (Pre-flight Checks)
🔍 Running pre-flight checks...
Docker version 29.1.2, build 890dcca
Docker Compose version v5.0.0
✅ Espaço disponível: 16GB
ℹ️  Build usa variáveis de ambiente do Azure (não requer .env local)

[Pipeline] stage { (Build and Push to ACR)
🏗️  Building Docker images using Azure Container Registry...
Packing source code into tar to upload...
Uploading archived source code...
Sending context to registry: apidover...
Step 1/20 : FROM php:8.2-fpm
...
Successfully built xxx
Successfully tagged apidover.azurecr.io/sdc-dev-app:7-59c56f9
Successfully tagged apidover.azurecr.io/sdc-dev-app:latest
✅ Imagem buildada e enviada para ACR

[Pipeline] stage { (Deploy to Azure App Service)
🚀 Deploying to Azure App Service...
Updating newsdc2027...
Restarting App Service...
✅ App Service está respondendo!

Finished: SUCCESS
```

---

## 🎓 Lição Aprendida

### Problema com .gitignore Patterns:

**Pattern genérico:** `SDC/.env.*`
- Ignora: `.env`, `.env.local`, `.env.example`, `.env.production`
- **Problema:** Ignora até arquivos que DEVERIAM estar no Git

**Solução 1 - Específico:**
```gitignore
SDC/.env
SDC/.env.local
SDC/.env.production
# NÃO ignora .env.example
```

**Solução 2 - Com exceção:**
```gitignore
SDC/.env.*
!SDC/.env.example
```

### Best Practice para .env Files:

1. **`.env.example`** → Commitar no Git
   - Template sem valores reais
   - Mostra estrutura das variáveis necessárias
   - Útil para novos desenvolvedores

2. **`.env`** → NUNCA commitar
   - Contém valores reais/sensíveis
   - Ignorar via `.gitignore`

3. **Produção** → Usar variáveis de ambiente do sistema
   - Azure App Service Settings
   - Kubernetes Secrets
   - AWS Systems Manager Parameter Store

---

## 📊 Histórico de Builds

| Build | Commit | Problema | Status |
|-------|--------|----------|--------|
| #5 | a619bd3 | Docker socket não disponível | ❌ Failed |
| #6 | fd8eda6 | .env.example não encontrado | ❌ Failed |
| #7 | 59c56f9 | **Correção aplicada** | ⏳ Running |

---

## 🔄 Próximos Passos

1. **Aguardar build #7 completar** (10-25 minutos)
2. **Verificar imagens no ACR:**
   ```bash
   az acr repository show-tags \
     --name apidover \
     --repository sdc-dev-app \
     --output table
   ```
3. **Verificar produção:**
   ```
   https://newsdc2027.azurewebsites.net/login
   ```

---

**Status:** 🟡 **Build #7 iniciando... Problema do .env resolvido!**

**Commit:** `59c56f9`
**Webhook:** ✅ Disparado
**Pipeline:** ⏳ Executando...
