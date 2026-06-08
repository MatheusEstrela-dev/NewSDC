@echo off
REM ============================================================================
REM SDC - Quick Start Development Environment (Windows)
REM Monta TODOS os containers, exceto Node (Vite roda no HOST)
REM ============================================================================
echo.
echo ========================================
echo   SDC - Starting All Containers
echo ========================================
echo.

REM Set environment variables
set HOST_UID=1000
set HOST_GID=1000
set DOCKER_BUILDKIT=1
set COMPOSE_DOCKER_CLI_BUILD=1

REM Navigate to docker directory
cd /d "%~dp0"

echo [1/5] Checking Docker...
docker version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Docker is not running or not installed
    echo Please start Docker Desktop and try again
    pause
    exit /b 1
)
echo OK: Docker is running

echo.
echo [2/5] Creating network...
docker network inspect sdc_network >nul 2>&1
if errorlevel 1 (
    echo Creating network sdc_network...
    docker network create --driver bridge --subnet 172.26.0.0/16 sdc_network
) else (
    echo OK: Network already exists
)

echo.
echo [3/5] Starting containers...
echo   - App (Laravel Octane + Swoole)
echo   - PostgreSQL/PostGIS (Database)
echo   - Redis (Cache/Queue/Session)
echo   - Mailhog (Email Testing)
echo   - Queue (Workers)
echo   - Prometheus (Metrics)
echo   - Grafana (Dashboards)
echo   - Alertmanager (Alerts)
echo   - Node Exporter
echo   - cAdvisor
echo   - Redis Exporter
echo   - Blackbox Exporter
echo.
docker compose -f docker-compose.yml up -d

echo.
echo [4/5] Waiting for services to be healthy...
timeout /t 10 /nobreak >nul

echo.
echo [5/5] Checking container status...
docker compose -f docker-compose.yml ps

echo.
echo ========================================
echo   ALL CONTAINERS STARTED!
echo ========================================
echo.
echo APLICACAO:
echo   App (Swoole):      http://localhost:18001
echo   Mailhog:           http://localhost:8026
echo.
echo BANCO DE DADOS:
echo   PostgreSQL:        localhost:5432 (user: sdc, pass: secret)
echo   Redis:             localhost:6380
echo.
echo MONITORAMENTO:
echo   Grafana:           http://localhost:3000 (admin/admin@123)
echo   Prometheus:        http://localhost:9090
echo   Alertmanager:      http://localhost:9093
echo   cAdvisor:          http://localhost:8080
echo.
echo IMPORTANTE - VITE DEVE SER EXECUTADO NO HOST:
echo   1. Abra um novo terminal
echo   2. Navegue para: C:\Users\x24679188\Documents\GitHub\NewSDC\SDC
echo   3. Execute: bun run dev
echo   4. Vite estara em: http://localhost:5173
echo.
echo COMANDOS UTEIS:
echo   Migrations:        docker compose -f docker-compose.yml exec app php artisan migrate
echo   View logs:         docker compose -f docker-compose.yml logs -f
echo   Stop all:          stop-all.bat
echo   Or use Makefile:   make dev, make logs, make shell, etc.
echo.
pause
