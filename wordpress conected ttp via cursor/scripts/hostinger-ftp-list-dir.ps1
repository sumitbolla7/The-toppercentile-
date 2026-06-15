# List remote FTP directories (active mode) to find the path that matches SFTP remotePath.
param(
  [string]$RemoteDir = "/",
  [string]$LocalRoot = ""
)
$scriptDir = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
if (-not $LocalRoot) { $LocalRoot = (Resolve-Path (Join-Path $scriptDir "..")).Path }
. (Join-Path $scriptDir "hostinger-ftp-lib.ps1")
$j = Get-HostingerSftpJson -LocalRoot $LocalRoot
$cred = Get-HostingerFtpCredential -Json $j
$h = [string]$j.host
$tail = ($RemoteDir -replace '\\', '/').Trim('/')
$rel = if ($tail) { (ConvertTo-FtpEscapedPath -RelativePath $tail) + '/' } else { '' }
$uri = "ftp://$h/$rel"
Write-Host "LIST $uri"
$req = [System.Net.FtpWebRequest]::Create($uri)
$req.Credentials = $cred
$req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
$req.UseBinary = $true
$req.UsePassive = $false
$req.KeepAlive = $false
$resp = $req.GetResponse()
$rdr = New-Object System.IO.StreamReader($resp.GetResponseStream())
Write-Host $rdr.ReadToEnd()
$rdr.Close()
$resp.Close()
