@echo off
echo 🚀 Iniciando Vite para NewSDC...
echo.

cd /d "%~dp0"

REM Verificar se node_modules existe
if not exist "node_modules" (
    echo 📦 Instalando dependências...
    call bun install
    if errorlevel 1 (
        echo ❌ Erro ao instalar dependências!
        pause
        exit /b 1
    )
)

REM Verificar se a porta está em uso
netstat -ano | findstr :8081 >nul
if %errorlevel% equ 0 (
    echo ⚠️  Porta 8081 já está em uso!
    echo.
    echo Verificando processo...
    netstat -ano | findstr :8081
    echo.
    set /p resposta="Deseja encerrar o processo e continuar? (S/N): "
    if /i "%resposta%"=="S" (
        for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8081') do (
            taskkill /PID %%a /F >nul 2>&1
        )
        echo ✅ Processo encerrado
        timeout /t 2 /nobreak >nul
    ) else (
        echo ❌ Operação cancelada
        pause
        exit /b 1
    )
)

REM Iniciar o Vite
echo.
echo 🔥 Iniciando servidor Vite...
echo 📍 URL: http://localhost:8081
echo 📍 Laravel: https://localhost:19444/pae
echo.
echo Pressione Ctrl+C para parar o servidor
echo.

call bun run dev

pause







