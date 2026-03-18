<?php
/**
 * Упрощенный скрипт для запуска тестов PHPUnit
 */

// Включаем вывод ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Устанавливаем кодировку UTF-8
mb_internal_encoding('UTF-8');
header('Content-Type: application/json; charset=utf-8');

// Функция для возврата JSON
function returnJson($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    returnJson(['success' => false, 'error' => 'Метод не разрешен']);
}

// Получение параметров
$action = $_POST['action'] ?? '';
$suite = $_POST['suite'] ?? 'all';

if ($action !== 'run_tests') {
    http_response_code(400);
    returnJson(['success' => false, 'error' => 'Неверное действие']);
}

// ПУТИ - ГЛАВНОЕ ИСПРАВЛЕНИЕ!
$phpunitPath = 'C:/Users/332/Desktop/auth_project/phpunit.phar';
$xmlPath = __DIR__ . '/test-results.xml';

// Проверка существования PHPUnit
if (!file_exists($phpunitPath)) {
    returnJson([
        'success' => false,
        'error' => "PHPUnit не найден по пути: $phpunitPath",
        'hint' => 'Убедитесь, что файл phpunit.phar находится в корне проекта'
    ]);
}

// Удаляем старый XML файл
if (file_exists($xmlPath)) {
    unlink($xmlPath);
}

// Формируем команду
$command = sprintf(
    'php %s --log-junit %s 2>&1',
    escapeshellarg($phpunitPath),
    escapeshellarg($xmlPath)
);

// Запускаем тесты
$startTime = microtime(true);
$output = [];
$returnCode = 0;

exec($command, $output, $returnCode);
$executionTime = round(microtime(true) - $startTime, 3);

// Обработка результата
if (file_exists($xmlPath) && filesize($xmlPath) > 0) {
    // Парсим XML
    $xml = simplexml_load_file($xmlPath);
    
    $total = (int)$xml->testsuite['tests'];
    $failed = (int)$xml->testsuite['failures'] + (int)$xml->testsuite['errors'];
    $passed = $total - $failed - (int)$xml->testsuite['skipped'];
    $skipped = (int)$xml->testsuite['skipped'];
    $time = (float)$xml->testsuite['time'];
    
    // Формируем группы тестов
    $groups = [];
    if (isset($xml->testsuite->testsuite)) {
        foreach ($xml->testsuite->testsuite as $testsuite) {
            $groupTests = [];
            foreach ($testsuite->testcase as $testcase) {
                $status = 'passed';
                if (isset($testcase->failure) || isset($testcase->error)) {
                    $status = 'failed';
                } elseif (isset($testcase->skipped)) {
                    $status = 'skipped';
                }
                
                $groupTests[] = [
                    'name' => (string)$testcase['name'],
                    'class' => (string)$testcase['class'],
                    'status' => $status,
                    'time' => (float)$testcase['time']
                ];
            }
            
            $groups[] = [
                'name' => (string)$testsuite['name'],
                'tests' => $groupTests
            ];
        }
    }
    
    returnJson([
        'success' => true,
        'total' => $total,
        'passed' => $passed,
        'failed' => $failed,
        'skipped' => $skipped,
        'time' => $time,
        'executionTime' => $executionTime,
        'groups' => $groups
    ]);
} else {
    // Если XML не создан, показываем ошибку
    returnJson([
        'success' => false,
        'error' => 'Не удалось запустить тесты или получить результаты',
        'command' => $command,
        'returnCode' => $returnCode,
        'output' => $output,
        'executionTime' => $executionTime,
        'xmlExists' => file_exists($xmlPath) ? 'да' : 'нет',
        'xmlSize' => file_exists($xmlPath) ? filesize($xmlPath) : 0
    ]);
}