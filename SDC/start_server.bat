@echo off
cd /d c:\Users\x16073201\NewSDC\SDC
if %errorlevel% neq 0 (
    echo Error: Could not change directory
    pause
    exit /b 1
)
echo Current directory: %cd%
echo Starting Laravel server on 8001...
php artisan serve --host=localhost --port=8001
pause
