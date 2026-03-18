# PowerShell скрипт для запуска PHP сервера
# Запуск: .\start_server.ps1

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Запуск PHP сервера для тестов" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$phpPath = $null

# Шаг 1: Проверка PHP в PATH
Write-Host "[1/4] Проверка PHP в PATH..." -ForegroundColor Yellow
try {
    $phpVersion = & php -v 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "    [OK] PHP найден в PATH" -ForegroundColor Green
        $phpPath = "php"
    }
} catch {
    # PHP не в PATH, продолжаем поиск
}

# Шаг 2: Проверка стандартных путей
if (-not $phpPath) {
    Write-Host "[2/4] Поиск PHP в стандартных местах..." -ForegroundColor Yellow
    
    $possiblePaths = @(
        "C:\PHP\php.exe",
        "C:\php\php.exe",
        "C:\xampp\php\php.exe",
        "C:\wamp64\bin\php\php8.2\php.exe",
        "C:\wamp64\bin\php\php8.1\php.exe",
        "C:\wamp64\bin\php\php8.0\php.exe",
        "C:\Program Files\PHP\php.exe",
        "C:\Program Files (x86)\PHP\php.exe"
    )
    
    foreach ($path in $possiblePaths) {
        if (Test-Path $path) {
            Write-Host "    [OK] PHP найден: $path" -ForegroundColor Green
            $phpPath = $path
            break
        }
    }
}

# Шаг 3: Проверка переменной окружения PHP_HOME
if (-not $phpPath -and $env:PHP_HOME) {
    $phpHomePath = Join-Path $env:PHP_HOME "php.exe"
    if (Test-Path $phpHomePath) {
        Write-Host "    [OK] PHP найден через PHP_HOME: $phpHomePath" -ForegroundColor Green
        $phpPath = $phpHomePath
    }
}

# Шаг 4: Если ничего не найдено
if (-not $phpPath) {
    Write-Host "[4/4] ❌ PHP не найден!" -ForegroundColor Red
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "РЕШЕНИЕ ПРОБЛЕМЫ:" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "1. Установите PHP с https://windows.php.net/download/" -ForegroundColor Yellow
    Write-Host "   Распакуйте в C:\PHP\" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "2. Или используйте XAMPP: https://www.apachefriends.org/" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "3. Или добавьте PHP в PATH:" -ForegroundColor Yellow
    Write-Host "   [Environment]::SetEnvironmentVariable('Path', [Environment]::GetEnvironmentVariable('Path', 'Machine') + ';C:\PHP', 'Machine')" -ForegroundColor Gray
    Write-Host ""
    Read-Host "Нажмите Enter для выхода"
    exit 1
}

# Проверка версии PHP
Write-Host "[4/4] ✅ PHP найден!" -ForegroundColor Green
Write-Host ""
Write-Host "Проверка версии PHP..." -ForegroundColor Yellow
try {
    & $phpPath -v
} catch {
    Write-Host "❌ Ошибка при запуске PHP!" -ForegroundColor Red
    Read-Host "Нажмите Enter для выхода"
    exit 1
}
Write-Host ""

# Запуск сервера
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ЗАПУСК СЕРВЕРА" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Сервер будет доступен по адресу:" -ForegroundColor Yellow
Write-Host "  http://localhost:8000" -ForegroundColor White
Write-Host ""
Write-Host "Страница тестов:" -ForegroundColor Yellow
Write-Host "  http://localhost:8000/backend/test_results.php" -ForegroundColor White
Write-Host ""
Write-Host "Открываю браузер через 2 секунды..." -ForegroundColor Yellow
Start-Sleep -Seconds 2
Start-Process "http://localhost:8000/backend/test_results.php"
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Сервер запущен! Нажмите Ctrl+C для остановки" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Запуск PHP сервера
& $phpPath -S localhost:8000

