<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Проверка test_runner.php</h3>";

// 1. Проверка mbstring
echo "1. mbstring: " . (extension_loaded('mbstring') ? "✓ Загружен" : "✗ Нет") . "<br>";

// 2. Проверка путей
$possiblePaths = [
    'C:/Users/332/Desktop/auth_project/phpunit.phar',
    'C:/Users/332/Desktop/auth_project/vendor/bin/phpunit.bat',
    __DIR__ . '/../phpunit.phar',
];

echo "<br>2. Поиск PHPUnit:<br>";
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        echo "✓ Найден: $path<br>";
        // Проверяем запуск
        $output = [];
        exec('php ' . escapeshellarg($path) . ' --version 2>&1', $output, $returnCode);
        echo "   Код: $returnCode, Вывод: " . implode(' ', $output) . "<br>";
    } else {
        echo "✗ Не найден: $path<br>";
    }
}

// 3. Проверка прав на запись
echo "<br>3. Права на запись:<br>";
echo "Текущая папка: " . __DIR__ . "<br>";
echo "Доступна для записи: " . (is_writable(__DIR__) ? "✓ Да" : "✗ Нет") . "<br>";

// 4. Проверка папки logs
$logsDir = __DIR__ . '/logs';
echo "Папка logs существует: " . (file_exists($logsDir) ? "✓ Да" : "✗ Нет") . "<br>";
if (file_exists($logsDir)) {
    echo "Папка logs доступна для записи: " . (is_writable($logsDir) ? "✓ Да" : "✗ Нет") . "<br>";
}

// 5. Тестовый запуск команды
echo "<br>4. Тестовый запуск:<br>";
$testCommand = 'php C:/Users/332/Desktop/auth_project/phpunit.phar --version 2>&1';
echo "Команда: $testCommand<br>";
$output = [];
exec($testCommand, $output, $returnCode);
echo "Код возврата: $returnCode<br>";
echo "Вывод: " . implode('<br>', $output) . "<br>";