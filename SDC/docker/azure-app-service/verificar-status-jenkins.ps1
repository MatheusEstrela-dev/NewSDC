# ============================================================================
# SDC - Verificar Status do Jenkins e Builds
# ============================================================================
# Uso: .\verificar-status-jenkins.ps1
# ============================================================================

param(
    [string]$JenkinsUrl = "https://jenkinssdc.azurewebsites.net",
    [string]$JobName = "SDC"
)

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "╔══════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║        🔍 Verificar Status do Jenkins CI/CD                 ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Verificar se Jenkins está online
Write-Host "[1/4] Verificando se Jenkins está online..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$JenkinsUrl" -UseBasicParsing -Method Head -ErrorAction Stop
    Write-Host "  ✓ Jenkins está online (Status: $($response.StatusCode))" -ForegroundColor Green
} catch {
    Write-Host "  ✗ Jenkins não está acessível" -ForegroundColor Red
    Write-Host "  URL: $JenkinsUrl" -ForegroundColor Gray
    exit 1
}

# Verificar job
Write-Host ""
Write-Host "[2/4] Verificando job '$JobName'..." -ForegroundColor Yellow
$jobUrl = "$JenkinsUrl/job/$JobName"
try {
    $jobResponse = Invoke-WebRequest -Uri "$jobUrl/api/json" -UseBasicParsing -ErrorAction Stop
    $jobData = $jobResponse.Content | ConvertFrom-Json
    Write-Host "  ✓ Job encontrado: $($jobData.displayName)" -ForegroundColor Green
    Write-Host "  Status: $($jobData.color)" -ForegroundColor Gray
} catch {
    Write-Host "  ⚠️  Não foi possível acessar informações do job (pode requerer autenticação)" -ForegroundColor Yellow
    Write-Host "  URL do job: $jobUrl" -ForegroundColor Gray
}

# Verificar último build
Write-Host ""
Write-Host "[3/4] Verificando último build..." -ForegroundColor Yellow
try {
    $lastBuildUrl = "$jobUrl/lastBuild/api/json"
    $buildResponse = Invoke-WebRequest -Uri "$lastBuildUrl" -UseBasicParsing -ErrorAction Stop
    $buildData = $buildResponse.Content | ConvertFrom-Json
    
    Write-Host "  Build #$($buildData.number)" -ForegroundColor Cyan
    Write-Host "  Status: $($buildData.result)" -ForegroundColor $(if ($buildData.result -eq "SUCCESS") { "Green" } else { "Yellow" })
    Write-Host "  Iniciado: $($buildData.timestamp)" -ForegroundColor Gray
    Write-Host "  Duração: $($buildData.duration)ms" -ForegroundColor Gray
    
    if ($buildData.building) {
        Write-Host "  ⏳ Build em andamento..." -ForegroundColor Yellow
    }
    
    Write-Host ""
    Write-Host "  URL do build: $($buildData.url)" -ForegroundColor Cyan
} catch {
    Write-Host "  ⚠️  Não foi possível acessar informações do build" -ForegroundColor Yellow
    Write-Host "  (Pode requerer autenticação ou não há builds ainda)" -ForegroundColor Gray
}

# Verificar webhook do GitHub
Write-Host ""
Write-Host "[4/4] Informações sobre webhook..." -ForegroundColor Yellow
Write-Host "  Para verificar webhook no GitHub:" -ForegroundColor Cyan
Write-Host "  1. Acesse: https://github.com/MatheusEstrela-dev/NewSDC/settings/hooks" -ForegroundColor Gray
Write-Host "  2. Verifique se há webhook configurado para:" -ForegroundColor Gray
Write-Host "     $JenkinsUrl/github-webhook/" -ForegroundColor Gray
Write-Host ""
Write-Host "  Para testar manualmente:" -ForegroundColor Cyan
Write-Host "  - Acesse: $jobUrl" -ForegroundColor Gray
Write-Host "  - Clique em 'Build Now' para testar" -ForegroundColor Gray

# Resumo
Write-Host ""
Write-Host "╔══════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║        📊 Resumo                                            ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""
Write-Host "Jenkins URL: $JenkinsUrl" -ForegroundColor Blue
Write-Host "Job URL: $jobUrl" -ForegroundColor Blue
Write-Host ""
Write-Host "🔍 Para verificar builds em tempo real:" -ForegroundColor Yellow
Write-Host "   Acesse: $jobUrl" -ForegroundColor Cyan
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""




