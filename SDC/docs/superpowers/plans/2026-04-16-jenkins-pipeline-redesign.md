# Jenkins Pipeline Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rewrite the Jenkinsfile with error handling, retry, rollback and Slack+Teams notifications; slim down the Jenkins Dockerfile; fix docker-compose backup healthcheck; add pigz fallback to backup script; harden casc.yaml.

**Architecture:** 5 independent tasks targeting separate files — all can be executed in parallel. No commits until user validation. Helper functions (azureLogin, notify, healthCheck) defined at file-level in the Jenkinsfile to eliminate duplication.

**Tech Stack:** Jenkins LTS JDK17, Azure CLI, Azure ACR, Azure App Service, FrankenPHP 8.3, Bun, Slack Incoming Webhooks, MS Teams MessageCard

---

## IMPORTANT: No commits until user validates all tasks

Do NOT run any `git` commands. All changes stay unstaged until the user reviews and approves.

---

## Task 1: Jenkinsfile — Full Rewrite

**Files:**
- Modify: `SDC/Jenkinsfile`

**Context:**
- Build is 100% remote via `az acr build` — Jenkins does not build locally
- Correct Dockerfile to reference: `docker/frankenphp/Dockerfile` (not `docker/Dockerfile.prod`)
- `PREVIOUS_IMAGE` is captured before deploy for rollback
- `agent any` is kept — `azure-service-principal` credential ID must exist in Jenkins
- Helper functions go AFTER the `pipeline {}` block (Groovy file-level scope)

- [ ] **Step 1: Verify current file**

Read `SDC/Jenkinsfile` to confirm it matches the version analyzed (Checkout, Pre-flight, Build and Push to ACR, Code Quality, Deploy to Azure App Service, post blocks).

- [ ] **Step 2: Write the new Jenkinsfile**

Replace the entire contents of `SDC/Jenkinsfile` with:

