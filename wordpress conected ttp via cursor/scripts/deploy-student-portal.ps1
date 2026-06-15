# Upload only Top Percentile Student Portal to Hostinger (FTP active). Uses .vscode/sftp.json like hostinger-deploy-all.ps1.
# Run from repo root:
#   powershell -ExecutionPolicy Bypass -File .\scripts\deploy-student-portal.ps1
param([string]$LocalRoot = "")
$ErrorActionPreference = "Stop"
$scriptDir = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
if (-not $LocalRoot) {
  $LocalRoot = (Resolve-Path (Join-Path $scriptDir "..")).Path
}
$LocalRoot = (Resolve-Path -LiteralPath $LocalRoot).Path.TrimEnd('\')

. (Join-Path $scriptDir "hostinger-ftp-lib.ps1")
$j = Get-HostingerSftpJson -LocalRoot $LocalRoot
$cred = Get-HostingerFtpCredential -Json $j
$h = [string]$j.host

function RelFromRoot([string]$fullPath) {
  return ($fullPath.Substring($LocalRoot.Length + 1)) -replace '\\', '/'
}

$base = Join-Path $LocalRoot "wp-content\plugins\top-percentile-student-portal"
if (-not (Test-Path -LiteralPath $base)) { throw "Missing: $base" }

Write-Host "=== Deploying wp-content/plugins/top-percentile-student-portal ($h) ==="
$n = 0
Get-ChildItem -Path $base -Recurse -File | Where-Object { $_.FullName -notmatch '[\\/]\.git[\\/]' } | ForEach-Object {
  $rel = RelFromRoot $_.FullName
  Write-Host "  $rel"
  Send-FtpFileActive -HostName $h -Credential $cred -LocalFullPath $_.FullName -RemoteRelativeFromPublicHtml $rel
  $n++
}
Write-Host "DONE: uploaded $n file(s). Open WP Admin -> Top Percentile Portal (should show v1.2.5 + UR auto-approve checkbox)."
