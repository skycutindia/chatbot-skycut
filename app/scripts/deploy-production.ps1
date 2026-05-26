# Production deploy helper (run from project root)
param(
    [switch]$SkipNpm,
    [switch]$SkipMigrate
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

$phpExe = 'php'
$phpIni = @()
if (Test-Path (Join-Path $Root 'php-cli.ini')) {
    $phpIni = @('-c', (Join-Path $Root 'php-cli.ini'))
}

function Run-Php([string[]]$Args) {
    & $phpExe @phpIni @Args
}

Write-Host '==> Composer (production)' -ForegroundColor Cyan
composer install --no-dev --optimize-autoloader --no-interaction

if (-not $SkipMigrate) {
    Write-Host '==> Migrate' -ForegroundColor Cyan
    Run-Php @('artisan', 'migrate', '--force')
}

if (-not $SkipNpm) {
    Write-Host '==> NPM build' -ForegroundColor Cyan
    npm ci
    npm run build
}

Write-Host '==> Laravel optimize' -ForegroundColor Cyan
Run-Php @('artisan', 'storage:link') 2>$null
Run-Php @('artisan', 'config:cache')
Run-Php @('artisan', 'route:cache')
Run-Php @('artisan', 'view:cache')
Run-Php @('artisan', 'platform:doctor')

Write-Host 'Deploy prep complete.' -ForegroundColor Green
