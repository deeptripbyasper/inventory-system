$phpDir = "C:\Users\DEEP TRIP\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe"
$iniFile = Join-Path $phpDir "php.ini"

$content = Get-Content $iniFile -Raw
$content = $content.Replace(';extension=sqlite3', 'extension=sqlite3')
$content = $content.Replace(';extension=pdo_sqlite', 'extension=pdo_sqlite')

Set-Content -Path $iniFile -Value $content -Force

Write-Host "PHP Extensions Enabled:"
& "$phpDir\php.exe" -m | Select-String "mysqli", "pdo_mysql", "pdo_sqlite", "sqlite3"