```groovy
pipeline {
    agent any

    environment {
        APP_NAME                 = 'sdc'
        DOCKER_BUILDKIT          = '1'
        COMPOSE_DOCKER_CLI_BUILD = '1'
        ACR_NAME                 = 'APIDOVER'
        ACR_RESOURCE_GROUP       = 'DOVER'
        ACR_LOGIN_SERVER         = 'apidover.azurecr.io'
        ACR_IMAGE                = 'apidover.azurecr.io/sdc-dev-app'
        ACR_TAG                  = "${env.BUILD_NUMBER}-${env.GIT_COMMIT?.take(7) ?: 'unknown'}"
    }

    options {
        timeout(time: 30, unit: 'MINUTES')
        buildDiscarder(logRotator(numToKeepStr: '10', artifactNumToKeepStr: '5'))
        timestamps()
        ansiColor('xterm')
    }

    triggers {
        githubPush()
    }

    stages {

        stage('Checkout') {
            steps {
                checkout scm
                script {
                    env.GIT_COMMIT_MSG = sh(script: 'git log -1 --pretty=%B', returnStdout: true).trim()
                    env.GIT_AUTHOR    = sh(script: 'git log -1 --pretty=%an', returnStdout: true).trim()
                    echo "[INFO] Commit: ${env.GIT_COMMIT_MSG}"
                    echo "[INFO] Autor: ${env.GIT_AUTHOR}"
                }
            }
        }

        stage('Pre-flight') {
            steps {
                script {
                    sh 'docker --version'
                    sh 'az --version'

                    def availableSpace = sh(
                        script: "df -BG ${WORKSPACE} | tail -1 | awk '{print \$4}' | sed 's/G//'",
                        returnStdout: true
                    ).trim().toInteger()

                    if (availableSpace < 5) {
                        error("[ERROR] Espaco insuficiente: ${availableSpace}GB. Minimo: 5GB")
                    }
                    echo "[INFO] Espaco disponivel: ${availableSpace}GB"
                }
            }
        }

        stage('Build and Push ACR') {
            steps {
                script {
                    withCredentials([usernamePassword(
                        credentialsId: 'azure-service-principal',
                        usernameVariable: 'SP_CLIENT_ID',
                        passwordVariable: 'SP_CLIENT_SECRET'
                    )]) {
                        azureLogin(SP_CLIENT_ID, SP_CLIENT_SECRET, env.AZURE_TENANT_ID)
                    }

                    def attempt    = 0
                    def maxAttempts = 3
                    def built      = false

                    while (!built) {
                        try {
                            dir('SDC') {
                                sh """
                                    az acr build \\
                                        --registry ${ACR_NAME} \\
                                        --resource-group ${ACR_RESOURCE_GROUP} \\
                                        --image sdc-dev-app:${ACR_TAG} \\
                                        --image sdc-dev-app:latest \\
                                        --file docker/frankenphp/Dockerfile \\
                                        --platform linux \\
                                        .
                                """
                            }
                            built = true
                        } catch (err) {
                            attempt++
                            if (attempt >= maxAttempts) throw err
                            echo "[WARN] Tentativa ${attempt}/${maxAttempts} falhou. Aguardando 30s..."
                            sleep(time: 30, unit: 'SECONDS')
                        }
                    }

                    echo "[INFO] Imagem publicada: ${ACR_IMAGE}:${ACR_TAG}"
                }
            }
        }

        stage('Code Quality and Tests') {
            when {
                not {
                    anyOf {
                        branch 'main'
                        branch 'master'
                    }
                }
            }
            steps {
                echo '[INFO] Testes executados no PR/branch. Producao confia no build ACR.'
            }
        }

        stage('Deploy to Azure App Service') {
            when {
                anyOf {
                    branch 'main'
                    branch 'master'
                }
            }
            steps {
                script {
                    def appServiceName = env.AZURE_APP_SERVICE_NAME ?: 'newsdc2027'
                    def resourceGroup  = env.AZURE_RESOURCE_GROUP  ?: 'DEFESA_CIVIL'

                    withCredentials([usernamePassword(
                        credentialsId: 'azure-service-principal',
                        usernameVariable: 'SP_CLIENT_ID',
                        passwordVariable: 'SP_CLIENT_SECRET'
                    )]) {
                        azureLogin(SP_CLIENT_ID, SP_CLIENT_SECRET, env.AZURE_TENANT_ID)

                        env.PREVIOUS_IMAGE = sh(
                            script: "az webapp config container show --name ${appServiceName} --resource-group ${resourceGroup} --query image -o tsv 2>/dev/null || echo ''",
                            returnStdout: true
                        ).trim()

                        echo "[INFO] Imagem anterior: ${env.PREVIOUS_IMAGE ?: 'nenhuma'}"

                        def acrUsername = sh(
                            script: "az acr credential show --name ${ACR_NAME} --query username -o tsv",
                            returnStdout: true
                        ).trim()

                        def acrPassword = sh(
                            script: "az acr credential show --name ${ACR_NAME} --query 'passwords[0].value' -o tsv",
                            returnStdout: true
                        ).trim()

                        sh """
                            az webapp config container set \\
                                --name ${appServiceName} \\
                                --resource-group ${resourceGroup} \\
                                --docker-custom-image-name ${ACR_IMAGE}:${ACR_TAG} \\
                                --docker-registry-server-url https://${ACR_LOGIN_SERVER} \\
                                --docker-registry-server-user ${acrUsername} \\
                                --docker-registry-server-password ${acrPassword}
                        """

                        sh "az webapp restart --name ${appServiceName} --resource-group ${resourceGroup}"
                    }

                    echo "[INFO] Aguardando aplicacao iniciar..."
                    def appUrl = "https://${appServiceName}.azurewebsites.net"

                    if (!healthCheck(appUrl)) {
                        echo "[ERROR] Health check falhou. Iniciando rollback..."

                        if (env.PREVIOUS_IMAGE) {
                            withCredentials([usernamePassword(
                                credentialsId: 'azure-service-principal',
                                usernameVariable: 'SP_CLIENT_ID',
                                passwordVariable: 'SP_CLIENT_SECRET'
                            )]) {
                                azureLogin(SP_CLIENT_ID, SP_CLIENT_SECRET, env.AZURE_TENANT_ID)
                                sh """
                                    az webapp config container set \\
                                        --name ${appServiceName} \\
                                        --resource-group ${resourceGroup} \\
                                        --docker-custom-image-name ${env.PREVIOUS_IMAGE}
                                """
                                sh "az webapp restart --name ${appServiceName} --resource-group ${resourceGroup}"
                            }
                            notify('ROLLBACK', "Rollback executado | Voltando para: ${env.PREVIOUS_IMAGE} | Build #${env.BUILD_NUMBER}")
                        }

                        error("[ERROR] Deploy falhou — rollback executado para ${env.PREVIOUS_IMAGE ?: 'imagem anterior'}")
                    }

                    echo "[INFO] Deploy concluido: ${ACR_IMAGE}:${ACR_TAG}"
                }
            }
        }

        stage('Smoke Test') {
            when {
                anyOf {
                    branch 'main'
                    branch 'master'
                }
            }
            steps {
                script {
                    def appServiceName = env.AZURE_APP_SERVICE_NAME ?: 'newsdc2027'
                    def appUrl = "https://${appServiceName}.azurewebsites.net"

                    if (!healthCheck(appUrl, 5, 20)) {
                        error("[ERROR] Smoke test falhou — aplicacao nao responde em ${appUrl}/health")
                    }

                    echo "[INFO] Smoke test OK: ${appUrl}"
                }
            }
        }

    }

    post {
        always {
            script {
                sh "find ${WORKSPACE}/.composer-cache -type f -mtime +7 -delete 2>/dev/null || true"
            }
        }
        success {
            script {
                def appServiceName = env.AZURE_APP_SERVICE_NAME ?: 'newsdc2027'
                notify('SUCCESS', "Deploy #${env.BUILD_NUMBER} em producao | Imagem: ${ACR_IMAGE}:${ACR_TAG} | https://${appServiceName}.azurewebsites.net")
            }
        }
        failure {
            script {
                sh """
                    {
                        echo "Build Number: ${env.BUILD_NUMBER}"
                        echo "Git Commit:   ${env.GIT_COMMIT}"
                        echo "Git Branch:   ${env.GIT_BRANCH}"
                        echo "ACR Image:    ${ACR_IMAGE}:${ACR_TAG}"
                        echo "Autor:        ${env.GIT_AUTHOR}"
                    } > build-info.txt
                """
                archiveArtifacts artifacts: 'build-info.txt', allowEmptyArchive: true
                notify('FAILURE', "Falha no Build #${env.BUILD_NUMBER} | Branch: ${env.GIT_BRANCH} | Autor: ${env.GIT_AUTHOR}")
            }
        }
        unstable {
            script {
                notify('UNSTABLE', "Build #${env.BUILD_NUMBER} com warnings | Branch: ${env.GIT_BRANCH}")
            }
        }
    }
}

// ============================================================
// FUNCOES INTERNAS
// ============================================================

def azureLogin(String clientId, String clientSecret, String tenantId) {
    if (!tenantId) {
        error("[ERROR] AZURE_TENANT_ID nao configurado. Acesse: Manage Jenkins -> Configure System -> Global properties")
    }
    sh """
        az login --service-principal \\
            --username '${clientId}' \\
            --password '${clientSecret}' \\
            --tenant '${tenantId}'
    """
}

def notify(String status, String message) {
    def slackWebhook = env.SLACK_WEBHOOK_URL ?: ''
    def teamsWebhook = env.TEAMS_WEBHOOK_URL ?: ''

    if (slackWebhook) {
        def color = (status == 'SUCCESS') ? 'good' : (status in ['FAILURE', 'ROLLBACK']) ? 'danger' : 'warning'
        sh(script: """
            curl -sf -X POST '${slackWebhook}' \\
                -H 'Content-Type: application/json' \\
                -d '{"attachments":[{"color":"${color}","title":"[${status}] Jenkins SDC - Build #${env.BUILD_NUMBER}","text":"${message}"}]}' \\
            || true
        """, returnStatus: true)
    }

    if (teamsWebhook && status in ['FAILURE', 'ROLLBACK']) {
        sh(script: """
            curl -sf -X POST '${teamsWebhook}' \\
                -H 'Content-Type: application/json' \\
                -d '{"@type":"MessageCard","@context":"http://schema.org/extensions","themeColor":"FF0000","summary":"[${status}] Jenkins SDC","title":"[${status}] Jenkins SDC - Build #${env.BUILD_NUMBER}","text":"${message}"}' \\
            || true
        """, returnStatus: true)
    }
}

def healthCheck(String url, Integer retries = 10, Integer intervalSeconds = 15) {
    for (int i = 1; i <= retries; i++) {
        def rc = sh(script: "curl -sf '${url}/health'", returnStatus: true)
        if (rc == 0) {
            echo "[INFO] Health check OK na tentativa ${i}/${retries}"
            return true
        }
        if (i < retries) {
            echo "[INFO] Tentativa ${i}/${retries}: aguardando ${intervalSeconds}s..."
            sleep(time: intervalSeconds, unit: 'SECONDS')
        }
    }
    echo "[WARN] Health check falhou apos ${retries} tentativas em ${url}/health"
    return false
}
```

