@echo off
chcp 1251
title Добавление PHP в PATH

echo ========================================
echo ДОБАВЛЕНИЕ PHP В PATH
echo ========================================
echo.
echo Этот скрипт добавит C:\PHP\ в системную переменную PATH
echo Требуются права администратора!
echo.
pause

REM Проверка прав администратора
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Требуются права администратора!
    echo Запустите этот файл от имени администратора (ПКМ ^> Запуск от имени администратора)
    pause
    exit /b 1
)

REM Проверка существования PHP
if not exist "C:\PHP\php.exe" (
    echo ❌ PHP не найден в C:\PHP\php.exe
    echo.
    echo Установите PHP:
    echo 1. Скачайте с https://windows.php.net/download/
    echo 2. Распакуйте в C:\PHP\
    echo 3. Запустите этот скрипт снова
    pause
    exit /b 1
)

echo ✅ PHP найден в C:\PHP\
echo.
echo Добавляю C:\PHP в PATH...

REM Добавление в системный PATH
setx /M PATH "%PATH%;C:\PHP" >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ PHP успешно добавлен в PATH!
    echo.
    echo ⚠️  ВАЖНО: Перезапустите командную строку/PowerShell
    echo    чтобы изменения вступили в силу!
) else (
    echo ❌ Ошибка при добавлении в PATH
    echo Попробуйте добавить вручную через интерфейс Windows
)

echo.
pause

