# 🔵🟢 Blue/Green Deployment - Guia Completo

## 📋 Índice
1. [O que é Blue/Green](#o-que-é-bluegreen)
2. [Arquitetura Azure](#arquitetura-azure)
3. [Fluxo do Pipeline](#fluxo-do-pipeline)
4. [Configuração Inicial](#configuração-inicial)
5. [Processo de Deploy](#processo-de-deploy)
6. [Rollback](#rollback)
7. [Monitoramento](#monitoramento)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 O que é Blue/Green

### Conceito
Blue/Green Deployment é uma estratégia de deploy que **elimina downtime** e **reduz risco** mantendo dois ambientes de produção idênticos:

| Ambiente | Status | Descrição |
|----------|--------|-----------|
| 🔵 **BLUE** | Live/Active | Versão atual em produção recebendo tráfego real |
| 🟢 **GREEN** | Idle/Staging | Nova versão deployada mas SEM tráfego público |

### Benefícios

✅ **Zero Downtime**: Swap instantâneo entre ambientes
✅ **Rollback Rápido**: < 1 minuto para reverter
✅ **Testes em Produção**: Validar GREEN antes de ir live
✅ **Redução de Risco**: Problemas detectados antes de afetar usuários
✅ **Confiança**: Aprovação manual opcional antes do swap

### Comparação com Outras Estratégias

| Estratégia | Downtime | Rollback | Complexidade | Custo |
|------------|----------|----------|--------------|-------|
| **Recreate** | ❌ Alto | ⚠️ Lento | ✅ Baixa | ✅ Baixo |
| **Rolling** | ⚠️ Parcial | ⚠️ Médio | ⚠️ Média | ⚠️ Médio |
| **Blue/Green** | ✅ Zero | ✅ Rápido | ⚠️ Média | ❌ Alto |
| **Canary** | ✅ Zero | ✅ Rápido | ❌ Alta | ❌ Alto |

---

## ☁️ Arquitetura Azure

### Azure App Service - Deployment Slots

Azure App Service suporta nativamente Blue/Green através de **Deployment Slots**:

```
newsdc2027 (App Service)
├── production (BLUE) 🔵
│   └── URL: https://newsdc2027.azurewebsites.net
│   └── Status: LIVE (recebe tráfego)
│   └── Image: apidover.azurecr.io/sdc-dev-app:123-abc1234
│
└── staging (GREEN) 🟢
    └── URL: https://newsdc2027-staging.azurewebsites.net
    └── Status: IDLE (sem tráfego público)
    └── Image: apidover.azurecr.io/sdc-dev-app:124-xyz5678
```

### Funcionamento do Swap

```
ANTES DO SWAP:
┌─────────────────┐
│   Users         │
└────────┬────────┘
         │ Traffic
         ▼
    ┌────────┐
    │  BLUE  │ 🔵 production (v1.0)
    └────────┘

    ┌────────┐
    │ GREEN  │ 🟢 staging (v2.0) - SEM TRÁFEGO
    └────────┘


APÓS O SWAP (instantâneo):
┌─────────────────┐
│   Users         │
└────────┬────────┘
         │ Traffic
         ▼
    ┌────────┐
    │  BLUE  │ 🔵 production (v2.0) ← ERA GREEN
    └────────┘

    ┌────────┐
    │ GREEN  │ 🟢 staging (v1.0) ← ERA BLUE (backup)
    └────────┘
```

---

## 🔄 Fluxo do Pipeline

### Visão Geral

```
1. 📦 Build
   └─ Gera imagem Docker e push para ACR

2. 🟢 Deploy to GREEN
   └─ Atualiza staging slot com nova imagem
   └─ Reinicia staging slot

3. 🏥 Health Check on GREEN
   └─ Testa /health endpoint
   └─ Smoke tests (response time, headers)
   └─ Se falhar → ABORT (BLUE não é afetado)

4. 🚦 Approval Gate (opcional)
   └─ Revisão manual da versão GREEN
   └─ GREEN acessível via URL staging

5. 🔀 BLUE ↔ GREEN Swap
   └─ Azure troca os slots (< 1 min)
   └─ GREEN vira production (BLUE)
   └─ BLUE vira staging (GREEN)

6. ✅ Post-Swap Validation
   └─ Valida production após swap
   └─ Se falhar → Alerta para rollback manual

7. 🧹 Cleanup
   └─ Mantém GREEN (versão antiga) como backup
```

### Timeline Esperado

| Fase | Tempo | Acumulado |
|------|-------|-----------|
| Build | 5-10 min | 5-10 min |
| Deploy to GREEN | 2-3 min | 7-13 min |
| Health Check | 1-2 min | 8-15 min |
| Approval | 0-30 min | 8-45 min |
| **SWAP** | **< 1 min** | **9-46 min** |
| Validation | 1 min | 10-47 min |

**Total**: 10-47 minutos (dependendo de aprovação manual)

---

## ⚙️ Configuração Inicial

### 1. Criar Staging Slot no Azure

```bash
# Via CLI
az webapp deployment slot create \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --slot staging

# Ou via portal Azure:
# App Service → Deployment slots → Add Slot
# Nome: staging
# Clone settings from: production
```

### 2. Configurar Jenkins Credentials

Credentials já existentes:
- ✅ `azure-service-principal`
- ✅ `AZURE_TENANT_ID`

### 3. Ativar Pipeline Blue/Green

**Opção A: Substituir pipeline atual**
```bash
cp Jenkinsfile Jenkinsfile.standard.backup
cp Jenkinsfile.bluegreen Jenkinsfile
git add Jenkinsfile
git commit -m "feat: implement Blue/Green deployment"
git push
```

**Opção B: Criar job separado**
```
Jenkins → New Item → "SDC-BlueGreen"
Pipeline from SCM → Script Path: Jenkinsfile.bluegreen
```

---

## 🚀 Processo de Deploy

### Deploy Manual via Jenkins

1. **Trigger Build**
   ```
   Jenkins → SDC → Build Now
   ```

2. **Monitorar Stages**
   ```
   ✅ Checkout
   ✅ Build and Push to ACR
   ✅ Deploy to GREEN (staging)
   ✅ Health Check on GREEN
   ```

3. **Revisar GREEN (opcional)**
   - Acesse: `https://newsdc2027-staging.azurewebsites.net`
   - Teste funcionalidades críticas
   - Valide visualmente

4. **Aprovar Swap** (se approval gate habilitado)
   ```
   Pipeline pausará aguardando aprovação
   → Click "Deploy to Production"
   ```

5. **Swap Automático**
   ```
   🔀 BLUE ↔ GREEN Traffic Swap
   ✅ GREEN vira production
   ```

6. **Validação**
   ```
   🔍 Post-Swap Validation
   ✅ Production healthy
   ```

### Deploy Automático (via GitHub Webhook)

```bash
# Qualquer push para main/master triggera o pipeline
git checkout main
git merge feature/minha-feature
git push origin main

# Jenkins detecta via webhook e inicia Blue/Green deploy
```

---

## 🔙 Rollback

### Cenário 1: Problema Detectado no Health Check
**Status**: GREEN falhou health check
**Ação**: Pipeline aborta automaticamente
**Resultado**: BLUE (produção) não foi afetado ✅

```
❌ Health check failed on GREEN
→ Pipeline aborted
→ BLUE still live (no impact)
```

### Cenário 2: Problema Após Swap (Rollback Necessário)
**Status**: Swap ocorreu, mas produção tem problemas
**Ação**: Rollback manual

#### Via Azure CLI (RÁPIDO - < 1 min)

```bash
# Login
az login

# Swap de volta (rollback)
az webapp deployment slot swap \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --slot staging \
  --target-slot production

# Resultado: Versão anterior volta para produção
```

#### Via Azure Portal

```
1. Portal Azure → App Service "newsdc2027"
2. Deployment slots
3. Click "Swap"
4. Source: staging
5. Target: production
6. Click "Swap"
```

#### Via Jenkins (Re-deploy)

```
1. Encontrar build anterior (que estava funcionando)
2. Build → "Rebuild"
3. Pipeline faz novo Blue/Green com versão antiga
```

### Rollback Timeline

| Método | Tempo | Downtime |
|--------|-------|----------|
| Azure CLI | < 1 min | ~10 seg |
| Azure Portal | < 2 min | ~10 seg |
| Jenkins Re-deploy | ~10-15 min | ~10 seg |

---

## 📊 Monitoramento

### Métricas para Observar

#### Durante Deploy

```bash
# Logs do staging slot (GREEN)
az webapp log tail \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --slot staging

# Status do deployment
az webapp deployment slot list \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL
```

#### Após Swap

```bash
# Application Insights (se configurado)
# → Response times
# → Error rates
# → User traffic

# Health endpoint
curl https://newsdc2027.azurewebsites.net/health

# Logs production
az webapp log tail \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL
```

### Alertas Recomendados

1. **Error Rate Spike**
   - Threshold: > 5% errors
   - Action: Considerar rollback

2. **Response Time Degradation**
   - Threshold: > 3s avg response time
   - Action: Investigar performance

3. **Health Check Failures**
   - Threshold: 3 falhas consecutivas
   - Action: Rollback automático (futuro)

---

## 🔧 Troubleshooting

### Problema: Health Check Falha no GREEN

**Sintomas:**
```
❌ GREEN environment health check FAILED after 10 attempts
```

**Diagnóstico:**
```bash
# 1. Verificar logs do staging slot
az webapp log tail --name newsdc2027 --slot staging

# 2. Testar manualmente
curl -v https://newsdc2027-staging.azurewebsites.net/health

# 3. Verificar imagem Docker
az acr repository show-tags --name apidover --repository sdc-dev-app
```

**Soluções:**
- Imagem Docker com problema → Fix code e rebuild
- Timeout muito curto → Aumentar `HEALTH_CHECK_INTERVAL`
- App demora a subir → Adicionar warm-up time

---

### Problema: Swap Demora Muito

**Sintomas:**
```
⏳ Swap taking > 5 minutes
```

**Causas:**
- App Service plano muito baixo (B1, F1)
- Container muito grande (> 2GB)
- Muitas conexões abertas

**Soluções:**
```bash
# 1. Upgrade App Service plan
az appservice plan update \
  --name YourPlan \
  --resource-group DEFESA_CIVIL \
  --sku P1V2

# 2. Otimizar imagem Docker
# → Multi-stage builds
# → Remover arquivos desnecessários
# → Cache de layers
```

---

### Problema: GREEN Não Inicia

**Sintomas:**
```
Container didn't respond to HTTP pings on port 80
```

**Diagnóstico:**
```bash
# Ver logs detalhados
az webapp log download --name newsdc2027 --slot staging
unzip webapp_logs.zip
cat LogFiles/stderr.txt
```

**Soluções Comuns:**
- Port errado → Verificar Dockerfile `EXPOSE 80`
- Variáveis ambiente faltando → Copiar de production
- Permissões → Verificar ACR credentials

---

### Problema: Rollback Não Funciona

**Sintomas:**
```
Swap command succeeds but old version not restored
```

**Causa:**
- Slots foram deletados
- Configurações diferentes

**Solução:**
```bash
# Re-deploy versão específica
ACR_TAG="123-abc1234"  # Build antiga funcionando

az webapp config container set \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --docker-custom-image-name apidover.azurecr.io/sdc-dev-app:$ACR_TAG
```

---

## 📈 Próximos Passos

### Melhorias Futuras

1. **Canary Deployment**
   - Roteamento de tráfego gradual (10% → 50% → 100%)
   - Azure Traffic Manager

2. **Automated Rollback**
   - Rollback automático se error rate > threshold
   - Integration com Application Insights

3. **A/B Testing**
   - Testar features em % de usuários
   - Feature flags + slot routing

4. **Disaster Recovery**
   - Multi-region deployment
   - Backup automático de slots

---

## 📚 Recursos Adicionais

### Documentação Oficial
- [Azure App Service Deployment Slots](https://docs.microsoft.com/azure/app-service/deploy-staging-slots)
- [Blue-Green Deployments](https://martinfowler.com/bliki/BlueGreenDeployment.html)

### Comandos Úteis

```bash
# Listar todos os slots
az webapp deployment slot list \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --output table

# Ver configuração de um slot
az webapp config show \
  --name newsdc2027 \
  --slot staging

# Copiar configuração entre slots
az webapp config appsettings list \
  --name newsdc2027 > config.json

az webapp config appsettings set \
  --name newsdc2027 \
  --slot staging \
  --settings @config.json
```

---

## ✅ Checklist de Deploy

### Pré-Deploy
- [ ] Código revisado e testado localmente
- [ ] Testes automatizados passando
- [ ] Changelog atualizado
- [ ] Stakeholders notificados

### Durante Deploy
- [ ] Monitorar logs do GREEN
- [ ] Health checks passaram
- [ ] Smoke tests validados
- [ ] Revisar staging URL manualmente

### Pós-Deploy
- [ ] Validação de produção OK
- [ ] Métricas normais (response time, errors)
- [ ] Usuários sem reclamações (primeiros 15 min)
- [ ] Documentar versão deployada

### Rollback (se necessário)
- [ ] Swap de volta executado
- [ ] Produção voltou ao normal
- [ ] Incident report criado
- [ ] Fix planejado para próximo deploy

---

**Status**: ✅ Pronto para uso em produção!

**Última atualização**: 2025-12-12
**Versão**: 1.0
