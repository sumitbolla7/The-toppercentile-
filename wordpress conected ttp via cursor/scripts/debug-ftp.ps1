param([string]$LocalRoot = "")
$scriptDir = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
if (-not $LocalRoot) { $LocalRoot = (Resolve-Path (Join-Path $scriptDir "..")).Path }

. (Join-Path $scriptDir "hostinger-ftp-lib.ps1")

$cfgPath = Join-Path $LocalRoot ".vscode\sftp.json"
$raw = Get-Content $cfgPath -Raw -Encoding UTF8 | ConvertFrom-Json
$cfg = $raw[0]

Write-Host "host      : $($cfg.host)"
Write-Host "username  : $($cfg.username)"
Write-Host "password  : $($cfg.password.Substring(0,4))****"
Write-Host "remotePath: $($cfg.remotePath)"

$h = [string]$cfg.host
Write-Host "h value   : [$h]"
$uri = "ftp://$h/public_html/wp-content/plugins/ttp-notifications/index.php"
Write-Host "URI       : $uri"

try {
    $req = [System.Net.FtpWebRequest]::Create($uri)
    Write-Host "URI created OK"
} catch {
    Write-Host "URI failed: $_"
}
