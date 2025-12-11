# ============================================================================
# SDC - Build e Push de TODAS as imagens para Azure Container Registry
# Garante que todas as imagens estejam na mesma rede bridge
# ============================================================================
# Uso: .\build-and-push-all-to-acr.ps1 -AcrName "apidover" -Tag "latest"
# ============================================================================

param(
    [string]$AcrName = "apidover",
    [string]$Tag = "latest",
    [switch]$NoApp,
    [switch]$NoJenkins,
    [switch]$NoPush
)

$ErrorActionPreference = "Stop"

$ACR_LOGIN_SERVER = "${AcrName}.azurecr.io"
$SCRIPT_DIR = Split-Path -Parent $MyInvocation.MyCommand.Path
$PROJECT_ROOT = Split-Path -Parent $SCRIPT_DIR

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "SDC - Build e Push para Azure ACR" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Configuração:" -ForegroundColor Blue
Write-Host "  ACR: $ACR_LOGIN_SERVER"
Write-Host "  Tag: $Tag"
Write-Host "  Build App: $(-not $NoApp)"
Write-Host "  Build Jenkins: $(-not $NoJenkins)"
Write-Host "  Push to ACR: $(-not $NoPush)"
Write-Host ""

# 1. Verificar Azure CLI
Write-Host "[1/6] Verificando Azure CLI..." -ForegroundColor Yellow
if (-not (Get-Command az -ErrorAction SilentlyContinue)) {
    Write-Host "✗ Azure CLI não encontrado" -ForegroundColor Red
    Write-Host "  Instale: https://docs.microsoft.com/cli/azure/install-azure-cli" -ForegroundColor Yellow
    exit 1
}
Write-Host "✓ Azure CLI encontrado" -ForegroundColor Green

# 2. Login no Azure
Write-Host ""
Write-Host "[2/6] Verificando login no Azure..." -ForegroundColor Yellow
$account = az account show 2>$null
if (-not $account) {
    Write-Host "Fazendo login no Azure..." -ForegroundColor Yellow
    az login
}
else {
    $accountName = az account show --query name -o tsv
    Write-Host "✓ Autenticado como: $accountName" -ForegroundColor Green
}

# 3. Login no ACR
Write-Host ""
Write-Host "[3/6] Fazendo login no ACR: $ACR_LOGIN_SERVER" -ForegroundColor Yellow
$loginResult = az acr login --name $AcrName 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "✗ Erro ao fazer login no ACR" -ForegroundColor Red
    Write-Host "  Verifique se o ACR existe: az acr list" -ForegroundColor Yellow
    exit 1
}
Write-Host "✓ Login no ACR realizado" -ForegroundColor Green

# 4. Build da imagem App (Laravel)
if (-not $NoApp) {
    Write-Host ""
    Write-Host "[4/6] Building imagem App (Laravel)..." -ForegroundColor Yellow
    Write-Host "  Context: $PROJECT_ROOT"
    Write-Host "  Dockerfile: docker/Dockerfile.dev"

    $APP_IMAGE_LOCAL = "sdc-dev-app:${Tag}"
    $APP_IMAGE_ACR = "${ACR_LOGIN_SERVER}/sdc-dev-app:${Tag}"

    Push-Location $PROJECT_ROOT

    docker build `
        -f docker/Dockerfile.dev `
        -t "${APP_IMAGE_LOCAL}" `
        -t "${APP_IMAGE_ACR}" `
        --build-arg UID=1000 `
        --build-arg GID=1000 `
        .

    if ($LASTEXITCODE -ne 0) {
        Write-Host "✗ Erro no build da imagem App" -ForegroundColor Red
        Pop-Location
        exit 1
    }

    Write-Host "✓ Build App concluído" -ForegroundColor Green

    # Push App para ACR
    if (-not $NoPush) {
        Write-Host "  Fazendo push da imagem App..." -ForegroundColor Yellow
        docker push "${APP_IMAGE_ACR}"
        if ($LASTEXITCODE -ne 0) {
            Write-Host "✗ Erro no push da imagem App" -ForegroundColor Red
            Pop-Location
            exit 1
        }
        Write-Host "✓ Push App concluído: ${APP_IMAGE_ACR}" -ForegroundColor Green
    }

    Pop-Location
}