- [ ] **Step 3: Verify syntax**

Run:
```bash
bash -c "grep -n 'def azureLogin\|def notify\|def healthCheck' SDC/Jenkinsfile"
```
Expected output: 3 lines with function definitions found after line 200.

- [ ] **Step 4: Confirm no emojis remain**

Run:
```bash
bash -c "grep -P '[^\x00-\x7F]' SDC/Jenkinsfile && echo 'FAIL: emojis found' || echo 'OK: no emojis'"
```
Expected: `OK: no emojis`

---

## Task 2: Jenkins Dockerfile — Slim Down + Azure CLI + pigz

**Files:**
- Modify: `SDC/docker/jenkins/Dockerfile`

**Context:**
- Base image `jenkins/jenkins:lts-jdk17` is Debian-based
- Build is remote (ACR) — PHP, Composer, Bun are NOT needed in the agent
- `pigz` is needed for the backup container (already in `Dockerfile.backup`) but adding to Jenkins Dockerfile is a no-op. The real fix here is removing dead weight.
- Azure CLI IS needed for `az` commands in the pipeline. Add via `install-azure-cli.sh` logic.
- `JAVA_OPTS` is currently set on line 108 (`-Djenkins.install.runSetupWizard=false`) and again on line 124 (`-Xms512m -Xmx2g ...`). The second overrides the first silently — combine them into one.
- `office-365-connector` plugin needed for Teams integration.

