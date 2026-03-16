@echo off
chcp 1251 >nul
title Быстрый запуск сервера

REM Простой скрипт - проверяет только C:\PHP\ и PATH
if exist "C:\PHP\php.exe" (
    echo ✅ PHP найден в C:\PHP\
    start http://localhost:8000/backend/test_results.php
    "C:\PHP\php.exe" -S localhost:8000
) else (
    where php >nul 2>&1
    if %errorlevel% equ 0 (
        echo ✅ PHP найден в PATH
        start http://localhost:8000/backend/test_results.php
        php -S localhost:8000
    ) else (
        echo ❌ PHP не найден!
        echo Установите PHP в C:\PHP\ или добавьте в PATH
        pause
    )
)

