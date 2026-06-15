param([string]$LocalRoot = "")
$scriptDir = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
if (-not $LocalRoot) { $LocalRoot = (Resolve-Path (Join-Path $scriptDir "..")).Path }

. (Join-Path $scriptDir "hostinger-ftp-lib.ps1")

$j    = Get-HostingerSftpJson -LocalRoot $LocalRoot
$cred = Get-HostingerFtpCredential -Json $j
$h    = [string]$j.host

Write-Host "Host     : $h"
Write-Host "User     : $($cred.UserName)"
Write-Host "PassLen  : $($cred.Password.Length)"

# Try a basic LIST on the FTP root
$uri = "ftp://$h/"
Write-Host "Testing  : $uri"

try {
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Credentials = $cred
    $req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
    $req.UseBinary = $true
    $req.UsePassive = $true
    $req.KeepAlive = $false
    $req.ConnectionGroupName = "ftptest"
    $resp = $req.GetResponse()
    $rdr  = New-Object System.IO.StreamReader($resp.GetResponseStream())
    $listing = $rdr.ReadToEnd()
    $rdr.Close()
    $resp.Close()
    Write-Host "FTP LOGIN OK (passive). Listing:" -ForegroundColor Green
    Write-Host $listing
} catch {
    Write-Host "FTP PASSIVE failed: $_" -ForegroundColor Red
}

# Try active mode
try {
    $req2 = [System.Net.FtpWebRequest]::Create($uri)
    $req2.Credentials = $cred
    $req2.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
    $req2.UseBinary = $true
    $req2.UsePassive = $false
    $req2.KeepAlive = $false
    $resp2 = $req2.GetResponse()
    $rdr2  = New-Object System.IO.StreamReader($resp2.GetResponseStream())
    $listing2 = $rdr2.ReadToEnd()
    $rdr2.Close()
    $resp2.Close()
    Write-Host "FTP LOGIN OK (active). Listing:" -ForegroundColor Green
    Write-Host $listing2
} catch {
    Write-Host "FTP ACTIVE failed: $_" -ForegroundColor Red
}
