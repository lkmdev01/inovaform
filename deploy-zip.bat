@echo off
chcp 65001 >nul
echo.
echo  Iniciando geração do ZIP de deploy...
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0deploy-zip.ps1" %*
echo.
pause
