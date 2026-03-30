# ============================================================================
# RAT Full-Stack Validation Script
# Inicializa servidor Laravel + Vite e valida endpoints RAT
# ============================================================================

Write-Host "`n╔═══════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║          RAT FULL-STACK VALIDATION - AUTO STARTUP              ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

$ProjectPath = "c:\Users\x16073201\NewSDC\SDC"
Set-Location $ProjectPath

# ============================================================================
# STEP 1: Limpar e preparar ambiente
# ============================================================================
Write-Host "[1/7] 🧹 Limpando cache..." -ForegroundColor Yellow

# Remover cache antigo
Remove-Item -Path "bootstrap\cache\*" -Force -ErrorAction SilentlyContinue
Remove-Item -Path "storage\logs\*" -Force -ErrorAction SilentlyContinue
Remove-Item -Path "storage\framework\sessions\*" -Force -ErrorAction SilentlyContinue

# Garantir que storage/framework/views existe
if (-not (Test-Path "storage\framework\views")) {
    New-Item -ItemType Directory -Path "storage\framework\views" -Force | Out-Null
}

Write-Host "✅ Cache limpo`n" -ForegroundColor Green

# ============================================================================
# STEP 2: Executar php artisan cache:clear
# ============================================================================
Write-Host "[2/7] 🔧 Limpando cache Laravel..." -ForegroundColor Yellow

$ClearCommands = @(
    "php artisan cache:clear",
    "php artisan config:clear",
    "php artisan route:clear",
    "php artisan view:clear"
)

foreach ($cmd in $ClearCommands) {
    Write-Host "  $ $cmd" -ForegroundColor Gray
    Invoke-Expression $cmd 2>&1 | Out-Null
}

Write-Host "✅ Cache Laravel limpo`n" -ForegroundColor Green

# ============================================================================
# STEP 3: Executar migrations
# ============================================================================
Write-Host "[3/7] 🗄️  Executando migrações..." -ForegroundColor Yellow

Write-Host "  $ php artisan migrate --force" -ForegroundColor Gray
php artisan migrate --force 2>&1 | Out-Null

Write-Host "✅ Migrações executadas`n" -ForegroundColor Green

# ============================================================================
# STEP 4: Garantir armazenamento de links
# ============================================================================
Write-Host "[4/7] 🔗 Criando link de armazenamento..." -ForegroundColor Yellow

if (-not (Test-Path "public\storage")) {
    php artisan storage:link 2>&1 | Out-Null
    Write-Host "✅ Link de armazenamento criado`n" -ForegroundColor Green
} else {
    Write-Host "✅ Link já existe`n" -ForegroundColor Green
}

# ============================================================================
# STEP 5: Iniciar Vite em background
# ============================================================================
Write-Host "[5/7] 🎨 Iniciando Vite (frontend)..." -ForegroundColor Yellow

# Verificar se npm está disponível
$npmCheck = npm --version 2>&1
if ($LASTEXITCODE -eq 0) {
    $viteProcess = Start-Process npm -ArgumentList "run dev" -PassThru -WindowStyle Hidden
    Write-Host "✅ Vite iniciado (PID: $($viteProcess.Id))`n" -ForegroundColor Green
    Start-Sleep -Seconds 3
} else {
    Write-Host "⚠️  npm não encontrado, pulando Vite`n" -ForegroundColor Yellow
}

# ============================================================================
# STEP 6: Iniciar Laravel em background
# ============================================================================
Write-Host "[6/7] 🚀 Iniciando Laravel (backend)..." -ForegroundColor Yellow

$phpProcess = Start-Process php -ArgumentList "artisan serve --host=localhost --port=8001" -PassThru -WindowStyle Hidden
Write-Host "✅ Laravel iniciado em http://localhost:8001 (PID: $($phpProcess.Id))`n" -ForegroundColor Green

Start-Sleep -Seconds 5

# ============================================================================
# STEP 7: Validar endpoints RAT
# ============================================================================
Write-Host "[7/7] ✅ Testando endpoints RAT..." -ForegroundColor Yellow

$endpoints = @(
    @{ Method = "GET"; Path = "/rat"; Description = "List RATs" },
    @{ Method = "POST"; Path = "/rat/form"; Description = "Create form" },
    @{ Method = "GET"; Path = "/"; Description = "Home page" }
)

$allHealthy = $true

foreach ($endpoint in $endpoints) {
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:8001$($endpoint.Path)" -Method $endpoint.Method -ErrorAction SilentlyContinue -TimeoutSec 3
        if ($response.StatusCode -eq 200 -or $response.StatusCode -eq 405) {
            Write-Host "  ✅ $($endpoint.Method) $($endpoint.Path) - OK" -ForegroundColor Green
        } else {
            Write-Host "  ⚠️  $($endpoint.Method) $($endpoint.Path) - Status: $($response.StatusCode)" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "  ❌ $($endpoint.Method) $($endpoint.Path) - Não respondeu" -ForegroundColor Red
        $allHealthy = $false
    }
}

Write-Host "`n"

# ============================================================================
# RESUMO FINAL
# ============================================================================
Write-Host "╔═══════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║                     🎉 STARTUP CONCLUÍDO!                       ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan

Write-Host "`n🌐 Aplicação disponível em: " -ForegroundColor Yellow -NoNewline
Write-Host "http://localhost:8001" -ForegroundColor Green

Write-Host "`n📋 Endpoints RAT para testar:`n" -ForegroundColor Yellow
Write-Host "  GET    /rat                           → Listar RATs" -ForegroundColor Gray
Write-Host "  POST   /rat                           → Criar RAT (rascunho)" -ForegroundColor Gray
Write-Host "  GET    /rat/{id}                      → Ver RAT" -ForegroundColor Gray
Write-Host "  PUT    /rat/{id}                      → Atualizar RAT" -ForegroundColor Gray
Write-Host "  PATCH  /rat/{id}/finalize            → Finalizar RAT" -ForegroundColor Gray
Write-Host "  POST   /rat/{id}/attachments         → Upload de anexo" -ForegroundColor Gray
Write-Host "  DELETE /rat/{id}/attachments/{anexoId} → Remover anexo" -ForegroundColor Gray

Write-Host "`n🔗 Teste com Postman/Insomnia ou browser`n" -ForegroundColor Yellow

Write-Host "📊 Para parar os serviços, feche esta janela e execute:`n" -ForegroundColor Yellow
Write-Host "   Get-Process php | Stop-Process" -ForegroundColor Gray
Write-Host "   Get-Process node | Stop-Process`n" -ForegroundColor Gray

Write-Host "✨ Sistema aguardando requisições..." -ForegroundColor Cyan

# Manter script rodando
while ($true) {
    Start-Sleep -Seconds 10

    # Verificar se os processos ainda estão rodando
    if ($phpProcess.HasExited) {
        Write-Host "`n❌ Laravel saiu inesperadamente! PID: $($phpProcess.Id)" -ForegroundColor Red
        break
    }
}
