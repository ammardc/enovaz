# Backup database E-Novaz ke folder backups\ (tidak ikut ke GitHub).
# Pakai akun migrator dari .env, bukan root. Contoh:
#   powershell -ExecutionPolicy Bypass -File scripts\backup-db.ps1
param(
    [string]$XamppPath = '',
    [string]$OutDir = ''
)
. (Join-Path $PSScriptRoot 'lib.ps1')

$envMap = Read-DotEnv
foreach ($k in 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_MIGRATOR_USERNAME', 'DB_MIGRATOR_PASSWORD') {
    if (-not $envMap[$k]) { throw "Nilai $k kosong di .env. Jalankan setup.bat dulu." }
}

$xampp = Find-Xampp $XamppPath
$dump = Join-Path $xampp 'mysql\bin\mysqldump.exe'
if (-not $OutDir) { $OutDir = Join-Path $script:Root 'backups' }
New-Item -ItemType Directory -Force -Path $OutDir | Out-Null

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$file = Join-Path $OutDir "$($envMap['DB_DATABASE'])-$stamp.sql"

Write-Step "Backup $($envMap['DB_DATABASE']) -> $file"
# --single-transaction: salinan konsisten tanpa mengunci tabel InnoDB.
# Tanpa CREATE DATABASE, jadi bisa dipulihkan ke database dengan nama lain.
Invoke-MySql -Exe $dump -User $envMap['DB_MIGRATOR_USERNAME'] -Password $envMap['DB_MIGRATOR_PASSWORD'] `
    -HostName $envMap['DB_HOST'] -Port ([int]$envMap['DB_PORT']) `
    -MySqlArgs @('--single-transaction', '--routines', '--triggers', '--hex-blob', '--default-character-set=utf8mb4', '--no-tablespaces', "--result-file=$file", $envMap['DB_DATABASE'])

$size = [math]::Round((Get-Item $file).Length / 1KB, 1)
Write-Ok "Selesai ($size KB)."
Write-Warn2 'File ini berisi data pengguna (hash password, kredensial terenkripsi). Jangan diunggah ke GitHub.'
Write-Warn2 'Kredensial stok hanya bisa dibuka dengan CREDENTIAL_KEY yang sama di .env.'
