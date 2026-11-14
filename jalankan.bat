@echo off
setlocal EnableDelayedExpansion
cd /d "%~dp0"
title E-Novaz

if not exist ".env" (
  echo .env belum ada. Jalankan setup.bat dulu.
  pause
  exit /b 1
)

set "PHP=php"
if exist "tools\php-path.txt" set /p PHP=<tools\php-path.txt
if not exist "%PHP%" if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"

set "PORT=8000"
if not "%~1"=="" set "PORT=%~1"

rem Database mandiri (setup.bat -OwnDb): nyalakan bila belum jalan
set "DBNOTE=Pastikan MySQL di XAMPP Control Panel menyala."
if exist ".mariadb\data\my.ini" (
  set "DBNOTE=Database mandiri berjalan di jendela E-Novaz database (diperkecil)."
  set "DBPORT=3308"
  for /f "tokens=2 delims==" %%p in ('findstr /b "DB_PORT=" .env') do set "DBPORT=%%p"
  netstat -ano | findstr /r /c:"127.0.0.1:!DBPORT! .*LISTENING" >nul
  if errorlevel 1 (
    set "MYSQLD=C:\xampp\mysql\bin\mysqld.exe"
    if exist "tools\mysqld-path.txt" set /p MYSQLD=<tools\mysqld-path.txt
    echo  Menyalakan database mandiri di port !DBPORT! ...
    start "E-Novaz database" /min "!MYSQLD!" "--defaults-file=%~dp0.mariadb\data\my.ini" --console
    timeout /t 6 /nobreak >nul
  )
)

rem Scheduler: melepas tagihan kedaluwarsa tiap menit (jendela terpisah, diperkecil)
start "E-Novaz scheduler" /min "%PHP%" artisan schedule:work

echo.
echo  E-Novaz berjalan
echo    Laptop ini : http://localhost:%PORT%
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4"') do (
  set "IP=%%a"
  set "IP=!IP: =!"
  echo    Jaringan   : http://!IP!:%PORT%
)
echo.
echo  !DBNOTE!
echo  Bila Windows Firewall bertanya, izinkan untuk jaringan Private.
echo  Tutup jendela ini (dan jendela scheduler/database) untuk berhenti.
echo.
"%PHP%" artisan serve --host=0.0.0.0 --port=%PORT%
