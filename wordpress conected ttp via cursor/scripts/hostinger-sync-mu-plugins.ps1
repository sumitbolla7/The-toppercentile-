# Push all MU plugins in this repo to Hostinger using FTP ACTIVE mode (no PASV).
# Run from repo root:  powershell -ExecutionPolicy Bypass -File .\scripts\hostinger-sync-mu-plugins.ps1
$ErrorActionPreference = "Stop"
$scriptDir = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
$root = (Resolve-Path (Join-Path $scriptDir "..")).Path
$upload = Join-Path $scriptDir "hostinger-ftp-upload.ps1"
$dir = Join-Path $root "wp-content\mu-plugins"
if (-not (Test-Path $dir)) { throw "Missing folder: $dir" }
Get-ChildItem -Path $dir -Filter "*.php" -File | ForEach-Object {
  $rel = "wp-content/mu-plugins/" + $_.Name
  Write-Host ">> $rel"
  & $upload -RelativePath $rel -LocalRoot $root
}
Write-Host "Done. MU plugins synced."
