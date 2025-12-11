# ============================================================================
# SDC - Configurar Webhook do GitHub para CI/CD Automático
# ============================================================================
# Uso: .\configurar-webhook-github.ps1 -Repo "usuario/New_SDC" -Token "ghp_xxx" -JenkinsUrl "http://localhost:8090"
# ============================================================================

param(
    [Parameter(Mandatory=$true)]
    [string]$Repo,
    
    [Parameter(Mandatory=$true)]
    [string]$Token,
    
    [string]$JenkinsUrl = "http://localhost:8090",
    
    [string]$Secret = ""
)

$ErrorActionPreference = "Stop"

$WebhookUrl = "${JenkinsUrl}/github-webhook/"

# Gerar secret se não fornecido
if ([string]::IsNullOrEmpty($Secret)) {
    $bytes = New-Object byte[] 32
    [System.Security.Cryptography.RandomNumberGenerator]::Fill($bytes)
    $Secret = [System.BitConverter]::ToString($bytes).Replace("-", "").ToLower()
    Write-Host "⚠ Secret gerado automaticamente. Guarde este valor:" -ForegroundColor Yellow
    Write-Host $Secret -ForegroundColor Cyan
    Write-Host ""
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "SDC - Configurar Webhook GitHub" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Configuração:" -ForegroundColor Blue
Write-Host "  Repositório: $Repo"
Write-Host "  Jenkins URL: $JenkinsUrl"
Write-Host "  Webhook URL: $WebhookUrl"
Write-Host ""

# Verificar se Jenkins está acessível
Write-Host "[1/4] Verificando acesso ao Jenkins..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "${JenkinsUrl}/login" -Method Get -UseBasicParsing -ErrorAction Stop
    Write-Host "✓ Jenkins está acessível" -ForegroundColor Green
} catch {
    Write-Host "✗ Jenkins não está acessível em $JenkinsUrl" -ForegroundColor Red
    Write-Host "  Verifique se o container está rodando: docker ps | Select-String jenkins" -ForegroundColor Yellow
    exit 1
}

# Verificar webhooks existentes
Write-Host ""
Write-Host "[2/4] Verificando webhooks existentes..." -ForegroundColor Yellow
$headers = @{
    "Authorization" = "token $Token"
    "Accept" = "application/vnd.github.v3+json"
}

try {
    $existingHooks = Invoke-RestMethod -Uri "https://api.github.com/repos/$Repo/hooks" -Headers $headers -Method Get
    $oldHooks = $existingHooks | Where-Object { $_.config.url -like "*github-webhook*" }
    
    if ($oldHooks) {
        Write-Host "⚠ Webhook já existe. Removendo webhooks antigos..." -ForegroundColor Yellow
        foreach ($hook in $oldHooks) {
            try {
                Invoke-RestMethod -Uri "https://api.github.com/repos/$Repo/hooks/$($hook.id)" -Headers $headers -Method Delete | Out-Null
                Write-Host "✓ Webhook $($hook.id) removido" -ForegroundColor Green
            } catch {
                Write-Host "⚠ Não foi possível remover webhook $($hook.id)" -ForegroundColor Yellow
            }
        }
    }
} catch {
    Write-Host "⚠ Não foi possível verificar webhooks existentes" -ForegroundColor Yellow
}

# Criar novo webhook
Write-Host ""
Write-Host "[3/4] Criando webhook no GitHub..." -ForegroundColor Yellow

$webhookPayload = @{
    name = "web"
    active = $true
    events = @("push", "pull_request")
    config = @{
        url = $WebhookUrl
        content_type = "application/json"
        insecure_ssl = "0"
        secret = $Secret
    }
} | ConvertTo-Json -Depth 10

try {
    $response = Invoke-RestMethod -Uri "https://api.github.com/repos/$Repo/hooks" `
        -Headers $headers `
        -Method Post `
        -Body $webhookPayload `
        -ContentType "application/json"
    
    $WebhookId = $response.id
    Write-Host "✓ Webhook criado com sucesso!" -ForegroundColor Green
    Write-Host "  Webhook ID: $WebhookId" -ForegroundColor Cyan
} catch {
    Write-Host "✗ Erro ao criar webhook" -ForegroundColor Red
    Write-Host "  Erro: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "  Detalhes: $($_.ErrorDetails.Message)" -ForegroundColor Red
    }
    exit 1
}

# Testar webhook
Write-Host ""
Write-Host "[4/4] Testando webhook..." -ForegroundColor Yellow
if ($WebhookId) {
    try {
        Invoke-RestMethod -Uri "https://api.github.com/repos/$Repo/hooks/$WebhookId/tests" `
            -Headers $headers `
            -Method Post | Out-Null
        Write-Host "✓ Webhook testado com sucesso!" -ForegroundColor Green
    } catch {
        Write-Host "⚠ Não foi possível testar o webhook automaticamente" -ForegroundColor Yellow
        Write-Host "  Teste manualmente fazendo um push no GitHub" -ForegroundColor Yellow
    }
}

# Resumo
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ Webhook configurado com sucesso!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Informações importantes:" -ForegroundColor Blue
Write-Host "  Webhook URL: $WebhookUrl"
Write-Host "  Secret: $Secret"
Write-Host ""
Write-Host "Próximos passos:" -ForegroundColor Cyan
Write-Host "  1. Configure o secret no Jenkins:"
Write-Host "     Manage Jenkins → Configure System → GitHub → Advanced"
Write-Host "     Shared secret: $Secret"
Write-Host ""
Write-Host "  2. No Jenkins, configure o job:"
Write-Host "     Build Triggers → ✅ GitHub hook trigger for GITScm polling"
Write-Host ""
Write-Host "  3. Teste fazendo um push:"
Write-Host "     git commit --allow-empty -m 'test: Trigger CI/CD'"
Write-Host "     git push origin main"
Write-Host ""
Write-Host "  4. Verifique no Jenkins se o build iniciou automaticamente"
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan




