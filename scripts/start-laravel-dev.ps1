$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath (Resolve-Path "$PSScriptRoot\..")
& 'C:\xampp\php\php.exe' artisan serve --host=127.0.0.1 --port=8000
