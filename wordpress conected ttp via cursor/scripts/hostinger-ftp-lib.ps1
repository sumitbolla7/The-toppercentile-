# Shared FTP helpers for Hostinger
# KEY FACTS learned from connection test:
#   - PASSIVE mode works, ACTIVE (port 20) is blocked by firewall
#   - FTP root "/" is already the WordPress root (wp-content is directly inside)
#   - NO "public_html/" prefix needed

function Get-HostingerSftpJson {
  param([string]$LocalRoot)
  $cfgPath = Join-Path $LocalRoot ".vscode\sftp.json"
  if (-not (Test-Path -LiteralPath $cfgPath)) { throw "Missing $cfgPath" }
  $raw = Get-Content $cfgPath -Raw -Encoding UTF8 | ConvertFrom-Json
  # sftp.json is an array; grab first entry with a host
  $j = $null
  if ($raw -is [System.Array]) {
    foreach ($entry in $raw) {
      if ($entry.host -and $entry.username) { $j = $entry; break }
    }
  } else {
    $j = $raw
  }
  if (-not $j -or -not $j.host -or -not $j.username) {
    throw "sftp.json must include host and username"
  }
  return $j
}

function Get-HostingerFtpCredential {
  param($Json)
  $u = $Json.ftpUsername
  if ([string]::IsNullOrWhiteSpace($u)) { $u = [string]$Json.username }
  $p = $Json.ftpPassword
  if ([string]::IsNullOrWhiteSpace($p)) { $p = [string]$Json.password }
  return New-Object System.Net.NetworkCredential($u, $p)
}

function ConvertTo-FtpEscapedPath {
  param([string]$RelativePath)
  $RelativePath = $RelativePath -replace '\\', '/'
  $parts = $RelativePath.Split([char[]]@('/'), [StringSplitOptions]::RemoveEmptyEntries)
  return ( $parts | ForEach-Object { [System.Uri]::EscapeDataString($_) } ) -join '/'
}

function Invoke-FtpMakeDirectory {
  param(
    [string]$HostName,
    [System.Net.NetworkCredential]$Credential,
    [string]$EscapedPathUnderHost
  )
  $uri = "ftp://$HostName/$EscapedPathUnderHost"
  try {
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Credentials = $Credential
    $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
    $req.UseBinary = $true
    $req.UsePassive = $true
    $req.KeepAlive = $false
    $resp = $req.GetResponse()
    $resp.Close()
  }
  catch {
    # 550 "already exists" is normal - ignore silently
  }
}

function Ensure-FtpRemoteDirs {
  param(
    [string]$HostName,
    [System.Net.NetworkCredential]$Credential,
    [string]$RemoteRelPath
  )
  $RemoteRelPath = $RemoteRelPath -replace '\\', '/'
  $dirOnly = Split-Path $RemoteRelPath -Parent
  if ([string]::IsNullOrWhiteSpace($dirOnly)) { return }
  # FTP root is already the WP root — no public_html prefix
  $segments = $dirOnly.Split([char[]]@('/'), [StringSplitOptions]::RemoveEmptyEntries)
  $built = New-Object System.Collections.Generic.List[string]
  foreach ($seg in $segments) {
    [void]$built.Add($seg)
    $escaped = ( $built | ForEach-Object { [System.Uri]::EscapeDataString($_) } ) -join '/'
    Invoke-FtpMakeDirectory -HostName $HostName -Credential $Credential -EscapedPathUnderHost $escaped
  }
}

function Send-FtpFileActive {
  # Kept for backward compat — now uses PASSIVE mode internally
  param(
    [string]$HostName,
    [System.Net.NetworkCredential]$Credential,
    [string]$LocalFullPath,
    [string]$RemoteRelativeFromPublicHtml
  )
  # Strip "public_html/" prefix if it was added by caller
  $remoteRel = $RemoteRelativeFromPublicHtml -replace '\\', '/'
  $remoteRel = $remoteRel -replace '^public_html/', ''

  Ensure-FtpRemoteDirs -HostName $HostName -Credential $Credential -RemoteRelPath $remoteRel

  $escaped = ConvertTo-FtpEscapedPath -RelativePath $remoteRel
  $uri     = "ftp://$HostName/$escaped"
  $bytes   = [System.IO.File]::ReadAllBytes($LocalFullPath)

  $req = [System.Net.FtpWebRequest]::Create($uri)
  $req.Credentials   = $Credential
  $req.Method        = [System.Net.WebRequestMethods+Ftp]::UploadFile
  $req.UseBinary     = $true
  $req.UsePassive    = $true
  $req.KeepAlive     = $false
  $req.ContentLength = $bytes.Length

  $stream = $req.GetRequestStream()
  $stream.Write($bytes, 0, $bytes.Length)
  $stream.Close()
  $resp = $req.GetResponse()
  $resp.Close()
}

function Receive-FtpFileActive {
  param(
    [string]$HostName,
    [System.Net.NetworkCredential]$Credential,
    [string]$RemoteRelativeFromPublicHtml,
    [string]$LocalFullPath
  )
  $remoteRel = $RemoteRelativeFromPublicHtml -replace '\\', '/'
  $remoteRel = $remoteRel -replace '^public_html/', ''
  $escaped   = ConvertTo-FtpEscapedPath -RelativePath $remoteRel
  $uri       = "ftp://$HostName/$escaped"

  $req = [System.Net.FtpWebRequest]::Create($uri)
  $req.Credentials = $Credential
  $req.Method      = [System.Net.WebRequestMethods+Ftp]::DownloadFile
  $req.UseBinary   = $true
  $req.UsePassive  = $true
  $req.KeepAlive   = $false
  $resp = $req.GetResponse()
  try {
    $stream = $resp.GetResponseStream()
    $ms     = New-Object System.IO.MemoryStream
    $stream.CopyTo($ms)
    $bytes  = $ms.ToArray()
    [System.IO.File]::WriteAllBytes($LocalFullPath, $bytes)
  }
  finally {
    $resp.Close()
  }
}
