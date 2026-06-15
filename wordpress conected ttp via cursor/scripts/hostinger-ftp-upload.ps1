# Hostinger FTP upload using ACTIVE mode (avoids broken EPSV/PASV on passive).
# Usage: .\scripts\hostinger-ftp-upload.ps1 -RelativePath "wp-content/mu-plugins/ttp-login-page-consolidate.php"
param(
  [Parameter(Mandatory = $true)][string]$RelativePath,
  [string]$LocalRoot = ""
)
$scriptDir = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
if (-not $LocalRoot) {
  $LocalRoot = (Resolve-Path (Join-Path $scriptDir "..")).Path
}

$ErrorActionPreference = "Stop"
. (Join-Path $scriptDir "hostinger-ftp-lib.ps1")

$j = Get-HostingerSftpJson -LocalRoot $LocalRoot
$local = Join-Path $LocalRoot ($RelativePath -replace '/', '\')
if (-not (Test-Path -LiteralPath $local)) { throw "Local file missing: $local" }

$cred = Get-HostingerFtpCredential -Json $j
try {
  Send-FtpFileActive -HostName $j.host -Credential $cred -LocalFullPath $local -RemoteRelativeFromPublicHtml ($RelativePath -replace '\\', '/')
}
catch [System.Net.WebException] {
  if ($_.Exception.Message -match '530') {
    Write-Host "FTP 530: login rejected. In .vscode/sftp.json set ftpUsername (often uXXXX.domain) and optional ftpPassword from hPanel > Files > FTP Accounts (can differ from SSH password)." -ForegroundColor Yellow
  }
  throw
}
Write-Host ("Uploaded: public_html/" + ($RelativePath -replace '\\', '/'))
