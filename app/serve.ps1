# Start Laravel dev server with project php-cli.ini (openssl, sqlite, etc.)
$Root = $PSScriptRoot
$Ini = Join-Path $Root 'php-cli.ini'
$Server = Join-Path $Root 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
if (Test-Path (Join-Path $Root 'server.php')) {
    $Server = Join-Path $Root 'server.php'
}
# Laravel server.php uses getcwd() as public path — run from /public (same as artisan serve)
$Public = Join-Path $Root 'public'
Set-Location $Public
Write-Host 'Starting http://127.0.0.1:8000 (php-cli.ini extensions enabled)' -ForegroundColor Green
php -c $Ini -S 127.0.0.1:8000 $Server
