# Upload ttp-woocommerce (TCY integration) with retry + delay
# Handles Hostinger FTP connection timeouts by retrying each failed file

param([string]$LocalRoot = "")

$scriptDir = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
if (-not $LocalRoot) {
    $LocalRoot = (Resolve-Path (Join-Path $scriptDir "..")).Path
}

$ErrorActionPreference = "Stop"
. (Join-Path $scriptDir "hostinger-ftp-lib.ps1")

$j    = Get-HostingerSftpJson -LocalRoot $LocalRoot
$cred = Get-HostingerFtpCredential -Json $j
$h    = [string]$j.host

$rootFull = (Resolve-Path -LiteralPath $LocalRoot).Path.TrimEnd('\')
$uploaded = 0
$failed   = 0
$errors   = [System.Collections.Generic.List[string]]::new()

function RelFromRoot {
    param([string]$fullPath)
    return ($fullPath.Substring($rootFull.Length + 1)) -replace '\\', '/'
}

function Upload-WithRetry {
    param([string]$LocalPath, [string]$RemoteRel, [int]$MaxRetries = 3)
    $attempt = 0
    while ($attempt -lt $MaxRetries) {
        $attempt++
        try {
            Send-FtpFileActive -HostName $h -Credential $cred `
                -LocalFullPath $LocalPath `
                -RemoteRelativeFromPublicHtml $RemoteRel
            Write-Host "  [OK] $RemoteRel" -ForegroundColor Green
            $script:uploaded++
            Start-Sleep -Milliseconds 400
            return
        } catch {
            $msg = $_.Exception.Message
            if ($attempt -lt $MaxRetries) {
                Write-Host "  [RETRY $attempt] $RemoteRel" -ForegroundColor Yellow
                Start-Sleep -Seconds 3
            } else {
                Write-Host "  [FAIL] $RemoteRel : $msg" -ForegroundColor Red
                $script:failed++
                $script:errors.Add("FAIL: $RemoteRel")
            }
        }
    }
}

Write-Host ""
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host " TCY Integration Upload (ttp-woocommerce)" -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan

# These are the files that failed previously — upload them all with retry
$pluginDir = Join-Path $rootFull "wp-content\plugins\ttp-woocommerce"

if (-not (Test-Path -LiteralPath $pluginDir)) {
    Write-Host "ERROR: Not found: $pluginDir" -ForegroundColor Red
    exit 1
}

Get-ChildItem -Path $pluginDir -Recurse -File |
    Where-Object { $_.FullName -notmatch '[/\\]\.git[/\\]' } |
    Where-Object { $_.FullName -notmatch '[/\\]\.vscode[/\\]' } |
    ForEach-Object {
        $rel = RelFromRoot $_.FullName
        Upload-WithRetry -LocalPath $_.FullName -RemoteRel $rel
    }

Write-Host ""
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host " DONE: ttp-woocommerce (TCY)" -ForegroundColor Cyan
Write-Host "  Uploaded : $uploaded" -ForegroundColor Green
if ($failed -gt 0) {
    Write-Host "  Failed   : $failed" -ForegroundColor Red
    foreach ($e in $errors) { Write-Host "    $e" -ForegroundColor Red }
} else {
    Write-Host "  Failed   : 0 - All files uploaded!" -ForegroundColor Green
}
Write-Host "======================================================" -ForegroundColor Cyan
