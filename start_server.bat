@echo off
title Bandhu Chol Inventory System Server
setlocal enabledelayedexpansion

echo ===================================================
echo   Bandhu Chol - Inventory, Expiry & POS System
echo ===================================================
echo.

:: Locate PHP Binary
set "PHP_EXE=php"
where php >nul 2>nul
if %errorlevel% neq 0 (
    set "PHP_WINGET=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
    if exist "!PHP_WINGET!" (
        set "PHP_EXE=!PHP_WINGET!"
    ) else (
        echo [ERROR] PHP executable not found. Please install PHP or ensure it is on your PATH.
        pause
        exit /b 1
    )
)

echo [OK] Using PHP: !PHP_EXE!
echo [OK] Launching Development Server on http://localhost:8000
echo.
echo Press Ctrl+C anytime to stop the server.
echo.

start http://localhost:8000
"!PHP_EXE!" -S localhost:8000
pause
