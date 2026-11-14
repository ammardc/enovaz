@echo off
cd /d "%~dp0"
if "%~1"=="" (echo Pakai: restore.bat backups\nama-file.sql & pause & exit /b 1)
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\restore-db.ps1" -File "%~1"
pause
