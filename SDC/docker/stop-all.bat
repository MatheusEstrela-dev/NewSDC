@echo off
REM ============================================================================
REM SDC - Stop All Docker Containers (Windows)
REM ============================================================================
echo.
echo ========================================
echo   SDC - Stopping All Containers
echo ========================================
echo.

REM Navigate to docker directory
cd /d "%~dp0"

echo Stopping all containers...
docker compose -f docker-compose.yml --profile tools --profile node down

echo.
echo Checking for running containers...
docker ps --filter "name=newsdc" --format "table {{.Names}}\t{{.Status}}"

echo.
echo ========================================
echo   All Containers Stopped!
echo ========================================
echo.
echo To remove volumes as well (WARNING: deletes data!):
echo   docker compose -f docker-compose.yml down -v
echo.
echo To start again:
echo   start-dev.bat or make dev
echo.
pause
