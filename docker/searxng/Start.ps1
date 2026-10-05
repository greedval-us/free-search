#Requires -Version 5.1
[CmdletBinding()]
param(
    [switch]$ValidateOnly
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$dockerCommand = Get-Command docker -CommandType Application -ErrorAction SilentlyContinue | Select-Object -First 1
$dockerPath = if ($dockerCommand) { $dockerCommand.Source } else { $null }

if (-not $dockerPath -and $env:ProgramFiles) {
    $desktopDockerPath = Join-Path $env:ProgramFiles 'Docker/Docker/resources/bin/docker.exe'
    if (Test-Path -LiteralPath $desktopDockerPath -PathType Leaf) {
        $dockerPath = $desktopDockerPath
    }
}

if (-not $dockerPath) {
    throw 'Docker CLI was not found. Install Docker Desktop with Linux containers and Compose v2, start it, then run this script again. Docker/WSL are not installed by this script.'
}

$null = & $dockerPath compose version --short
if ($LASTEXITCODE -ne 0) {
    throw 'Docker Compose v2 is unavailable. Enable or update the Compose plugin in Docker Desktop.'
}

$engineType = & $dockerPath info --format '{{.OSType}}'
if ($LASTEXITCODE -ne 0) {
    throw 'Docker engine is unavailable. Start Docker Desktop, wait for the engine to become ready, and try again.'
}
if (($engineType -join '').Trim() -ne 'linux') {
    throw 'SearXNG requires Linux containers. Switch Docker Desktop to Linux containers and try again.'
}

$composePath = Join-Path $PSScriptRoot 'compose.yaml'
$settingsPath = Join-Path $PSScriptRoot 'settings.yml'
$examplePath = Join-Path $PSScriptRoot '.env.example'
$localEnvPath = Join-Path $PSScriptRoot '.env'

foreach ($path in @($composePath, $settingsPath, $examplePath)) {
    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) {
        throw "Required SearXNG setup file is missing: $path"
    }
}

if (-not (Test-Path -LiteralPath $localEnvPath)) {
    $template = [System.IO.File]::ReadAllText($examplePath)
    if (-not [regex]::IsMatch($template, '(?m)^SEARXNG_SECRET=\r?$')) {
        throw 'The .env.example must contain an empty SEARXNG_SECRET entry.'
    }

    $secretBytes = New-Object byte[] 32
    $random = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try {
        $random.GetBytes($secretBytes)
    } finally {
        $random.Dispose()
    }
    $secret = [System.BitConverter]::ToString($secretBytes).Replace('-', '').ToLowerInvariant()
    $localEnv = [regex]::Replace($template, '(?m)^SEARXNG_SECRET=\r?$', "SEARXNG_SECRET=$secret")

    # CreateNew preserves an existing secret and prevents an accidental overwrite.
    $stream = [System.IO.File]::Open($localEnvPath, [System.IO.FileMode]::CreateNew, [System.IO.FileAccess]::Write, [System.IO.FileShare]::None)
    try {
        $encoding = New-Object System.Text.UTF8Encoding $false
        $writer = New-Object System.IO.StreamWriter $stream, $encoding
        try {
            $writer.Write($localEnv)
        } finally {
            $writer.Dispose()
        }
    } finally {
        $stream.Dispose()
    }
    Write-Host 'Created docker/searxng/.env with a random secret. The secret is not printed.'
}

$savedEnv = [System.IO.File]::ReadAllText($localEnvPath)
if (-not [regex]::IsMatch($savedEnv, '(?m)^SEARXNG_SECRET=[a-fA-F0-9]{64}\r?$')) {
    throw 'The local .env must contain a 64-character hexadecimal SEARXNG_SECRET. Keep the generated secret; remove only an unused placeholder .env to let the script create one.'
}

$composeArguments = @('compose', '--project-directory', $PSScriptRoot, '--env-file', $localEnvPath, '-f', $composePath)
$null = & $dockerPath @composeArguments config --quiet
if ($LASTEXITCODE -ne 0) {
    throw 'SearXNG Compose configuration is invalid. Review compose.yaml and the local .env.'
}

if ($ValidateOnly) {
    Write-Host 'SearXNG setup validated. No container was started.'
    return
}

& $dockerPath @composeArguments up --detach --wait --wait-timeout 120
if ($LASTEXITCODE -ne 0) {
    throw 'SearXNG did not become ready. Inspect it with docker compose logs using the compose.yaml in this directory.'
}

Write-Host 'SearXNG is ready at http://127.0.0.1:8088/ (loopback only).'
