# 🚀 Otimizar CI/CD - Zero Downtime para Sistema Crítico

## 📊 Problema Atual

- ⏱️ **Tempo total de build:** ~5min (Build #10)
- ⏱️ **Downtime durante deploy:** ~8min
- 🔴 **Sistema crítico ficou fora do ar**

## 🎯 Objetivo

- ✅ Reduzir tempo de build de 5min para 2-3min
- ✅ **Zero downtime** durante deploy
- ✅ Rollback instantâneo em caso de problemas

---

## 🔵🟢 Solução 1: Blue-Green Deployment (ZERO DOWNTIME)

### Como Funciona:

1. **Production (Blue)** - Aplicação atual em produção
2. **Staging (Green)** - Nova versão é deployada aqui primeiro
3. **Swap** - Troca instantânea (< 2 segundos) entre slots
4. **Rollback** - Se algo falhar, swap de volta instantaneamente

### Implementação com Azure Deployment Slots:

#### Passo 1: Criar Deployment Slot

```bash
# Criar slot "staging"
az webapp deployment slot create \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --slot staging
```

#### Passo 2: Atualizar Jenkinsfile

```groovy
stage('Deploy to Azure App Service') {
    steps {
        echo '🚀 Deploying to STAGING slot (zero downtime)...'

        script {
            def APP_SERVICE_NAME = env.AZURE_APP_SERVICE_NAME ?: 'newsdc2027'
            def RESOURCE_GROUP = env.AZURE_RESOURCE_GROUP ?: 'DEFESA_CIVIL'
            def ACR_NAME = env.ACR_NAME ?: 'apidover'

            withCredentials([usernamePassword(
                credentialsId: 'azure-service-principal',
                usernameVariable: 'AZURE_CLIENT_ID',
                passwordVariable: 'AZURE_CLIENT_SECRET'
            )]) {
                sh """
                    az login --service-principal \
                        --username \$AZURE_CLIENT_ID \
                        --password \$AZURE_CLIENT_SECRET \
                        --tenant ${env.AZURE_TENANT_ID}
                """

                // Deploy para slot STAGING (não afeta produção)
                sh """
                    az webapp config container set \
                        --name ${APP_SERVICE_NAME} \
                        --resource-group ${RESOURCE_GROUP} \
                        --slot staging \
                        --docker-custom-image-name ${ACR_IMAGE}:${ACR_TAG} \
                        --docker-registry-server-url https://${ACR_LOGIN_SERVER} \
                        --docker-registry-server-user \$(az acr credential show --name ${ACR_NAME} --query username -o tsv) \
                        --docker-registry-server-password \$(az acr credential show --name ${ACR_NAME} --query 'passwords[0].value' -o tsv)
                """

                // Reiniciar slot staging
                sh """
                    az webapp restart \
                        --name ${APP_SERVICE_NAME} \
                        --resource-group ${RESOURCE_GROUP} \
                        --slot staging
                """

                // Health check no staging
                echo "Verificando staging slot..."
                timeout(time: 3, unit: 'MINUTES') {
                    sh """
                        for i in \$(seq 1 18); do
                            echo "Tentativa \$i/18: Testando staging..."
                            if curl -f -s -o /dev/null -w "%{http_code}" https://${APP_SERVICE_NAME}-staging.azurewebsites.net | grep -q "200\\|302"; then
                                echo "✅ Staging está OK!"
                                exit 0
                            fi
                            sleep 10
                        done
                        echo "❌ Staging não respondeu"
                        exit 1
                    """
                }

                // SWAP: Troca staging <-> production (< 2 segundos!)
                echo "🔄 Fazendo SWAP para produção (zero downtime)..."
                sh """
                    az webapp deployment slot swap \
                        --name ${APP_SERVICE_NAME} \
                        --resource-group ${RESOURCE_GROUP} \
                        --slot staging \
                        --target-slot production
                """

                echo "✅ Deploy concluído com ZERO DOWNTIME!"
                echo "🌐 Production: https://${APP_SERVICE_NAME}.azurewebsites.net"
                echo "🔧 Staging: https://${APP_SERVICE_NAME}-staging.azurewebsites.net"
            }
        }
    }
}
```

### Vantagens:

- ✅ **Zero downtime** - Swap leva < 2 segundos
- ✅ **Rollback instantâneo** - Se der problema, swap de volta
- ✅ **Testes em staging** - Valida antes de ir para produção
- ✅ **Warm-up** - Staging já está aquecido quando faz swap

---

## ⚡ Solução 2: Otimizar Build Docker (Reduzir de 5min para 2min)

### A. Cache de Camadas Docker

O ACR já faz cache, mas podemos otimizar a ordem no Dockerfile:

```dockerfile
# ============= OTIMIZADO PARA CACHE =============

FROM php:8.3-cli-alpine

# 1. Instalar dependências do sistema (raramente muda)
RUN apk add --no-cache bash curl git libpng libjpeg-turbo freetype libwebp \
    oniguruma libxml2 libzip postgresql-libs icu-libs

# 2. Instalar extensões PHP (raramente muda)
RUN apk add --no-cache --virtual .build-deps ... \
    && docker-php-ext-install ... \
    && apk del .build-deps

# 3. Copiar Composer (nunca muda)
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# 4. Instalar Node.js (raramente muda)
RUN apk add --no-cache nodejs npm

WORKDIR /var/www

# 5. Copiar apenas arquivos de dependências (muda pouco)
COPY composer.json composer.lock package.json package-lock.json ./

# 6. Instalar dependências (usa cache se arquivos não mudaram)
RUN composer install --no-dev --optimize-autoloader
RUN npm ci || npm install

# 7. Copiar código da aplicação (muda sempre) - POR ÚLTIMO!
COPY . .

# 8. Build de assets
RUN npm run build && apk del nodejs npm

# 9. Configuração final
RUN mkdir -p storage/framework/{cache,sessions,views} \
    && chmod -R 775 storage bootstrap/cache

CMD ["/start.sh"]
```

**Ganho:** Build de 5min → 2-3min (quando apenas código muda)

### B. Build Paralelo com Cache Externo

```bash
# No Jenkinsfile, usar build cache do ACR
az acr build \
    --registry ${ACR_NAME} \
    --resource-group ${ACR_RESOURCE_GROUP} \
    --image sdc-dev-app:${ACR_TAG} \
    --file docker/Dockerfile.prod \
    --platform linux \
    --no-logs \
    .
```

---

## 🔄 Solução 3: Rolling Deployment (Gradual)

Para App Service em modo Premium, pode configurar múltiplas instâncias:

```bash
# Escalar para 2 instâncias
az appservice plan update \
  --name <plan-name> \
  --resource-group DEFESA_CIVIL \
  --number-of-workers 2
```

O Azure faz rolling update automaticamente:
- Atualiza 1 instância por vez
- Downtime reduzido para ~2min

---

## 📊 Comparação de Estratégias

| Estratégia | Downtime | Tempo Total | Complexidade | Custo |
|------------|----------|-------------|--------------|-------|
| **Atual** | 8min | 5min build | Simples | Baixo |
| **Blue-Green** | 0s | 5min build + 2s swap | Média | Médio |
| **Cache otimizado** | 8min | 2-3min build | Baixa | Baixo |
| **Blue-Green + Cache** | 0s | 2-3min build + 2s swap | Média | Médio |
| **Rolling** | ~2min | 5min build | Baixa | Alto |

---

## 🎯 Recomendação para Sistema Crítico

### Implementação Rápida (1 hora):

1. ✅ **Criar deployment slot "staging"**
2. ✅ **Atualizar Jenkinsfile** para usar blue-green
3. ✅ **Testar** um deploy completo

### Ganhos Imediatos:
- ⏱️ Downtime: **8min → 0 segundos**
- 🔄 Rollback: **Instantâneo** (1 comando)
- 🧪 Testes: **Staging isolado** antes de produção

### Otimização Futura (2-3 horas):
4. ✅ Reordenar Dockerfile para melhor cache
5. ✅ Configurar health checks personalizados
6. ✅ Adicionar testes automatizados em staging

---

## 🚀 Quer que eu implemente agora?

Posso criar o deployment slot e atualizar o Jenkinsfile para você ter **zero downtime** no próximo deploy.

**Preciso apenas que você:**
1. Confirme que quer implementar blue-green deployment
2. Me avise se o App Service Plan permite deployment slots (plano B1 ou superior)

Isso vai resolver o problema crítico de **8 minutos de downtime** imediatamente!

---

## 📝 Comandos Rápidos

### Verificar se o plano suporta slots:
```bash
az appservice plan show \
  --name <plan-name> \
  --resource-group DEFESA_CIVIL \
  --query "sku.tier" -o tsv
```

Se for "Basic" ou superior → **Suporta slots!**

### Criar slot staging:
```bash
az webapp deployment slot create \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --slot staging
```

### Fazer swap manual (teste):
```bash
az webapp deployment slot swap \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --slot staging
```

Quer que eu implemente isso agora?
