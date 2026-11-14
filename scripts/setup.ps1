# Setup E-Novaz di laptop Windows + XAMPP. Aman dijalankan berulang.
#   setup.bat                         -> setup biasa (database baru dari seeder)
#   setup.bat -Dump _untuk-tim\db.sql -> pakai dump database dari tim (butuh .env yang sama)
#   setup.bat -XamppPath D:\xampp     -> bila XAMPP tidak di C:\xampp
#   setup.bat -OwnDb                  -> database mandiri di folder .mariadb (port 3308),
#                                        tanpa MySQL XAMPP dan tanpa password root XAMPP
param(
    [string]$XamppPath = '',
    [string]$Dump = '',
    [int]$DbPort = 0,
    [string]$DbHost = '127.0.0.1',
    [switch]$OwnDb,
    [switch]$SkipPhpIni,
    [switch]$SkipBuild
)
. (Join-Path $PSScriptRoot 'lib.ps1')
Set-Location $script:Root

Write-Host 'E-Novaz - setup' -ForegroundColor White

# 1. PHP dari XAMPP ---------------------------------------------------------
Write-Step 'Mencari XAMPP dan PHP'
$xampp = Find-Xampp $XamppPath
$php = Join-Path $xampp 'php\php.exe'
$phpVer = [version]((& $php -r 'echo PHP_VERSION;') -replace '[^0-9.].*$', '')
if ($phpVer -lt [version]'8.2') { throw "PHP $phpVer terlalu lama. Butuh PHP 8.2+ (XAMPP 8.2 ke atas)." }
New-Item -ItemType Directory -Force -Path (Join-Path $script:Root 'tools') | Out-Null
Set-Content -LiteralPath (Join-Path $script:Root 'tools\php-path.txt') -Value $php -Encoding ASCII
Write-Ok "XAMPP: $xampp (PHP $phpVer)"

# 2. Ekstensi PHP -------------------------------------------------------------
$need = 'gd', 'sodium', 'zip', 'fileinfo', 'mbstring', 'openssl', 'pdo_mysql', 'curl'
$loaded = (& $php -m) | ForEach-Object { $_.Trim().ToLower() }
$missing = $need | Where-Object { $loaded -notcontains $_ }
if ($missing -and -not $SkipPhpIni) {
    Write-Step "Mengaktifkan ekstensi PHP: $($missing -join ', ')"
    $ini = Join-Path $xampp 'php\php.ini'
    Copy-Item -LiteralPath $ini -Destination "$ini.bak-enovaz-$(Get-Date -Format yyyyMMddHHmmss)"
    $text = Get-Content -LiteralPath $ini -Raw
    foreach ($ext in $missing) { $text = $text -replace "(?m)^;\s*extension=$ext\s*$", "extension=$ext" }
    $text = $text -replace '(?m)^expose_php\s*=\s*On', 'expose_php=Off'   # sembunyikan versi PHP
    [IO.File]::WriteAllText($ini, $text)
    $loaded = (& $php -m) | ForEach-Object { $_.Trim().ToLower() }
    $still = $need | Where-Object { $loaded -notcontains $_ }
    if ($still) { throw "Ekstensi belum aktif: $($still -join ', '). Aktifkan manual di $ini" }
    Write-Ok "Aktif. Cadangan php.ini disimpan di folder yang sama."
} elseif ($missing) {
    throw "Ekstensi PHP belum aktif: $($missing -join ', ')"
} else {
    Write-Ok 'Semua ekstensi PHP yang dibutuhkan sudah aktif.'
}

# 3. Composer & Node -------------------------------------------------------
Write-Step 'Memasang dependensi PHP (Composer)'
$composer = Get-Command composer -ErrorAction SilentlyContinue
if ($composer) {
    & composer install --no-interaction --prefer-dist
} else {
    $phar = Join-Path $script:Root 'tools\composer.phar'
    if (-not (Test-Path $phar)) {
        Write-Ok 'Composer belum ada, mengunduh composer.phar ...'
        Invoke-WebRequest -UseBasicParsing -Uri 'https://getcomposer.org/download/latest-stable/composer.phar' -OutFile $phar
    }
    & $php $phar install --no-interaction --prefer-dist
}
if ($LASTEXITCODE -ne 0) { throw 'composer install gagal.' }

if (-not $SkipBuild) {
    Write-Step 'Membangun tampilan (Node.js)'
    if (-not (Get-Command npm -ErrorAction SilentlyContinue)) { throw 'Node.js belum terpasang. Unduh di https://nodejs.org (versi LTS), lalu jalankan setup lagi.' }
    & npm install --no-audit --no-fund
    if ($LASTEXITCODE -ne 0) { throw 'npm install gagal.' }
    & npm run build
    if ($LASTEXITCODE -ne 0) { throw 'npm run build gagal.' }
}

