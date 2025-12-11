# ============================================================================
# SDC - Script para Push da Imagem Jenkins para Azure Container Registry (ACR)
# ============================================================================
# Uso: .\docker\push-jenkins-to-acr.ps1 -AcrName "apidover" -Tag "latest"
# ============================================================================

param(
    [Parameter(Mandatory=$false)]
    [string]$AcrName = "apidover",

    [Parameter(Mandatory=$false)]
    [string]$ResourceGroup = "",

    [Parameter(Mandatory=$false)]
    [string]$Tag = "latest",

    [Parameter(Mandatory=$false)]
    [switch]$BuildFirst,

    [Parameter(Mandatory=$false)]
    [switch]$LoginOnly
)

$ErrorActionPreference = "Stop"

# Configuração
$ACR_LOGIN_SERVER = "${AcrName}.azurecr.io"
$IMAGE_NAME = "sdc-jenkins"
$LOCAL_IMAGE = "${IMAGE_NAME}:${Tag}"
$ACR_IMAGE = "${ACR_LOGIN_SERVER}/${IMAGE_NAME}:${Tag}"
$ACR_IMAGE_LATEST = "${ACR_LOGIN_SERVER}/${IMAGE_NAME}:latest"
$DOCKERFILE_PATH = ".\jenkins\Dockerfile.acr"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "SDC - Push Jenkins para Azure ACR" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "ACR: $ACR_LOGIN_SERVER" -ForegroundColor White
Write-Host "Imagem: $IMAGE_NAME" -ForegroundColor White
Write-Host "Tag: $Tag" -ForegroundColor White
Write-Host ""

# 1. Login no Azure
Write-Host "[1/6] Verificando login no Azure..." -ForegroundColor Yellow
try {
    $azAccount = az account show 2>$null | ConvertFrom-Json
    if (-not $azAccount) {
        Write-Host "Fazendo login no Azure..." -ForegroundColor Yellow
        az login
        $azAccount = az account show | ConvertFrom-Json
    } else {
        Write-Host "✓ Já autenticado no Azure" -ForegroundColor Green
        Write-Host "  Conta: $($azAccount.user.name)" -ForegroundColor Gray
        Write-Host "  Subscription: $($azAccount.name)" -ForegroundColor Gray
    }
} catch {
    Write-Host "✗ Erro ao verificar login no Azure. Instale o Azure CLI." -ForegroundColor Red
    exit 1
}

# 2. Login no ACR
Write-Host ""
Write-Host "[2/6] Fazendo login no ACR: $ACR_LOGIN_SERVER" -ForegroundColor Yellow
try {
    if ($ResourceGroup) {
        az acr login --name $AcrName --resource-group $ResourceGroup
    } else {
        az acr login --name $AcrName
    }
    Write-Host "✓ Login no ACR realizado com sucesso" -ForegroundColor Green
} catch {
    Write-Host "✗ Erro ao fazer login no ACR. Verifique o nome do registro." -ForegroundColor Red
    Write-Host "  Dica: az acr list --output table" -ForegroundColor Yellow
    exit 1
}

if ($LoginOnly) {
    Write-Host ""
    Write-Host "✓ Login realizado. Use o script novamente sem -LoginOnly para fazer push." -ForegroundColor Green
    exit 0
}

# 3. Build da imagem (se solicitado)
if ($BuildFirst) {
    Write-Host ""
    Write-Host "[3/6] Building imagem Jenkins..." -ForegroundColor Yellow
    Write-Host "  Dockerfile: $DOCKERFILE_PATH" -ForegroundColor Gray

    if (-not (Test-Path $DOCKERFILE_PATH)) {
        Write-Host "✗ Dockerfile não encontrado: $DOCKERFILE_PATH" -ForegroundColor Red
        exit 1
    }

    Push-Location -Path ".\jenkins"
    try {
        docker build -f Dockerfile.acr -t ${LOCAL_IMAGE} .
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✓ Build concluído: $LOCAL_IMAGE" -ForegroundColor Green
        } else {
            Write-Host "✗ Erro no build da imagem" -ForegroundColor Red
            exit 1
        }
    } finally {
        Pop-Location
    }
} else {
    Write-Host ""
    Write-Host "[3/6] Pulando build (use -BuildFirst para buildar)" -ForegroundColor Yellow

    # Verificar se a imagem existe localmente
    $imageExists = docker images -q $LOCAL_IMAGE
    if (-not $imageExists) {
        Write-Host "✗ Imagem $LOCAL_IMAGE não encontrada localmente." -ForegroundColor Red
        Write-Host "  Use -BuildFirst para buildar a imagem" -ForegroundColor Yellow
        exit 1
    }
    Write-Host "✓ Imagem encontrada localmente: $LOCAL_IMAGE" -ForegroundColor Green
}

