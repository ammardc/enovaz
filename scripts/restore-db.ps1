# Pulihkan database E-Novaz dari file .sql hasil backup-db.ps1.
# MENIMPA isi database. Contoh:
#   powershell -ExecutionPolicy Bypass -File scripts\restore-db.ps1 -File backups\enovaz_db-20261112-210000.sql
param(
    [Parameter(Mandatory = $true)][string]$File,
    [string]$XamppPath = '',
    [string]$Database = '',
    [switch]$Yes
)
. (Join-Path $PSScriptRoot 'lib.ps1')

$envMap = Read-DotEnv
foreach ($k in 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_MIGRATOR_USERNAME', 'DB_MIGRATOR_PASSWORD') {
    if (-not $envMap[$k]) { throw "Nilai $k kosong di .env. Jalankan setup.bat dulu." }
}
if (-not (Test-Path -LiteralPath $File)) { throw "File tidak ditemukan: $File" }
if (-not $Database) { $Database = $envMap['DB_DATABASE'] }

$xampp = Find-Xampp $XamppPath
$mysql = Join-Path $xampp 'mysql\bin\mysql.exe'

Write-Host "Database '$Database' akan DITIMPA dengan isi $File." -ForegroundColor Yellow
if (-not $Yes) {
    $answer = Read-Host 'Ketik YA untuk lanjut'
    if ($answer -cne 'YA') { Write-Host 'Dibatalkan.'; exit 1 }
}

# Cadangan otomatis sebelum menimpa, supaya restore yang salah bisa dibatalkan.
if ($Database -eq $envMap['DB_DATABASE']) {
    Write-Step 'Backup isi sekarang dulu (jaga-jaga)'
    & (Join-Path $PSScriptRoot 'backup-db.ps1') -XamppPath $xampp
}

Write-Step "Restore ke $Database"
Invoke-MySql -Exe $mysql -User $envMap['DB_MIGRATOR_USERNAME'] -Password $envMap['DB_MIGRATOR_PASSWORD'] `
    -HostName $envMap['DB_HOST'] -Port ([int]$envMap['DB_PORT']) `
    -MySqlArgs @('--default-character-set=utf8mb4', $Database, '-e', "source $(((Resolve-Path -LiteralPath $File).Path) -replace '\\', '/')")

Write-Ok 'Selesai. Jalankan setup.bat sekali lagi bila ada migrasi baru atau hak akses (GRANT) perlu dipasang ulang.'