# 4. File .env ---------------------------------------------------------------
Write-Step 'Menyiapkan .env'
$envPath = Join-Path $script:Root '.env'
$newEnv = -not (Test-Path $envPath)
if ($newEnv) {
    if ($Dump) { throw 'Dump database butuh .env yang sama dengan pembuat dump (kunci enkripsi). Taruh .env dari tim di folder ini dulu.' }
    Copy-Item (Join-Path $script:Root '.env.example') $envPath
    Set-DotEnvValue 'APP_ENV' 'production'
    Set-DotEnvValue 'APP_DEBUG' 'false'
    Set-DotEnvValue 'APP_URL' 'http://localhost:8000'
    Set-DotEnvValue 'LOG_LEVEL' 'warning'
    Set-DotEnvValue 'DB_PASSWORD' (New-Secret 24)
    Set-DotEnvValue 'DB_MIGRATOR_PASSWORD' (New-Secret 24)
    Set-DotEnvValue 'ADMIN_PASSWORD' (New-Secret 20)
    & $php artisan key:generate --force --no-ansi | Out-Null
    $ck = (& $php artisan enovaz:credential-key --no-ansi | Select-String -Pattern 'CREDENTIAL_KEY=(\S+)').Matches[0].Groups[1].Value
    Set-DotEnvValue 'CREDENTIAL_KEY' $ck
    Write-Ok '.env baru dibuat dengan kunci dan password acak.'
} else {
    $e = Read-DotEnv
    if (-not $e['DB_MIGRATOR_USERNAME']) { Set-DotEnvValue 'DB_MIGRATOR_USERNAME' 'enovaz_migrator' }
    if (-not $e['DB_MIGRATOR_PASSWORD']) { Set-DotEnvValue 'DB_MIGRATOR_PASSWORD' (New-Secret 24) }
    if (-not $e['DB_PASSWORD']) { Set-DotEnvValue 'DB_PASSWORD' (New-Secret 24) }
    if (-not $e['APP_KEY']) { & $php artisan key:generate --force --no-ansi | Out-Null }
    Write-Ok '.env sudah ada, dipakai apa adanya (kunci tidak diganti).'
}

# Port MariaDB: database mandiri = 3308, selain itu mengikuti XAMPP di laptop ini
$port = if ($DbPort -gt 0) { $DbPort } elseif ($OwnDb) { 3308 } else { Get-XamppDbPort $xampp }
Set-DotEnvValue 'DB_HOST' $DbHost
Set-DotEnvValue 'DB_PORT' "$port"
$e = Read-DotEnv

$rootPw = $env:ENOVAZ_DB_ROOT_PASSWORD
if ($OwnDb) {
    # Server MariaDB terpisah milik project: data di .mariadb\data, hanya 127.0.0.1, root password acak di .env
    Write-Step "Menyiapkan database mandiri (.mariadb, port $port)"
    $bin = Join-Path $xampp 'mysql\bin'
    $dataDir = Join-Path $script:Root '.mariadb\data'
    $myIni = Join-Path $dataDir 'my.ini'
    Set-Content -LiteralPath (Join-Path $script:Root 'tools\mysqld-path.txt') -Value (Join-Path $bin 'mysqld.exe') -Encoding ASCII
    if (-not (Test-Path $myIni)) {
        $rootPw = if ($e['DB_ROOT_PASSWORD']) { $e['DB_ROOT_PASSWORD'] } else { New-Secret 24 }
        Set-DotEnvValue 'DB_ROOT_PASSWORD' $rootPw
        New-Item -ItemType Directory -Force -Path (Split-Path $dataDir) | Out-Null
        & (Join-Path $bin 'mysql_install_db.exe') "--datadir=$dataDir" "--port=$port" "--password=$rootPw"
        if ($LASTEXITCODE -ne 0) { throw 'Gagal membuat database mandiri (mysql_install_db).' }
        # Hanya terima koneksi dari laptop ini
        $ini = Get-Content -LiteralPath $myIni -Raw
        if ($ini -notmatch 'bind-address') { $ini = $ini -replace '\[mysqld\]', "[mysqld]`r`nbind-address=127.0.0.1" }
        [IO.File]::WriteAllText($myIni, $ini)
        $ownDbNew = $true
        Write-Ok 'Database mandiri dibuat (root password acak tersimpan di .env: DB_ROOT_PASSWORD).'
    } else {
        $rootPw = $e['DB_ROOT_PASSWORD']
        if (-not $rootPw) { throw 'Folder .mariadb ada tapi DB_ROOT_PASSWORD kosong di .env.' }
    }
    if (-not (Test-Port $DbHost $port)) {
        Start-Process -FilePath (Join-Path $bin 'mysqld.exe') -ArgumentList "--defaults-file=`"$myIni`"" -WindowStyle Hidden
        for ($i = 0; $i -lt 30 -and -not (Test-Port $DbHost $port); $i++) { Start-Sleep -Seconds 1 }
    }
    if ($ownDbNew) {
        # Akun root berbasis nama komputer tidak diperlukan; sisakan root lokal saja.
        Invoke-MySql -Exe (Join-Path $bin 'mysql.exe') -User 'root' -Password $rootPw -HostName $DbHost -Port $port `
            -MySqlArgs @('-e', "DELETE FROM mysql.global_priv WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1'); DELETE FROM mysql.global_priv WHERE User=''; FLUSH PRIVILEGES;")
    }
}