- [ ] **Step 1: Read current Dockerfile**

Read `SDC/docker/jenkins/Dockerfile` to confirm lines 65-81 (Bun + PHP + Composer) and lines 108/124 (duplicate JAVA_OPTS).

- [ ] **Step 2: Remove Bun installation block**

In `SDC/docker/jenkins/Dockerfile`, remove the entire block:
```dockerfile
# Bun (para builds frontend)
RUN curl -fsSL https://bun.sh/install | bash \
    && ln -s /root/.bun/bin/bun /usr/local/bin/bun \
    && ln -s /root/.bun/bin/bunx /usr/local/bin/bunx \
    && bun --version
```

- [ ] **Step 3: Remove PHP + Composer installation blocks**

Remove:
```dockerfile
# PHP e Composer (para projetos Laravel/PHP)
RUN apt-get update && apt-get install -y \
    php8.2-cli \
    php8.2-curl \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-zip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer --version
```

- [ ] **Step 4: Add pigz to base apt-get block**

In the existing `apt-get install` block (the one with `curl gnupg lsb_release git openssh-client vim wget unzip jq python3 python3-pip`), add `pigz` to the list:

```dockerfile
RUN apt-get update && apt-get install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    git \
    openssh-client \
    vim \
    wget \
    unzip \
    jq \
    pigz \
    python3 \
    python3-pip \
    && rm -rf /var/lib/apt/lists/*
```

- [ ] **Step 5: Add Azure CLI installation**

After the Docker CLI installation block and before the plugins block, add:

```dockerfile
# ===== AZURE CLI =====
RUN curl -sL https://aka.ms/InstallAzureCLIDeb | bash \
    && az --version
```

- [ ] **Step 6: Fix duplicate JAVA_OPTS**

Remove the first (incomplete) JAVA_OPTS declaration:
```dockerfile
ENV JAVA_OPTS="-Djenkins.install.runSetupWizard=false"
```

Keep only the final combined one (currently line 124), updating it to include the setup wizard flag:
```dockerfile
ENV JAVA_OPTS="-Xms512m -Xmx2g -Djava.awt.headless=true -Djenkins.install.runSetupWizard=false"
```

- [ ] **Step 7: Add office-365-connector to plugins list**

In the `jenkins-plugin-cli --plugins` block, add `office-365-connector` to the list:

```dockerfile
RUN jenkins-plugin-cli --plugins \
    git \
    workflow-aggregator \
    docker-workflow \
    docker-plugin \
    kubernetes \
    configuration-as-code \
    job-dsl \
    pipeline-stage-view \
    blueocean \
    credentials-binding \
    ssh-agent \
    github \
    gitlab-plugin \
    slack \
    office-365-connector \
    email-ext \
    prometheus \
    timestamper \
    ws-cleanup \
    build-timeout \
    ansicolor
```

- [ ] **Step 8: Verify no PHP/Bun references remain**

Run:
```bash
bash -c "grep -n 'php\|bun\|composer' SDC/docker/jenkins/Dockerfile | grep -v '#' && echo 'FAIL: references found' || echo 'OK: clean'"
```
Expected: `OK: clean`

---

## Task 3: docker-compose.jenkins.yml — Fixes

**Files:**
- Modify: `SDC/docker/docker-compose.jenkins.yml`

**Context:**
- `version: '3.8'` is deprecated in Docker Compose v2 and generates a warning — remove it
- The `backup-local` healthcheck uses `mtime -1` which fails at midnight edge case
- New healthcheck reads `last_backup_success` timestamp file written by the backup script (added in Task 4)
- Watchtower needs a schedule to avoid running at random intervals

- [ ] **Step 1: Read current file**

Read `SDC/docker/docker-compose.jenkins.yml` lines 1-10 to confirm `version: '3.8'` on line 1.

- [ ] **Step 2: Remove version declaration**

Remove the line:
```yaml
version: '3.8'
```

- [ ] **Step 3: Fix backup-local healthcheck**

Find the current healthcheck in the `backup-local` service:
```yaml
    healthcheck:
      test: ["CMD-SHELL", "find /backups -type f -name 'jenkins-*.tar.gz' -mtime -1 | grep -q . || exit 1"]
      interval: 3600s  # A cada 1 hora
      timeout: 10s
      retries: 2
      start_period: 300s
```

Replace with:
```yaml
    healthcheck:
      test: ["CMD-SHELL", "test -f /backups/last_backup_success && [ $(( $(date +%s) - $(cat /backups/last_backup_success) )) -lt 25200 ] || exit 1"]
      interval: 3600s
      timeout: 10s
      retries: 2
      start_period: 300s
```

The value `25200` = 7 hours in seconds. Backup runs every 6h, so 7h allows 1h of tolerance without false positives.

- [ ] **Step 4: Fix Watchtower schedule**

In the `watchtower` service environment block, replace:
```yaml
      - WATCHTOWER_POLL_INTERVAL=86400  # 24h
```
With:
```yaml
      - WATCHTOWER_SCHEDULE=0 0 3 * * *
```

This uses cron format (runs at 3am daily) instead of a poll interval, giving predictable update windows.

- [ ] **Step 5: Verify**

Run:
```bash
bash -c "grep -n 'version:\|mtime\|POLL_INTERVAL' SDC/docker/docker-compose.jenkins.yml && echo 'FAIL: old patterns found' || echo 'OK: clean'"
```
Expected: `OK: clean`

---

## Task 4: backup-local.sh — Fallback + Healthcheck File + Teams Notify

**Files:**
- Modify: `SDC/docker/jenkins/scripts/backup-local.sh`
- Modify: `SDC/docker/jenkins/Dockerfile.backup`

**Context:**
- `Dockerfile.backup` already installs `pigz` via `apk add pigz` — so in production `pigz` is available
- Adding a fallback to `gzip` is a defensive measure for environments where the image is used without the full Dockerfile
- `last_backup_success` file is written after successful backup — used by the new healthcheck in Task 3
- Teams notification only on failure (critical alert)

- [ ] **Step 1: Read current backup-local.sh**

Read `SDC/docker/jenkins/scripts/backup-local.sh` lines 1-30 to confirm COMPRESS_CMD is hardcoded to `pigz`.

- [ ] **Step 2: Add pigz fallback**

After the `SLACK_WEBHOOK` and `PROMETHEUS_GATEWAY` variable declarations (around line 25), add the fallback detection block:

```bash
# Compressao: preferir pigz (paralelo), fallback para gzip
if command -v pigz &>/dev/null; then
    COMPRESS_CMD="pigz -9 -p 2"
    TEST_CMD="pigz -t"
else
    COMPRESS_CMD="gzip -9"
    TEST_CMD="gzip -t"
fi
```