# 5. Build da imagem Jenkins
if (-not $NoJenkins) {
    Write-Host ""
    Write-Host "[5/6] Building imagem Jenkins..." -ForegroundColor Yellow
    Write-Host "  Context: $SCRIPT_DIR\jenkins"
    Write-Host "  Dockerfile: Dockerfile.acr"

    $JENKINS_IMAGE_LOCAL = "sdc-dev-jenkins:${Tag}"
    $JENKINS_IMAGE_ACR = "${ACR_LOGIN_SERVER}/sdc-dev-jenkins:${Tag}"

    Push-Location "$SCRIPT_DIR\jenkins"

    docker build `
        -f Dockerfile.acr `
        -t "${JENKINS_IMAGE_LOCAL}" `
        -t "${JENKINS_IMAGE_ACR}" `
        --build-arg DOCKER_GID=999 `
        .

    if ($LASTEXITCODE -ne 0) {
        Write-Host "✗ Erro no build da imagem Jenkins" -ForegroundColor Red
        Pop-Location
        exit 1
    }

    Write-Host "✓ Build Jenkins concluído" -ForegroundColor Green

    # Push Jenkins para ACR
    if (-not $NoPush) {
        Write-Host "  Fazendo push da imagem Jenkins..." -ForegroundColor Yellow
        docker push "${JENKINS_IMAGE_ACR}"
        if ($LASTEXITCODE -ne 0) {
            Write-Host "✗ Erro no push da imagem Jenkins" -ForegroundColor Red
            Pop-Location
            exit 1
        }
        Write-Host "✓ Push Jenkins concluído: ${JENKINS_IMAGE_ACR}" -ForegroundColor Green
    }

    Pop-Location
}

# 6. Verificar imagens no ACR
Write-Host ""
Write-Host "[6/6] Verificando imagens no ACR..." -ForegroundColor Yellow

if (-not $NoPush) {
    Write-Host "Imagens disponíveis no ACR:" -ForegroundColor Blue

    if (-not $NoApp) {
        Write-Host "  App:" -ForegroundColor Cyan
        az acr repository show-tags --name $AcrName --repository sdc-dev-app --output table 2>$null
        if ($LASTEXITCODE -ne 0) {
            Write-Host "    (nenhuma tag encontrada)" -ForegroundColor Yellow
        }
    }

    if (-not $NoJenkins) {
        Write-Host "  Jenkins:" -ForegroundColor Cyan
        az acr repository show-tags --name $AcrName --repository sdc-dev-jenkins --output table 2>$null
        if ($LASTEXITCODE -ne 0) {
            Write-Host "    (nenhuma tag encontrada)" -ForegroundColor Yellow
        }
    }
}

# Resumo final
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ Build e Push concluídos!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

if (-not $NoApp) {
    Write-Host "Imagem App:" -ForegroundColor Blue
    Write-Host "  ${ACR_LOGIN_SERVER}/sdc-dev-app:${Tag}"
    Write-Host ""
}

if (-not $NoJenkins) {
    Write-Host "Imagem Jenkins:" -ForegroundColor Blue
    Write-Host "  ${ACR_LOGIN_SERVER}/sdc-dev-jenkins:${Tag}"
    Write-Host ""
}

Write-Host "Para usar as imagens:" -ForegroundColor Cyan
Write-Host "  docker pull ${ACR_LOGIN_SERVER}/sdc-dev-app:${Tag}"
Write-Host "  docker pull ${ACR_LOGIN_SERVER}/sdc-dev-jenkins:${Tag}"
Write-Host ""
Write-Host "Para atualizar docker-compose.yml:" -ForegroundColor Cyan
Write-Host "  image: ${ACR_LOGIN_SERVER}/sdc-dev-app:${Tag}"
Write-Host "  image: ${ACR_LOGIN_SERVER}/sdc-dev-jenkins:${Tag}"
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan

