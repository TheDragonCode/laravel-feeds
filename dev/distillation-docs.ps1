Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$scriptDirectory = Split-Path -Parent $PSCommandPath
$docsDirectory = Split-Path -Parent $scriptDirectory/docs
$repositoryRoot = Split-Path -Parent $scriptDirectory

try {
    Push-Location -LiteralPath docsDirectory
    npm run build:llms

    Push-Location -LiteralPath $repositoryRoot
    codex '$aif-distillation docs/static/llms.txt --split --path skills --redact-source-map --name laravel-feeds'
}
finally {
    Pop-Location
}
