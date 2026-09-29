# Bandhu Chol PowerShell Launcher
$ErrorActionPreference = "Stop"

$phpPath = "C:\Users\DEEP TRIP\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
if (-not (Test-Path $phpPath)) {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd) {
        $phpPath = $cmd.Source
    } else {
        Write-Error "PHP binary not found."
    }
}

Write-Host "===================================================" -ForegroundColor Cyan
Write-Host "  Bandhu Chol Inventory & POS System Running" -ForegroundColor Green
Write-Host "===================================================" -ForegroundColor Cyan
Write-Host "URL: http://localhost:8000" -ForegroundColor Yellow
Write-Host "PHP: $phpPath" -ForegroundColor Gray
Write-Host ""

Start-Process "http://localhost:8000"
& $phpPath -S localhost:8000