# 5. Database ----------------------------------------------------------------
Write-Step "Menyiapkan database $($e['DB_DATABASE']) di ${DbHost}:$port"
if (-not (Test-Port $DbHost $port)) { throw "MariaDB tidak bisa dihubungi di ${DbHost}:$port. Nyalakan MySQL di XAMPP Control Panel (atau pakai setup.bat -OwnDb), lalu jalankan setup lagi." }

if ($null -eq $rootPw) {
    $sec = Read-Host 'Password root MariaDB XAMPP (Enter bila kosong)' -AsSecureString
    $rootPw = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec))
}
$mysql = Join-Path $xampp 'mysql\bin\mysql.exe'
$db = $e['DB_DATABASE']
$sqlFile = Join-Path $script:Root 'storage\framework\setup-db.sql'
$q = { param($s) $s.Replace('\', '\\').Replace("'", "\'") }
@"
CREATE DATABASE IF NOT EXISTS ``$db`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$($e['DB_MIGRATOR_USERNAME'])'@'localhost';
ALTER USER '$($e['DB_MIGRATOR_USERNAME'])'@'localhost' IDENTIFIED BY '$(& $q $e['DB_MIGRATOR_PASSWORD'])';
GRANT ALL PRIVILEGES ON ``$db``.* TO '$($e['DB_MIGRATOR_USERNAME'])'@'localhost';
CREATE USER IF NOT EXISTS '$($e['DB_USERNAME'])'@'localhost';
ALTER USER '$($e['DB_USERNAME'])'@'localhost' IDENTIFIED BY '$(& $q $e['DB_PASSWORD'])';
FLUSH PRIVILEGES;
"@ | Set-Content -LiteralPath $sqlFile -Encoding UTF8
try {
    Invoke-MySql -Exe $mysql -User 'root' -Password $rootPw -HostName $DbHost -Port $port -MySqlArgs @('-e', "source $($sqlFile -replace '\\', '/')")
} finally {
    Remove-Item -LiteralPath $sqlFile -ErrorAction SilentlyContinue   # berisi password, jangan ditinggal
}
Write-Ok 'Database dan dua akun terbatas siap (enovaz_app untuk aplikasi, enovaz_migrator untuk migrasi/backup).'

if ($Dump) {
    Write-Step "Memulihkan dump $Dump"
    & (Join-Path $PSScriptRoot 'restore-db.ps1') -File $Dump -XamppPath $xampp -Yes
}

# 6. Migrasi & seed (pakai akun migrator) -----------------------------------
Write-Step 'Migrasi dan data awal'
$env:DB_USERNAME = $e['DB_MIGRATOR_USERNAME']
$env:DB_PASSWORD = $e['DB_MIGRATOR_PASSWORD']
try {
    & $php artisan migrate --force --no-ansi
    if ($LASTEXITCODE -ne 0) { throw 'Migrasi gagal.' }
    & $php artisan db:seed --force --no-ansi
    if ($LASTEXITCODE -ne 0) { throw 'Seeder gagal.' }
} finally {
    Remove-Item Env:DB_USERNAME, Env:DB_PASSWORD -ErrorAction SilentlyContinue
}

# 7. Hak akses akun aplikasi (per tabel, tanpa DDL) ---------------------------
Write-Step 'Memasang hak akses enovaz_app (GRANT per tabel)'
$grants = (Get-Content -LiteralPath (Join-Path $script:Root 'database\sql\grants.sql')) |
    Where-Object { $_ -match '^\s*(GRANT|FLUSH)' } |
    ForEach-Object { $_ -replace 'enovaz_db\.', "``$db``." -replace "'enovaz_app'", "'$($e['DB_USERNAME'])'" }
Set-Content -LiteralPath $sqlFile -Value $grants -Encoding UTF8
try {
    Invoke-MySql -Exe $mysql -User 'root' -Password $rootPw -HostName $DbHost -Port $port -MySqlArgs @('-e', "source $($sqlFile -replace '\\', '/')")
} finally {
    Remove-Item -LiteralPath $sqlFile -ErrorAction SilentlyContinue
}

& $php artisan storage:link --no-ansi 2>$null | Out-Null
& $php artisan optimize:clear --no-ansi | Out-Null

# 8. Ringkasan ---------------------------------------------------------------
$e = Read-DotEnv
Write-Host "`nSetup selesai." -ForegroundColor Green
Write-Host '  Jalankan     : jalankan.bat'
Write-Host '  Alamat       : http://localhost:8000'
Write-Host "  Admin        : $($e['ADMIN_USERNAME'])"
if ($newEnv) {
    Write-Host "  Password     : $($e['ADMIN_PASSWORD'])   (catat sekarang, juga ada di .env)" -ForegroundColor Yellow
} else {
    Write-Host '  Password     : lihat ADMIN_PASSWORD di .env'
}
Write-Host '  Login admin pertama kali akan meminta setel 2FA (scan QR dengan aplikasi authenticator).'
Write-Host '  QRIS         :' $(if ($e['QRIS_ENABLED'] -eq 'true') { 'aktif' } else { 'maintenance (saldo pembeli diisi admin)' })
