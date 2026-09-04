# ============================================================
# SIMAPAN - Setup & migrasi di PC lain (Laragon OR native)
# Jalankan di PC TUJUAN:  powershell -ExecutionPolicy Bypass -File migrate-to-pc.ps1
# Prerekwisit: PHP 8.2, Composer, Node >=20, MySQL 8 (running)
# ============================================================
$ErrorActionPreference = 'Stop'
$root = Split-Path (Split-Path $MyInvocation.MyCommand.Definition -Parent) -Parent

Write-Host "=== SIMAPAN Setup PC lain ===" -ForegroundColor Cyan
Write-Host "Root: $root"
Write-Host ""

# ---------- 1. Deteksi tool (PATH atau Laragon) ----------
function Find-Exe([string]$name) {
    $cmd = Get-Command $name -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    $cand = @("C:\laragon\bin\php\php-*\php.exe", "C:\laragon\bin\nodejs\node-v*\node.exe")
    foreach ($pat in $cand) {
        $f = Get-ChildItem $pat -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($f) { return $f.FullName }
    }
    return $null
}

$php = Find-Exe 'php'
$composer = Get-Command 'composer' -ErrorAction SilentlyContinue
$composerPath = $composer.Source
$node = Find-Exe 'node'
$npm = Get-Command 'npm' -ErrorAction SilentlyContinue
$npmPath = $npm.Source

if (!$php) { Write-Host '[ERROR] PHP tidak ditemukan (install PHP 8.2 / Laragon)' -ForegroundColor Red; exit 1 }
if (!$node) { Write-Host '[ERROR] Node.js tidak ditemukan' -ForegroundColor Red; exit 1 }

Write-Host "[OK] PHP : $php"
if ($composerPath) { Write-Host "[OK] Composer: $composerPath" } else { Write-Host "[WARN] Composer tidak di PATH - wajib install composer" }
Write-Host "[OK] Node: $node"
Write-Host ""

# ---------- 2. .env ----------
$envFile = Join-Path $root '.env'
if (!(Test-Path $envFile)) {
    Write-Host "[INFO] .env belum ada - copy dari .env.example lalu sesuaikan DB/admin."
    if (Test-Path (Join-Path $root '.env.example')) {
        Copy-Item (Join-Path $root '.env.example') $envFile
        Write-Host "[INFO] .env dibuat dari .env.example. Edit dulu DB creds & SIMAPAN_ADMIN_*"
        Write-Host "       Sebelum lanjut, isi .env lalu jalankan ulang skrip ini."
        exit 0
    } else {
        Write-Host '[ERROR] .env.example tidak ada. Siapkan .env manual.' -ForegroundColor Red
        exit 1
    }
}
# ---------- 3. APP_KEY ----------
Write-Host "=== APP_KEY ===" -ForegroundColor Cyan
$keyLine = (Get-Content $envFile | Select-String '^APP_KEY=' | Select-Object -First 1)
if ($null -eq $keyLine -or $keyLine.Line -match '^APP_KEY=\s*$') {
    & $php (Join-Path $root 'artisan') key:generate
    Write-Host "[INFO] APP_KEY baru dibuat. Session lama (dari PC asal) tidak valid - login ulang."
} else {
    Write-Host "[INFO] APP_KEY sudah ada - memakai kunci yang dicopy."
}

# ---------- 4. Install dependency ----------
Write-Host "=== Install dependencies (vendor) ===" -ForegroundColor Cyan
if ($composerPath) {
    & $composerPath install --prefer-dist --no-interaction
} else {
    Write-Host '[ERROR] Composer tidak tersedia' -ForegroundColor Red
    exit 1
}
if ($LASTEXITCODE -ne 0) { Write-Host '[ERROR] composer install gagal' -ForegroundColor Red; exit 1 }

