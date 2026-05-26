# Interactive MySQL setup for AI Chatbot Hub Pro
# Prompts for root password, updates .env, creates DB, migrates, seeds.

$ErrorActionPreference = "Stop"
$AppRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $AppRoot

$env:PHPRC = Join-Path $AppRoot "php-cli.ini"

if (-not (Test-Path ".env")) {
    Write-Host "Missing .env — copy from .env.example first." -ForegroundColor Red
    exit 1
}

Write-Host "`n=== AI Chatbot Hub Pro — MySQL setup ===" -ForegroundColor Cyan
Write-Host "MySQL 8 must be running (service: MySQL80).`n"

$currentPass = (Select-String -Path ".env" -Pattern "^DB_PASSWORD=(.*)$").Matches.Groups[1].Value
if ($currentPass) {
    Write-Host "DB_PASSWORD is already set in .env. Press Enter to keep it, or type a new password."
    $input = Read-Host "MySQL root password"
    if ($input) { $password = $input } else { $password = $currentPass }
} else {
    $password = Read-Host "MySQL root password" -AsSecureString
    $password = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
        [Runtime.InteropServices.Marshal]::SecureStringToBSTR($password)
    )
}

if (-not $password) {
    Write-Host "Password cannot be empty." -ForegroundColor Red
    exit 1
}

# Update .env DB_PASSWORD
$content = Get-Content ".env" -Raw
if ($content -match "(?m)^DB_PASSWORD=.*$") {
    $content = $content -replace "(?m)^DB_PASSWORD=.*$", "DB_PASSWORD=$password"
} else {
    $content += "`nDB_PASSWORD=$password"
}
Set-Content ".env" $content.TrimEnd() -NoNewline
Add-Content ".env" ""

Write-Host "`nTesting connection..." -ForegroundColor Cyan
php artisan db:setup-mysql --seed
exit $LASTEXITCODE
