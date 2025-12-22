# Script PowerShell para iniciar o Vite
# Uso: .\iniciar-vite.ps1

Write-Host "🚀 Iniciando Vite para NewSDC..." -ForegroundColor Green

# Navegar para o diretório do projeto
Set-Location $PSScriptRoot

# Verificar se node_modules existe
if (-not (Test-Path "node_modules")) {
    Write-Host "📦 Instalando dependências..." -ForegroundColor Yellow
    npm install
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Erro ao instalar dependências!" -ForegroundColor Red
        exit 1
    }
}

# Verificar se a porta 5175 está em uso
$portaEmUso = Get-NetTCPConnection -LocalPort 5175 -ErrorAction SilentlyContinue
if ($portaEmUso) {
    Write-Host "⚠️  Porta 5175 já está em uso!" -ForegroundColor Yellow
    Write-Host "Processo usando a porta:" -ForegroundColor Yellow
    Get-Process -Id $portaEmUso.OwningProcess | Select-Object Id, ProcessName, Path
    Write-Host ""
    $resposta = Read-Host "Deseja encerrar o processo e continuar? (S/N)"
    if ($resposta -eq "S" -or $resposta -eq "s") {
        Stop-Process -Id $portaEmUso.OwningProcess -Force
        Write-Host "✅ Processo encerrado" -ForegroundColor Green
        Start-Sleep -Seconds 2
    } else {
        Write-Host "❌ Operação cancelada" -ForegroundColor Red
        exit 1
    }
}

# Iniciar o Vite
Write-Host "🔥 Iniciando servidor Vite..." -ForegroundColor Cyan
Write-Host "📍 URL: http://localhost:5175" -ForegroundColor Cyan
Write-Host "📍 Network: http://0.0.0.0:5175" -ForegroundColor Cyan
Write-Host ""
Write-Host "Pressione Ctrl+C para parar o servidor" -ForegroundColor Yellow
Write-Host ""

npm run dev









