Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$scriptDirectory = Split-Path -Parent $PSCommandPath
$repositoryRoot = Split-Path -Parent $scriptDirectory
$docsDirectory = Join-Path $repositoryRoot 'docs'

Push-Location -LiteralPath $docsDirectory
try {
    npm run build:llms
    if ($LASTEXITCODE -ne 0) {
        throw "npm run build:llms failed with exit code $LASTEXITCODE."
    }

    Set-Location -LiteralPath $repositoryRoot
    codex '$aif-distillation docs/static/llms.txt --split --path skills --redact-source-map --name laravel-feeds'
    if ($LASTEXITCODE -ne 0) {
        throw "codex failed with exit code $LASTEXITCODE."
    }
}
finally {
    Pop-Location
}
