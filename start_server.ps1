#!/usr/bin/env pwsh
Push-Location -Path "c:\Users\x16073201\NewSDC\SDC"
try {
    Write-Host "Current directory: $(Get-Location)"
    Write-Host "Checking for artisan file..."
    if (Test-Path "artisan") {
        Write-Host "Found artisan, starting server..."
        & php artisan serve --host=localhost --port=8001
    } else {
        Write-Host "ERROR: artisan file not found in current directory"
        Get-ChildItem -Name | head -20
        exit 1
    }
}
finally {
    Pop-Location
}