# 4. Tag para ACR
Write-Host ""
Write-Host "[4/6] Aplicando tags ACR..." -ForegroundColor Yellow

# Tag com versão específica
docker tag $LOCAL_IMAGE $ACR_IMAGE
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Tag aplicada: $ACR_IMAGE" -ForegroundColor Green
} else {
    Write-Host "✗ Erro ao aplicar tag" -ForegroundColor Red
    exit 1
}

# Tag como latest (se não for latest)
if ($Tag -ne "latest") {
    docker tag $LOCAL_IMAGE $ACR_IMAGE_LATEST
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Tag aplicada: $ACR_IMAGE_LATEST" -ForegroundColor Green
    } else {
        Write-Host "⚠ Aviso: Erro ao aplicar tag latest" -ForegroundColor Yellow
    }
}

# 5. Push para ACR
Write-Host ""
Write-Host "[5/6] Fazendo push para o ACR..." -ForegroundColor Yellow

# Push tag específica
Write-Host "  Pushing: $ACR_IMAGE" -ForegroundColor Gray
docker push $ACR_IMAGE
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Push concluído: $ACR_IMAGE" -ForegroundColor Green
} else {
    Write-Host "✗ Erro ao fazer push" -ForegroundColor Red
    exit 1
}

# Push latest
if ($Tag -ne "latest") {
    Write-Host "  Pushing: $ACR_IMAGE_LATEST" -ForegroundColor Gray
    docker push $ACR_IMAGE_LATEST
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Push concluído: $ACR_IMAGE_LATEST" -ForegroundColor Green
    } else {
        Write-Host "⚠ Aviso: Erro ao fazer push da tag latest" -ForegroundColor Yellow
    }
}

# 6. Verificar no ACR
Write-Host ""
Write-Host "[6/6] Verificando imagens no ACR..." -ForegroundColor Yellow
try {
    $tags = az acr repository show-tags --name $AcrName --repository $IMAGE_NAME --output json | ConvertFrom-Json
    Write-Host "✓ Tags disponíveis no ACR:" -ForegroundColor Green
    foreach ($t in $tags) {
        Write-Host "  - ${ACR_LOGIN_SERVER}/${IMAGE_NAME}:$t" -ForegroundColor White
    }
} catch {
    Write-Host "⚠ Não foi possível listar tags do ACR" -ForegroundColor Yellow
}

# Resumo
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✓ PUSH CONCLUÍDO COM SUCESSO!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Imagem disponível em:" -ForegroundColor White
Write-Host "  $ACR_IMAGE" -ForegroundColor Cyan
if ($Tag -ne "latest") {
    Write-Host "  $ACR_IMAGE_LATEST" -ForegroundColor Cyan
}
Write-Host ""
Write-Host "Para usar no Azure Container Instances:" -ForegroundColor Yellow
Write-Host "  az container create --image $ACR_IMAGE ..." -ForegroundColor Gray
Write-Host ""
Write-Host "Para usar no Azure Kubernetes Service:" -ForegroundColor Yellow
Write-Host "  kubectl set image deployment/jenkins jenkins=$ACR_IMAGE" -ForegroundColor Gray
Write-Host ""
Write-Host "Para pull local:" -ForegroundColor Yellow
Write-Host "  docker pull $ACR_IMAGE" -ForegroundColor Gray
Write-Host "========================================" -ForegroundColor Cyan