Write-Host "=== Install npm & build assets ===" -ForegroundColor Cyan
Push-Location $root
if ($npmPath) {
    & $npmPath ci
    if ($LASTEXITCODE -ne 0) {
        Write-Host '[WARN] npm ci gagal - coba npm install' -ForegroundColor Yellow
        & $npmPath install
    }
    & $npmPath run build
    if ($LASTEXITCODE -ne 0) { Write-Host '[ERROR] npm run build gagal' -ForegroundColor Red; Pop-Location; exit 1 }
} else {
    Write-Host '[ERROR] npm tidak tersedia' -ForegroundColor Red
    Pop-Location; exit 1
}
Pop-Location
# ---------- 5. Database ----------
Write-Host "=== Database ===" -ForegroundColor Cyan
$dbName = ((Get-Content $envFile | Select-String '^DB_DATABASE=' | Select-Object -First 1).Line).Split('=')[1].Trim().Trim('"')
if ($dbName -eq '') { $dbName = 'simapan_db' }
Write-Host "DB tujuan: $dbName"

$restoreDump = Get-ChildItem (Split-Path $MyInvocation.MyCommand.Definition -Parent) -Filter 'simapan_db_backup_*.sql' -ErrorAction SilentlyContinue | Select-Object -First 1

$useDump = $false
if ($restoreDump) {
    $resp = Read-Host "Ditemukan dump '$($restoreDump.Name)'. Import data ini? (y/N)"
    if ($resp -match '^[yY]') { $useDump = $true }
}

if ($useDump) {
    Write-Host "[INFO] Import dump: $($restoreDump.FullName)"
    $mysql = Get-Command 'mysql' -ErrorAction SilentlyContinue
    $mysqlBin = $mysql.Source
    if (!$mysqlBin) {
        $mysqlBin = (Get-ChildItem 'C:\laragon\bin\mysql\mysql-*\bin\mysql.exe' -ErrorAction SilentlyContinue | Select-Object -First 1).FullName
    }
    if (!$mysqlBin) { Write-Host '[ERROR] mysql.exe tidak ditemukan' -ForegroundColor Red; exit 1 }
    $dbUser = (Get-Content $envFile | Select-String '^DB_USERNAME=' | Select-Object -First 1).Line.Split('=')[1].Trim().Trim('"')
    $dbPass = (Get-Content $envFile | Select-String '^DB_PASSWORD=' | Select-Object -First 1).Line.Split('=')[1].Trim().Trim('"')
    $env:MYSQL_PWD = $dbPass
    if ($dbPass -eq '') {
        & $mysqlBin -h 127.0.0.1 -u $dbUser $dbName -e "source $($restoreDump.FullName)"
    } else {
        & $mysqlBin -h 127.0.0.1 -u $dbUser "-p$dbPass" $dbName -e "source $($restoreDump.FullName)"
    }
    if ($LASTEXITCODE -ne 0) { Write-Host '[WARN] Import dump gagal - cek DB name & creds' -ForegroundColor Yellow }
    Write-Host "[OK] Selesai import dump. Skip migrate --seed (data sudah penuh)."
} else {
    Write-Host "[INFO] Migrasi schema + seed dummy..."
    & $php (Join-Path $root 'artisan') migrate --force
    if ($LASTEXITCODE -ne 0) { Write-Host '[ERROR] migrate gagal' -ForegroundColor Red; exit 1 }
    & $php (Join-Path $root 'artisan') db:seed --force
    Write-Host "[OK] Schema + seeder dummy selesai."
}

# ---------- 6. Storage link & cache ----------
& $php (Join-Path $root 'artisan') storage:link
& $php (Join-Path $root 'artisan') optimize:clear

Write-Host ""
Write-Host "=== SELESAI ===" -ForegroundColor Green
Write-Host "Menjalankan:  cd $root ; php artisan serve --port=8000"
Write-Host "Login:        http://127.0.0.1:8000/login"
Write-Host "  admin:      (SIMAPAN_ADMIN_EMAIL / SIMAPAN_ADMIN_PASSWORD di .env)"
Write-Host "  dev roles:  <role>@simapan.test / Simapan-Dev-2026 (hanya local)"
Write-Host "Catatan: bila memakai Laragon, VirtualHost -> public/  atau artisan serve"

