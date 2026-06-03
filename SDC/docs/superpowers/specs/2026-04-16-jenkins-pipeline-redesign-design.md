# Jenkins Pipeline Redesign — Robustez, Backup e Observabilidade

**Data:** 2026-04-16  
**Stack:** FrankenPHP 8.3, Bun, Azure ACR, Azure App Service  
**Escopo:** Jenkinsfile + Dockerfile Jenkins + docker-compose.jenkins.yml + backup-local.sh + casc.yaml  
**Abordagem escolhida:** B — Redesign do Jenkinsfile + correcoes de infra

---

## 1. Contexto e Motivacao

O setup atual funciona mas tem problemas estruturais que comprometem confiabilidade em producao:

- `az login` duplicado em dois stages (DRY violation)
- Notificacoes Slack/Teams comentadas — falhas silenciosas
- Sem retry em operacoes de rede (`az acr build`, health check)
- Sem rollback automatico se o deploy produzir instancia doentia
- `pigz` chamado no backup sem estar instalado na imagem
- PHP 8.2 + Bun instalados no agente Jenkins sem necessidade (build e remoto no ACR)
- `JAVA_OPTS` definido duas vezes no Dockerfile (segundo sobrescreve o primeiro)
- Senhas default fracas no casc.yaml (`admin123`, etc)
- `version: '3.8'` deprecated no Docker Compose v2

---

## 2. Arquitetura Geral

Nenhuma mudanca na arquitetura de infraestrutura. Os servicos permanecem os mesmos:

```
Jenkins Master
  └── Docker Socket Proxy (tecnativa)
  └── Jenkins Agent
  └── Nginx (reverse proxy + SSL)
  └── Backup Local (GFS)
  └── Backup Remoto (S3/rsync)
  └── Prometheus Exporter
```

O build da aplicacao continua **100% remoto** via `az acr build` — o agente Jenkins nao precisa de Docker local, PHP ou Bun.

---

## 3. Jenkinsfile

### 3.1 Funcoes Internas (Closures)

Tres closures extraidos para eliminar duplicacao e centralizar comportamento:

**`azureLogin(clientId, clientSecret, tenantId)`**
- Executa `az login --service-principal`
- Unico ponto de auth usado em Build e Deploy
- Lanca erro explicito se `AZURE_TENANT_ID` nao estiver configurado

**`notify(status, message)`**
- Recebe `status` (SUCCESS, FAILURE, ROLLBACK, UNSTABLE)
- Sempre envia para Slack via `SLACK_WEBHOOK_URL`
- Envia para Teams via `TEAMS_WEBHOOK_URL` **apenas para** FAILURE e ROLLBACK (alertas criticos)
- Ambos opcionais — se a env var estiver vazia, skip silencioso
- Inclui: numero do build, branch, autor do commit, stage que falhou (se aplicavel), link do Jenkins

**`healthCheck(url, retries=10, intervalSeconds=15)`**
- Loop com `curl -sf` + timeout
- Retorna `true/false` — nao lanca excecao
- Usado tanto no pos-deploy quanto no Smoke Test stage

### 3.2 Stages

```
Checkout
  - checkout scm
  - extrai GIT_COMMIT_MSG e GIT_AUTHOR para uso nas notificacoes

Pre-flight
  - verifica docker --version, az --version
  - verifica espaco em disco (minimo 5GB)
  - falha rapido antes de consumir credenciais

Build & Push ACR
  - azureLogin()
  - retry(3) { az acr build ... }
  - intervalo de 30s entre tentativas
  - salva ACR_TAG em env para uso no rollback

Deploy to Azure App Service
  - only when: branch main ou master
  - azureLogin()
  - salva imagem atual como PREVIOUS_IMAGE antes de atualizar (para rollback)
  - az webapp config container set (nova imagem)
  - az webapp restart
  - healthCheck(APP_URL) — se falhar, executa rollback

Smoke Test
  - only when: branch main ou master
  - healthCheck adicional apos restart completo
  - verifica /health retorna 200

Code Quality & Tests
  - only when: NOT main/master (comportamento atual mantido)
```

### 3.3 Rollback

Se `healthCheck()` falhar no stage Deploy:

```groovy
az webapp config container set \
    --docker-custom-image-name ${PREVIOUS_IMAGE}
az webapp restart
notify(ROLLBACK, "Rollback executado — voltando para ${PREVIOUS_IMAGE}")
error("Deploy falhou — rollback executado para ${PREVIOUS_IMAGE}")
```

O `PREVIOUS_IMAGE` e capturado antes do deploy via:
```bash
az webapp config container show --query linuxFxVersion -o tsv
```

### 3.4 Post Actions

```
always:   limpeza de cache do workspace (composer, npm)
success:  notify(SUCCESS, "Deploy #N em producao — imagem: TAG")
failure:  notify(FAILURE, "Falha no stage [X]") + archiveArtifacts build-info.txt
unstable: notify(UNSTABLE, "Build #N com warnings")
```

### 3.5 Remocoes

- Todos os emojis nos `echo` (viola regra do projeto)
- Azure login duplicado no stage Deploy

---

## 4. Jenkins Dockerfile

### 4.1 O que e Removido

| Pacote | Motivo da remocao |
|---|---|
| `php8.2-cli` + extensoes | Build da app e remoto no ACR |
| `composer` | Idem |
| `bun` + `bunx` | Idem |

