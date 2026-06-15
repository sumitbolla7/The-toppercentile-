# TTP Full Custom Plugin Upload
# Uploads: ttp-notifications, ttp-affiliate, affiliate-notification-hub, mu-plugins
# Run from project root: .\scripts\upload-all-custom-plugins.ps1

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

Write-Host ""
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host " TTP Full Custom Plugin Upload to: $h" -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host " Local root: $LocalRoot"
Write-Host ""

$rootFull = (Resolve-Path -LiteralPath $LocalRoot).Path.TrimEnd('\')
$uploaded = 0
$failed   = 0
$errors   = [System.Collections.Generic.List[string]]::new()

function RelFromRoot {
    param([string]$fullPath)
    return ($fullPath.Substring($rootFull.Length + 1)) -replace '\\', '/'
}

function Upload-File {
    param([string]$LocalPath, [string]$RemoteRel)
    try {
        Send-FtpFileActive -HostName $h -Credential $cred `
            -LocalFullPath $LocalPath `
            -RemoteRelativeFromPublicHtml $RemoteRel
        Write-Host "  [OK] $RemoteRel" -ForegroundColor Green
        $script:uploaded++
    } catch {
        $msg = $_.Exception.Message
        Write-Host "  [FAIL] $RemoteRel : $msg" -ForegroundColor Red
        $script:failed++
        $script:errors.Add("FAIL: $RemoteRel -- $msg")
    }
}

function Upload-Dir {
    param([string]$Section, [string]$DirPath)
    if (-not (Test-Path -LiteralPath $DirPath)) {
        Write-Host "SKIP (not found): $Section" -ForegroundColor Yellow
        return
    }
    Write-Host ""
    Write-Host "=== $Section ===" -ForegroundColor Cyan
    Get-ChildItem -Path $DirPath -Recurse -File |
        Where-Object { $_.FullName -notmatch '[/\\]\.git[/\\]' } |
        ForEach-Object {
            $rel = RelFromRoot $_.FullName
            Upload-File -LocalPath $_.FullName -RemoteRel $rel
        }
}

# 1. ttp-notifications  (CRITICAL - fixes the fatal error)
Upload-Dir "wp-content/plugins/ttp-notifications [CRITICAL FIX]" `
           (Join-Path $rootFull "wp-content\plugins\ttp-notifications")

# 2. ttp-affiliate  (affiliate + referral + ATCY integration)
Upload-Dir "wp-content/plugins/ttp-affiliate [AFFILIATE + ATCY]" `
           (Join-Path $rootFull "wp-content\plugins\ttp-affiliate")

# 3. affiliate-notification-hub  (unified hub)
Upload-Dir "wp-content/plugins/affiliate-notification-hub [HUB]" `
           (Join-Path $rootFull "wp-content\plugins\affiliate-notification-hub")

# 4. mu-plugins  (only .php files, skip .off files)
$muDir = Join-Path $rootFull "wp-content\mu-plugins"
if (Test-Path -LiteralPath $muDir) {
    Write-Host ""
    Write-Host "=== wp-content/mu-plugins [MUST-USE PLUGINS] ===" -ForegroundColor Cyan
    Get-ChildItem -Path $muDir -Recurse -File |
        Where-Object { $_.Extension -eq ".php" } |
        Where-Object { $_.FullName -notmatch '[/\\]\.git[/\\]' } |
        ForEach-Object {
            $rel = RelFromRoot $_.FullName
            Upload-File -LocalPath $_.FullName -RemoteRel $rel
        }
} else {
    Write-Host "SKIP: wp-content/mu-plugins not found" -ForegroundColor Yellow
}

# Summary
Write-Host ""
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host " UPLOAD COMPLETE" -ForegroundColor Cyan
Write-Host "  Uploaded : $uploaded files" -ForegroundColor Green
if ($failed -gt 0) {
    Write-Host "  Failed   : $failed files" -ForegroundColor Red
    Write-Host ""
    Write-Host " Failed files:" -ForegroundColor Red
    foreach ($e in $errors) { Write-Host "   $e" -ForegroundColor Red }
} else {
    Write-Host "  Failed   : 0 - All files uploaded successfully!" -ForegroundColor Green
}
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "NEXT STEP: Visit https://thetoppercentile.co.in/wp-admin/" -ForegroundColor Yellow
Write-Host "           Test: Affiliate Hub -> Create Notification -> All Users" -ForegroundColor Yellow
