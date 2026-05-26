# Run PHPUnit with the same PHP extensions as serve.ps1
$AppRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $AppRoot

$ini = Join-Path $AppRoot "php-cli.ini"
php -c $ini vendor\bin\phpunit @args
