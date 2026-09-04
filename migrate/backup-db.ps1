# ============================================================
# SIMAPAN - Backup Database (MySQL) untuk migrasi ke PC lain
# Jalankan: powershell -ExecutionPolicy Bypass -File backup-db.ps1
# Prerek: MySQL server jalan di 127.0.0.1:3306 (creds dari .env)
# ============================================================
$ErrorActionPreference = 'Stop'

function Get-EnvVal($key, $file) {
    if (!(Test-Path $file)) { return '' }
    foreach ($line in Get-Content $file) {
        if ($line -match "^\s*$key\s*=(.*)$") {
            $v = $Matches[1].Trim()
            $v = $v.Trim('"')
            $v = $v.Trim("'")
            return $v
        }
    }
    return ''
}

$root = Split-Path (Split-Path $MyInvocation.MyCommand.Definition -Parent) -Parent
$envFile = Join-Path $root '.env'

$dbDatabase = Get-EnvVal 'DB_DATABASE' $envFile
$dbUser     = Get-EnvVal 'DB_USERNAME' $envFile
$dbPassword = Get-EnvVal 'DB_PASSWORD' $envFile
$dbHost     = Get-EnvVal 'DB_HOST' $envFile
$dbPort     = Get-EnvVal 'DB_PORT' $envFile

if ($dbDatabase -eq '') { $dbDatabase = 'simapan_db' }
if ($dbUser -eq '')     { $dbUser = 'root' }
if ($dbHost -eq '')     { $dbHost = '127.0.0.1' }
if ($dbPort -eq '')     { $dbPort = '3306' }

# Lokalize mysqldump (PATH or Laragon biasa)
$dumpBin = (Get-Command 'mysqldump' -ErrorAction SilentlyContinue)
$mysqlDump = $dumpBin.Source
if ($mysqlDump -eq '' -or $null -eq $mysqlDump) {
    $candidates = @(
        'C:\laragon\bin\mysql\mysql-*',
        'C:\laragon\bin\mysql\*',
        'C:\Program Files\MySQL\*'
    )
    foreach ($pat in $candidates) {
        $found = Get-ChildItem $pat -Recurse -Filter 'mysqldump.exe' -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($found) { $mysqlDump = $found.FullName; break }
    }
}
if (!$mysqlDump -or $mysqlDump -eq '') {
    Write-Host '[ERROR] mysqldump tidak ditemukan. Install MySQL atau memindahkan PATH.' -ForegroundColor Red
    exit 1
}

$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$outDir = Split-Path $MyInvocation.MyCommand.Definition -Parent
$outFile = Join-Path $outDir "simapan_db_backup_$stamp.sql"

Write-Host "[INFO] Dumping $dbDatabase -> $outFile" -ForegroundColor Cyan

$args = @("-h", $dbHost, "-P", $dbPort, "-u", $dbUser, "--single-transaction", "--routines", "--triggers", "--default-character-set=utf8mb4")
if ($dbPassword -ne '') { $args += "-p$dbPassword" }
$args += $dbDatabase

$errFile = Join-Path $outDir 'mysqldump.err'
& $mysqlDump @args 2>$errFile | Set-Content -Path $outFile -Encoding utf8

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Backup gagal (exit $LASTEXITCODE). Cek $errFile" -ForegroundColor Red
    Get-Content $errFile
    exit 1
}

$size = (Get-Item $outFile).Length
Write-Host "[OK] Backup: $outFile ($size bytes)" -ForegroundColor Green

# Verifikasi minimal: file tidak kosong + ada CREATE TABLE
$content = Get-Content $outFile -Raw
if ($content -match 'CREATE TABLE' -or $size -gt 100000) {
    Write-Host "[OK] Verifikasi backup: kempleten (contains schema/data)." -ForegroundColor Green
} else {
    Write-Host "[WARN] Backup bisa jadi kosong/kuranc - checked manual sebelum migrasi." -ForegroundColor Yellow
}

Remove-Item (Join-Path $outDir 'mysqldump.err') -ErrorAction SilentlyContinue
Write-Host ""
Write-Host "Copiar file ini + proyek ke PC lain, lalu jalankan pindah-ke-pc.ps1" -ForegroundColor Cyan