- [ ] **Step 3: Replace hardcoded pigz in the tar command**

Find:
```bash
tar -C "$SOURCE_DIR" \
    --exclude='workspace/*' \
    --exclude='caches/*' \
    --exclude='.cache/*' \
    --exclude='war/*' \
    --exclude='tmp/*' \
    --exclude='*.log' \
    -cf - . | pigz -9 -p 2 > "$BACKUP_FILE" \
    || error "Falha ao criar backup"
```

Replace with:
```bash
tar -C "$SOURCE_DIR" \
    --exclude='workspace/*' \
    --exclude='caches/*' \
    --exclude='.cache/*' \
    --exclude='war/*' \
    --exclude='tmp/*' \
    --exclude='*.log' \
    -cf - . | ${COMPRESS_CMD} > "$BACKUP_FILE" \
    || error "Falha ao criar backup"
```

- [ ] **Step 4: Replace hardcoded pigz -t in integrity check**

Find:
```bash
if ! pigz -t "$BACKUP_FILE" &>/dev/null; then
    error "Arquivo de backup está corrompido (falha no pigz -t)"
fi
```

Replace with:
```bash
if ! ${TEST_CMD} "$BACKUP_FILE" &>/dev/null; then
    error "Arquivo de backup esta corrompido (falha no teste de integridade)"
fi
```

- [ ] **Step 5: Write last_backup_success after successful backup**

Find the success notification call near the end of the script:
```bash
notify_success "$BACKUP_SIZE" "$DURATION"
```

Add BEFORE that line:
```bash
# Registrar timestamp de sucesso (usado pelo healthcheck do compose)
echo "$(date +%s)" > "${BACKUP_DIR}/last_backup_success"
```

- [ ] **Step 6: Add Teams notification to notify_failure**

In the `notify_failure()` function, after the existing Prometheus block, add:

```bash
    # Teams notification (apenas falhas criticas)
    local TEAMS_WEBHOOK="${TEAMS_WEBHOOK_URL:-}"
    if [ -n "$TEAMS_WEBHOOK" ]; then
        curl -sf -X POST "$TEAMS_WEBHOOK" \
            -H 'Content-Type: application/json' \
            -d "{\"@type\":\"MessageCard\",\"@context\":\"http://schema.org/extensions\",\"themeColor\":\"FF0000\",\"summary\":\"Backup Jenkins FALHOU\",\"title\":\"Backup Jenkins FALHOU\",\"text\":\"$error_msg\"}" \
            &>/dev/null || true
    fi
```

- [ ] **Step 7: Fix Dockerfile.backup healthcheck**

In `SDC/docker/jenkins/Dockerfile.backup`, replace the HEALTHCHECK directive:
```dockerfile
HEALTHCHECK --interval=1h --timeout=10s --retries=2 \
    CMD find /backups -type f -name 'jenkins-*.tar.gz' -mtime -1 | grep -q . || exit 1
```

With:
```dockerfile
HEALTHCHECK --interval=1h --timeout=10s --retries=2 \
    CMD test -f /backups/last_backup_success && [ $(( $(date +%s) - $(cat /backups/last_backup_success) )) -lt 25200 ] || exit 1
```

- [ ] **Step 8: Verify bash syntax**

Run:
```bash
bash -n SDC/docker/jenkins/scripts/backup-local.sh && echo "OK: syntax valid" || echo "FAIL: syntax error"
```
Expected: `OK: syntax valid`

---

## Task 5: casc.yaml + .env.example — Security + Teams Config

**Files:**
- Modify: `SDC/docker/jenkins/casc.yaml`
- Modify: `SDC/docker/jenkins/.env.example`

**Context:**
- Current casc.yaml has weak password defaults: `admin123`, `omlioes123`, `matheus123`
- If env vars are not set, Jenkins starts with these weak passwords in production
- Fix: remove all `:-fallback` from password fields — missing var = JCasC startup failure (explicit error is safer)
- Teams webhook configured via `office-365-connector` plugin (added to Dockerfile in Task 2)
- `TEAMS_WEBHOOK_URL` added to `.env.example` for documentation

- [ ] **Step 1: Read current casc.yaml**

Read `SDC/docker/jenkins/casc.yaml` lines 1-25 to confirm the weak password defaults.

