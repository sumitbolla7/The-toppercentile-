# Deploy MU-plugins + TTP custom plugins via FTP ACTIVE (no PASV). Reads .vscode/sftp.json for host/user/pass.
param([string]$LocalRoot = "")
$scriptDir = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
if (-not $LocalRoot) {
  $LocalRoot = (Resolve-Path (Join-Path $scriptDir "..")).Path
}

$ErrorActionPreference = "Stop"
. (Join-Path $scriptDir "hostinger-ftp-lib.ps1")

$j = Get-HostingerSftpJson -LocalRoot $LocalRoot
$cred = Get-HostingerFtpCredential -Json $j
$h = [string]$j.host

$rootFull = (Resolve-Path -LiteralPath $LocalRoot).Path.TrimEnd('\')

function RelFromRoot([string]$fullPath) {
  return ($fullPath.Substring($rootFull.Length + 1)) -replace '\\', '/'
}

$mu = Join-Path $rootFull "wp-content\mu-plugins"
if (Test-Path -LiteralPath $mu) {
  Write-Host "=== wp-content/mu-plugins ==="
  Get-ChildItem -Path $mu -Recurse -File -Filter "*.php" | ForEach-Object {
    $rel = RelFromRoot $_.FullName
    Write-Host "  $rel"
    Send-FtpFileActive -HostName $h -Credential $cred -LocalFullPath $_.FullName -RemoteRelativeFromPublicHtml $rel
  }
}
else { Write-Host "SKIP: wp-content/mu-plugins" }

foreach ($name in @("ttp-woocommerce", "top-percentile-student-portal")) {
  $base = Join-Path $rootFull "wp-content\plugins\$name"
  if (-not (Test-Path -LiteralPath $base)) {
    Write-Host "SKIP: wp-content/plugins/$name"
    continue
  }
  Write-Host "=== wp-content/plugins/$name ==="
  Get-ChildItem -Path $base -Recurse -File | Where-Object { $_.FullName -notmatch '[\\/]\.git[\\/]' } | ForEach-Object {
    $rel = RelFromRoot $_.FullName
    Write-Host "  $rel"
    Send-FtpFileActive -HostName $h -Credential $cred -LocalFullPath $_.FullName -RemoteRelativeFromPublicHtml $rel
  }
}

Write-Host "DONE: all configured paths uploaded."
