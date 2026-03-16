<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "=== Начало отладки ===<br>";

// Проверяем, существует ли test_runner.php
if (file_exists('test_runner.php')) {
    echo "✓ test_runner.php найден<br>";
    
    // Пробуем подключить его
    require_once 'test_runner.php';
    
    echo "✓ test_runner.php загружен<br>";
} else {
    echo "✗ test_runner.php НЕ найден<br>";
    echo "Текущая папка: " . __DIR__ . "<br>";
    
    // Покажем список файлов
    echo "Файлы в папке backend:<br>";
    $files = scandir(__DIR__);
    foreach ($files as $file) {
        echo "- $file<br>";
    }
}

echo "=== Конец отладки ===";