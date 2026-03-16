@echo off
chcp 1251 >nul
title Запуск PHP сервера для тестов

echo ========================================
echo Запуск PHP сервера для тестов
echo ========================================
echo.

REM Инициализация переменной для пути к PHP
set PHP_PATH=

REM Шаг 1: Проверка PHP в PATH
echo [1/4] Проверка PHP в PATH...
where php >nul 2>&1
if %errorlevel% equ 0 (
    echo     [OK] PHP найден в PATH
    set PHP_PATH=php
    goto found_php
)

REM Шаг 2: Проверка стандартных путей
echo [2/4] Поиск PHP в стандартных местах...

REM Список возможных путей к PHP
for %%i in (
    "C:\PHP\php.exe"
    "C:\php\php.exe"
    "C:\xampp\php\php.exe"
    "C:\wamp64\bin\php\php8.2\php.exe"
    "C:\wamp64\bin\php\php8.1\php.exe"
    "C:\wamp64\bin\php\php8.0\php.exe"
    "C:\OpenServer\modules\php\*\php.exe"
    "C:\Program Files\PHP\php.exe"
    "C:\Program Files (x86)\PHP\php.exe"
    "C:\laragon\bin\php\php-*\php.exe"
) do (
    if exist %%i (
        echo     [OK] PHP найден: %%i
        set PHP_PATH=%%i
        goto found_php
    )
)

REM Шаг 3: Поиск через реестр (для установленных версий)
echo [3/4] Поиск через системные пути...

REM Проверка переменной окружения PHP_HOME
if defined PHP_HOME (
    if exist "%PHP_HOME%\php.exe" (
        echo     [OK] PHP найден через PHP_HOME: %PHP_HOME%\php.exe
        set PHP_PATH=%PHP_HOME%\php.exe
        goto found_php
    )
)

REM Если ничего не найдено
echo [4/4] ❌ PHP не найден!
echo.
echo ========================================
echo РЕШЕНИЕ ПРОБЛЕМЫ:
echo ========================================
echo.
echo 1. Установите PHP с https://windows.php.net/download/
echo    Распакуйте в C:\PHP\
echo.
echo 2. Или используйте XAMPP: https://www.apachefriends.org/
echo    PHP будет в C:\xampp\php\
echo.
echo 3. Или добавьте PHP в PATH:
echo    - Win+X ^> Система ^> Дополнительные параметры
echo    - Переменные среды ^> Path ^> Изменить
echo    - Добавьте путь к PHP (например: C:\PHP)
echo.
echo 4. Если PHP уже установлен, укажите полный путь:
echo    "C:\PHP\php.exe -S localhost:8000"
echo.
echo ========================================
pause
exit /b 1

:found_php
echo [4/4] ✅ PHP найден!
echo.

REM Проверка версии PHP
echo Проверка версии PHP...
"%PHP_PATH%" -v 2>nul
if %errorlevel% neq 0 (
    echo ❌ Ошибка при запуске PHP!
    echo Проверьте правильность пути: %PHP_PATH%
    pause
    exit /b 1
)
echo.

REM Запуск сервера
echo ========================================
echo ЗАПУСК СЕРВЕРА
echo ========================================
echo.
echo Сервер будет доступен по адресу:
echo   http://localhost:8000
echo.
echo Страница тестов:
echo   http://localhost:8000/backend/test_results.php
echo.
echo Открываю браузер через 2 секунды...
timeout /t 2 /nobreak >nul
start http://localhost:8000/backend/test_results.php
echo.
echo ========================================
echo Сервер запущен! Нажмите Ctrl+C для остановки
echo ========================================
echo.

REM Запуск PHP сервера
"%PHP_PATH%" -S localhost:8000