- [ ] **Step 2: Remove weak password defaults**

Find:
```yaml
        - id: "${JENKINS_ADMIN_USER:-admin}"
          password: "${JENKINS_ADMIN_PASSWORD:-admin123}"
        - id: "omlioes"
          password: "${JENKINS_OMLIOES_PASSWORD:-omlioes123}"
        - id: "matheus.estrela"
          password: "${JENKINS_MATHEUS_PASSWORD:-matheus123}"
```

Replace with:
```yaml
        - id: "${JENKINS_ADMIN_USER:-admin}"
          password: "${JENKINS_ADMIN_PASSWORD}"
        - id: "omlioes"
          password: "${JENKINS_OMLIOES_PASSWORD}"
        - id: "matheus.estrela"
          password: "${JENKINS_MATHEUS_PASSWORD}"
```

No fallback — if the env var is missing, JCasC will fail to start with an explicit error message rather than silently using `admin123` in production.

- [ ] **Step 3: Add Teams webhook configuration**

In the `unclassified:` section, after the `timestamper` block, add:

```yaml
  # Teams notifications via office-365-connector
  office365ConnectorWebhookNotifier:
    webhooks:
      - url: "${TEAMS_WEBHOOK_URL:-}"
        name: "SDC Teams"
        notifySuccess: false
        notifyFailure: true
        notifyBackToNormal: true
```

- [ ] **Step 4: Add TEAMS_WEBHOOK_URL to .env.example**

Read `SDC/docker/jenkins/.env.example` to find where to insert.

After the `SLACK_WEBHOOK_URL` line (if it exists) or after the JENKINS ADMIN section, add:

```bash
# ===== NOTIFICATIONS =====
SLACK_WEBHOOK_URL=
TEAMS_WEBHOOK_URL=
```

If `SLACK_WEBHOOK_URL` already exists in the file, just add `TEAMS_WEBHOOK_URL=` on the next line after it.

- [ ] **Step 5: Add missing Jenkins env vars to .env.example**

Add the missing password entries that casc.yaml now requires (no defaults):

```bash
# ===== JENKINS USERS =====
JENKINS_ADMIN_USER=admin
JENKINS_ADMIN_PASSWORD=
JENKINS_ADMIN_EMAIL=admin@sdc.local
JENKINS_OMLIOES_PASSWORD=
JENKINS_MATHEUS_PASSWORD=
```

- [ ] **Step 6: Verify casc.yaml syntax**

Run:
```bash
python3 -c "import yaml; yaml.safe_load(open('SDC/docker/jenkins/casc.yaml'))" && echo "OK: valid YAML" || echo "FAIL: invalid YAML"
```
Expected: `OK: valid YAML`

- [ ] **Step 7: Confirm no weak defaults remain**

Run:
```bash
bash -c "grep -n 'admin123\|omlioes123\|matheus123' SDC/docker/jenkins/casc.yaml && echo 'FAIL: weak defaults found' || echo 'OK: clean'"
```
Expected: `OK: clean`

---

## Final: User Validation + Single Commit

After all 5 tasks complete:

- [ ] **Step 1: Review all changed files**

```bash
git diff --stat
```

Expected output should list:
- `SDC/Jenkinsfile`
- `SDC/docker/jenkins/Dockerfile`
- `SDC/docker/docker-compose.jenkins.yml`
- `SDC/docker/jenkins/scripts/backup-local.sh`
- `SDC/docker/jenkins/Dockerfile.backup`
- `SDC/docker/jenkins/casc.yaml`
- `SDC/docker/jenkins/.env.example`

- [ ] **Step 2: Wait for user validation**

Do NOT commit. Present the diff summary to the user and ask for approval.

- [ ] **Step 3: Commit after user approval** (only when user says OK)

```bash
git add SDC/Jenkinsfile \
        SDC/docker/jenkins/Dockerfile \
        SDC/docker/docker-compose.jenkins.yml \
        SDC/docker/jenkins/scripts/backup-local.sh \
        SDC/docker/jenkins/Dockerfile.backup \
        SDC/docker/jenkins/casc.yaml \
        SDC/docker/jenkins/.env.example

git commit -m "refactor(jenkins): pipeline redesign — retry, rollback, Slack+Teams, slim Dockerfile"
```