### 4.2 O que e Adicionado

| Pacote | Motivo |
|---|---|
| `pigz` | Compressao paralela usada no backup-local.sh |
| Azure CLI (via install-azure-cli.sh) | Ja existe o script, sera usado no RUN |

### 4.3 Correcoes

- `JAVA_OPTS` unificado em **uma unica** declaracao `ENV` no final do Dockerfile
- Remocao da declaracao duplicada na linha 108 (`-Djenkins.install.runSetupWizard=false`)
- Ordem de instrucoes reorganizada para melhor cache Docker: sistema → ferramentas estaveis → plugins

### 4.4 Resultado Esperado

Imagem ~30% menor. Sem dependencias mortas. `pigz` disponivel para o container de backup.

---

## 5. docker-compose.jenkins.yml

### 5.1 Remocoes

- `version: '3.8'` — Docker Compose v2 nao usa, gera warning

### 5.2 Backup Healthcheck

**Atual (fragil):**
```yaml
test: ["CMD-SHELL", "find /backups -type f -name 'jenkins-*.tar.gz' -mtime -1 | grep -q . || exit 1"]
```
Problema: falha na virada do dia se o backup rodou antes da meia-noite.

**Novo:**
```yaml
test: ["CMD-SHELL", "test -f /backups/last_backup_success && [ $(( $(date +%s) - $(cat /backups/last_backup_success) )) -lt 25200 ] || exit 1"]
```
Verifica se o arquivo `last_backup_success` existe e foi escrito ha menos de 7 horas (25200s), independente da data.

### 5.3 Watchtower

Mantido no profile `autoupdate`. Adiciona:
- `WATCHTOWER_SCHEDULE: "0 0 3 * * *"` — roda as 3h da manha
- `WATCHTOWER_NOTIFICATIONS: slack` — notifica antes de atualizar

---

## 6. backup-local.sh

### 6.1 Fallback de Compressao

```bash
if command -v pigz &>/dev/null; then
    COMPRESS_CMD="pigz -9 -p 2"
    TEST_CMD="pigz -t"
else
    COMPRESS_CMD="gzip -9"
    TEST_CMD="gzip -t"
fi
```

### 6.2 Arquivo de Controle

Ao final do backup com sucesso:
```bash
echo "$(date +%s)" > "${BACKUP_DIR}/last_backup_success"
```
Usado pelo healthcheck do compose.

### 6.3 Notificacoes Duplas

`notify_success()` e `notify_failure()` enviam para:
- Slack via `SLACK_WEBHOOK_URL`
- Teams via `TEAMS_WEBHOOK_URL`

Ambos opcionais — se vazio, skip.

---

## 7. casc.yaml

### 7.1 Senhas Default

**Atual:** `password: "${JENKINS_ADMIN_PASSWORD:-admin123}"`  
**Novo:** `password: "${JENKINS_ADMIN_PASSWORD}"` — sem fallback

Se a variavel nao estiver setada, JCasC falha na inicializacao com erro explicito. Isso forcara configuracao correta antes de subir em producao.

### 7.2 Teams Webhook

Adiciona configuracao do plugin `office-365-connector`:
```yaml
unclassified:
  office365ConnectorWebhookNotifier:
    webhooks:
      - url: "${TEAMS_WEBHOOK_URL:-}"
        name: "SDC Teams"
```

### 7.3 Plugins Adicionados

- `office-365-connector` — Teams integration
- (demais plugins existentes mantidos)

---

## 8. Fluxo de Notificacoes

```
Build inicia     → [sem notificacao, evita spam]
Build falha      → Slack + Teams: "FALHA no stage [X] — Build #N — link"
Deploy ok        → Slack + Teams: "Deploy #N em producao — imagem: TAG — autor: X"
Rollback ativo   → Slack + Teams: "ROLLBACK executado — voltando para TAG_ANTERIOR"
Backup ok        → Slack: "Backup concluido — 2.3GB, 47s"
Backup falha     → Slack + Teams: "FALHA no backup — erro: mensagem"
```

Teams recebe apenas alertas criticos (falha, rollback, backup falho) para nao poluir o canal. Slack recebe tudo.

---

## 9. Arquivos Modificados

| Arquivo | Tipo de mudanca |
|---|---|
| `SDC/Jenkinsfile` | Reescrita completa |
| `SDC/docker/jenkins/Dockerfile` | Remocao de PHP/Bun, adicao pigz, fix JAVA_OPTS |
| `SDC/docker/docker-compose.jenkins.yml` | Remove version, fix healthcheck, watchtower schedule |
| `SDC/docker/jenkins/scripts/backup-local.sh` | Fallback gzip, last_backup_success, Teams notify |
| `SDC/docker/jenkins/casc.yaml` | Remove defaults fracos, adiciona Teams, office-365-connector |
| `SDC/docker/jenkins/.env.example` | Adiciona TEAMS_WEBHOOK_URL |

---

## 10. Fora de Escopo

- Migracao para Jenkins Shared Library (evolucao futura quando houver 2+ pipelines)
- Mudanca de provedor de build (az acr build remoto mantido)
- Configuracao do Prometheus/Grafana dashboard (monitoramento ja existe, sem alteracao)
- Testes automatizados no pipeline (comportamento atual mantido: so em non-main)
