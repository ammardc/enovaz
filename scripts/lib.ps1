# Fungsi bersama untuk setup/backup/restore E-Novaz (Windows PowerShell 5.1+).

$ErrorActionPreference = 'Stop'
$script:Root = Split-Path -Parent $PSScriptRoot

function Read-DotEnv {
    param([string]$Path = (Join-Path $script:Root '.env'))
    $map = @{}
    if (-not (Test-Path $Path)) { return $map }
    foreach ($line in Get-Content -LiteralPath $Path -Encoding UTF8) {
        if ($line -match '^\s*#' -or $line -notmatch '=') { continue }
        $k, $v = $line -split '=', 2
        $v = $v.Trim()
        if ($v.Length -ge 2 -and $v.StartsWith('"') -and $v.EndsWith('"')) { $v = $v.Substring(1, $v.Length - 2) }
        $map[$k.Trim()] = $v
    }
    return $map
}

function Set-DotEnvValue {
    param([string]$Key, [string]$Value, [string]$Path = (Join-Path $script:Root '.env'))
    $lines = @(Get-Content -LiteralPath $Path -Encoding UTF8)
    $found = $false
    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($lines[$i] -match "^\s*$([regex]::Escape($Key))=") { $lines[$i] = "$Key=$Value"; $found = $true }
    }
    if (-not $found) { $lines += "$Key=$Value" }
    [System.IO.File]::WriteAllLines($Path, [string[]]$lines, (New-Object System.Text.UTF8Encoding($false)))
}

function New-Secret {
    param([int]$Length = 24)
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789'.ToCharArray()
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    $bytes = New-Object byte[] $Length
    $rng.GetBytes($bytes)
    $s = -join ($bytes | ForEach-Object { $chars[$_ % $chars.Length] })
    # Pastikan ada huruf dan angka (aturan password app)
    return $s.Substring(0, $Length - 2) + 'a7'
}

function Find-Xampp {
    param([string]$Hint = '')
    foreach ($p in @($Hint, 'C:\xampp', 'D:\xampp', 'E:\xampp')) {
        if ($p -and (Test-Path (Join-Path $p 'php\php.exe'))) { return $p }
    }
    throw 'XAMPP tidak ditemukan. Jalankan dengan -XamppPath "D:\lokasi\xampp".'
}

function Get-XamppDbPort {
    param([string]$Xampp)
    $ini = Join-Path $Xampp 'mysql\bin\my.ini'
    if (Test-Path $ini) {
        $inMysqld = $false
        foreach ($line in Get-Content -LiteralPath $ini) {
            if ($line -match '^\s*\[(.+)\]') { $inMysqld = ($Matches[1] -eq 'mysqld') }
            elseif ($inMysqld -and $line -match '^\s*port\s*=\s*(\d+)') { return [int]$Matches[1] }
        }
    }
    return 3306
}

function Test-Port {
    param([string]$HostName, [int]$Port)
    try {
        $c = New-Object System.Net.Sockets.TcpClient
        $iar = $c.BeginConnect($HostName, $Port, $null, $null)
        $ok = $iar.AsyncWaitHandle.WaitOne(1500) -and $c.Connected
        $c.Close()
        return $ok
    } catch { return $false }
}

# Jalankan mysql/mysqldump tanpa menaruh password di command line (pakai MYSQL_PWD).
function Invoke-MySql {
    param([string]$Exe, [string]$User, [string]$Password, [string]$HostName, [int]$Port, [string[]]$MySqlArgs)
    $old = $env:MYSQL_PWD
    $env:MYSQL_PWD = $Password
    try {
        $all = @("--user=$User", "--host=$HostName", "--port=$Port") + $MySqlArgs
        & $Exe @all
        if ($LASTEXITCODE -ne 0) { throw "$([IO.Path]::GetFileName($Exe)) gagal (kode $LASTEXITCODE)." }
    } finally {
        $env:MYSQL_PWD = $old
    }
}

function Write-Step { param([string]$Text) Write-Host "`n==> $Text" -ForegroundColor Cyan }
function Write-Ok { param([string]$Text) Write-Host "    $Text" -ForegroundColor Green }
function Write-Warn2 { param([string]$Text) Write-Host "    $Text" -ForegroundColor Yellow }
