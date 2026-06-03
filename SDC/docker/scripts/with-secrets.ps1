# ---------------------------------------------------------------------------
# Decripta secrets do docker/.env (gerenciado via dotenvx) e executa o
# comando recebido com as variaveis ja injetadas no ambiente.
#
# Uso (PowerShell):
#   .\docker\scripts\with-secrets.ps1 docker compose -f docker/compose.dev.yml up -d
#
# Requer:
#   - npm i (para ter o @dotenvx/dotenvx em node_modules);
#   - docker/.env.keys local (chave privada que decripta os 'encrypted:...').
# ---------------------------------------------------------------------------
$ErrorActionPreference = "Stop"

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot "..\..")

if (-not (Test-Path (Join-Path $projectRoot "docker\.env.keys"))) {
    Write-Warning "docker/.env.keys nao encontrado. Solicite a chave a um dev autorizado."
}

Push-Location $projectRoot
try {
    & npx dotenvx run -f "docker/.env" -- @Args
    exit $LASTEXITCODE
} finally {
    Pop-Location
}
