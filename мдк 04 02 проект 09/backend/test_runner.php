<?php
/**
 * Скрипт для запуска тестов PHPUnit и возврата результатов в JSON
 */

// Включаем обработку ошибок
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Устанавливаем кодировку UTF-8
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Устанавливаем заголовок JSON
header('Content-Type: application/json; charset=utf-8');

// Путь к файлу логов
define('LOG_FILE', __DIR__ . '/logs/test_errors.log');

// Функция для логирования ошибок
function logError($message, $data = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    
    if (!empty($data)) {
        $logEntry .= "Данные: " . print_r($data, true) . "\n";
    }
    
    $logEntry .= str_repeat('-', 80) . "\n";
    
    @file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

// Функция для очистки строк от невалидных UTF-8 символов
function cleanUtf8($string) {
    if (!is_string($string)) {
        return $string;
    }
    
    // Способ 1: Использование iconv для удаления невалидных символов
    $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE//TRANSLIT', $string);
    if ($cleaned !== false) {
        $string = $cleaned;
    }
    
    // Способ 2: Использование mb_convert_encoding
    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    
    // Способ 3: Удаление невалидных байтов вручную
    $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
    
    // Способ 4: Проверка и очистка через json_encode/decode
    $json = json_encode($string, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
    if ($json !== false && $json !== 'null') {
        $decoded = json_decode($json, true);
        if ($decoded !== null) {
            $string = $decoded;
        }
    }
    
    return $string;
}

// Функция для рекурсивной очистки массива от невалидных UTF-8
function cleanArray($data) {
    if (is_array($data)) {
        return array_map('cleanArray', $data);
    } elseif (is_string($data)) {
        return cleanUtf8($data);
    } else {
        return $data;
    }
}

// Функция для безопасного возврата JSON
function returnJson($data) {
    // Очищаем данные от невалидных UTF-8 символов
    $data = cleanArray($data);
    
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE);
    if ($json === false) {
        // Если не удалось закодировать JSON, возвращаем ошибку
        $errorMsg = json_last_error_msg();
        // Очищаем сообщение об ошибке тоже
        $errorMsg = cleanUtf8($errorMsg);
        echo json_encode([
            'success' => false,
            'error' => 'Ошибка кодирования JSON: ' . $errorMsg,
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'time' => 0,
            'groups' => []
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
    } else {
        echo $json;
    }
    exit;
}

// Обработка фатальных ошибок
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        returnJson([
            'success' => false,
            'error' => 'Критическая ошибка PHP: ' . $error['message'],
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'time' => 0,
            'groups' => []
        ]);
    }
});

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    returnJson(['success' => false, 'error' => 'Метод не разрешен', 'total' => 0, 'passed' => 0, 'failed' => 0, 'skipped' => 0, 'time' => 0, 'groups' => []]);
}

// Получение параметров
$action = $_POST['action'] ?? '';
$suite = $_POST['suite'] ?? 'all';

if ($action !== 'run_tests') {
    http_response_code(400);
    returnJson(['success' => false, 'error' => 'Неверное действие', 'total' => 0, 'passed' => 0, 'failed' => 0, 'skipped' => 0, 'time' => 0, 'groups' => []]);
}

// Определение набора тестов
switch ($suite) {
    case 'unit':
        $testSuite = 'Unit Tests';
        break;
    case 'functional':
        $testSuite = 'Functional Tests';
        break;
    default:
        $testSuite = 'All Tests';
        break;
}

// Путь к PHPUnit
$phpunitPath = null;
$phpunitFound = false;

// Список возможных путей к PHPUnit
$possiblePaths = [];

// На Windows проверяем .bat и .php файлы
if (PHP_OS_FAMILY === 'Windows') {
    $possiblePaths = [
        __DIR__ . '/vendor/bin/phpunit.bat',
        __DIR__ . '/vendor/bin/phpunit',
        __DIR__ . '/../phpunit.phar',
        __DIR__ . '/phpunit.phar'
    ];
} else {
    $possiblePaths = [
        __DIR__ . '/vendor/bin/phpunit',
        __DIR__ . '/../phpunit.phar',
        __DIR__ . '/phpunit.phar'
    ];
}

// Ищем PHPUnit
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $phpunitPath = $path;
        $phpunitFound = true;
        break;
    }
}

// Если не найден локально, ищем в системе
if (!$phpunitFound) {
    if (PHP_OS_FAMILY === 'Windows') {
        $whichOutput = @shell_exec('where phpunit 2>&1');
    } else {
        $whichOutput = @shell_exec('which phpunit 2>&1');
    }
    
    if ($whichOutput && strpos($whichOutput, 'phpunit') !== false && strpos($whichOutput, 'not found') === false) {
        $phpunitPath = trim(explode("\n", $whichOutput)[0]);
        $phpunitFound = true;
    }
}

// Если PHPUnit не найден, возвращаем ошибку
if (!$phpunitFound) {
    logError('PHPUnit не найден', [
        'searchedPaths' => $possiblePaths,
        'currentDir' => __DIR__,
        'phpExecutable' => $phpExecutable ?? 'не определен'
    ]);
    
    returnJson([
        'success' => false,
        'error' => 'PHPUnit не найден. Выполните: cd backend && composer install. Подробности в файле: backend/logs/test_errors.log',
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'skipped' => 0,
        'time' => 0,
        'groups' => []
    ]);
}

// Находим путь к PHP исполняемому файлу
// Упрощенная логика: PHP_BINARY всегда доступен при запуске через PHP сервер
$phpExecutable = null;

// Приоритет 1: PHP_BINARY (всегда доступен, если сервер запущен через PHP)
if (defined('PHP_BINARY') && PHP_BINARY) {
    $phpExecutable = PHP_BINARY;
}

// Приоритет 2: Ищем в стандартных местах (только если PHP_BINARY не определен)
if (!$phpExecutable) {
    $phpPaths = [];
    if (PHP_OS_FAMILY === 'Windows') {
        $phpPaths = [
            'C:\\PHP\\php.exe',
            'C:\\php\\php.exe',
            'C:\\xampp\\php\\php.exe',
            'C:\\wamp64\\bin\\php\\php8.2\\php.exe',
            'C:\\wamp64\\bin\\php\\php8.1\\php.exe',
            'C:\\wamp64\\bin\\php\\php8.0\\php.exe',
            'C:\\Program Files\\PHP\\php.exe',
            'C:\\Program Files (x86)\\PHP\\php.exe'
        ];
    } else {
        $phpPaths = ['/usr/bin/php', '/usr/local/bin/php'];
    }
    
    foreach ($phpPaths as $path) {
        if (file_exists($path)) {
            $phpExecutable = $path;
            break;
        }
    }
}

// Приоритет 3: Пробуем через which/where
if (!$phpExecutable) {
    if (PHP_OS_FAMILY === 'Windows') {
        $whichOutput = @shell_exec('where php 2>&1');
    } else {
        $whichOutput = @shell_exec('which php 2>&1');
    }
    
    if ($whichOutput && strpos($whichOutput, 'php') !== false && strpos($whichOutput, 'not found') === false) {
        $foundPath = trim(explode("\n", $whichOutput)[0]);
        if ($foundPath && file_exists($foundPath)) {
            $phpExecutable = $foundPath;
        }
    }
}

// Финальный fallback: используем PHP_BINARY или просто 'php'
if (!$phpExecutable) {
    if (defined('PHP_BINARY') && PHP_BINARY) {
        $phpExecutable = PHP_BINARY;
    } else {
        $phpExecutable = 'php';
    }
}

// Команда для запуска тестов
// Упрощенная логика: всегда используем PHP для запуска PHPUnit
// Это гарантирует, что команда будет работать даже если PHP не в PATH
$phpunitCommand = '';
if (PHP_OS_FAMILY === 'Windows') {
    // На Windows .bat файлы содержат PHP команду внутри, но лучше запускать напрямую
    if (substr($phpunitPath, -4) === '.bat') {
        // .bat файлы можно запускать напрямую
        $phpunitCommand = escapeshellarg($phpunitPath);
    } else {
        // Для всех остальных файлов используем PHP
        $phpunitCommand = escapeshellarg($phpExecutable) . ' ' . escapeshellarg($phpunitPath);
    }
} else {
    // На Linux/Mac: если исполняемый - запускаем напрямую, иначе через PHP
    if (is_executable($phpunitPath)) {
        $phpunitCommand = escapeshellarg($phpunitPath);
    } else {
        $phpunitCommand = escapeshellarg($phpExecutable) . ' ' . escapeshellarg($phpunitPath);
    }
}

// Путь к XML файлу результатов
$xmlPath = __DIR__ . '/test-results.xml';

// Проверяем права на запись в директорию
if (!is_writable(__DIR__)) {
    logError('Нет прав на запись в директорию', [
        'directory' => __DIR__,
        'isWritable' => is_writable(__DIR__),
        'permissions' => substr(sprintf('%o', fileperms(__DIR__)), -4)
    ]);
    
    returnJson([
        'success' => false,
        'error' => 'Нет прав на запись в директорию backend/. Проверьте права доступа. Подробности в файле: backend/logs/test_errors.log',
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'skipped' => 0,
        'time' => 0,
        'groups' => []
    ]);
}

// Удаляем старый XML файл, если существует
if (file_exists($xmlPath)) {
    @unlink($xmlPath);
}

// Не проверяем PHP заранее - просто пробуем выполнить команду
// Если PHP не найден, ошибка будет видна в выводе команды

// Формируем команду
// На Windows используем правильный синтаксис
if (PHP_OS_FAMILY === 'Windows') {
    // Используем абсолютный путь для XML файла
    $xmlPathAbsolute = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'test-results.xml';
    $command = sprintf(
        '%s --testsuite %s --log-junit %s 2>&1',
        $phpunitCommand,
        escapeshellarg($testSuite),
        escapeshellarg($xmlPathAbsolute)
    );
} else {
    $command = sprintf(
        '%s --testsuite %s --log-junit %s 2>&1',
        $phpunitCommand,
        escapeshellarg($testSuite),
        escapeshellarg($xmlPath)
    );
}

// Запуск тестов
$startTime = microtime(true);
$output = [];
$returnCode = 0;

try {
    // На Windows устанавливаем кодировку UTF-8 для вывода
    if (PHP_OS_FAMILY === 'Windows') {
        $command = 'chcp 65001 >nul 2>&1 && ' . $command;
    }
    
    // Выполняем команду
    exec($command, $output, $returnCode);
    $executionTime = microtime(true) - $startTime;
    
    // Если вывод пустой и есть ошибка, пробуем получить через shell_exec
    if (empty($output) && $returnCode !== 0) {
        $outputText = @shell_exec($command . ' 2>&1');
        if ($outputText !== null && trim($outputText) !== '') {
            $output = array_filter(explode("\n", $outputText), function($line) {
                return trim($line) !== '';
            });
            $output = array_values($output);
        }
    }
    
    // Конвертируем вывод в UTF-8 если нужно
    if (PHP_OS_FAMILY === 'Windows' && !empty($output)) {
        foreach ($output as $key => $line) {
            // Пытаемся определить кодировку и конвертировать
            if (!mb_check_encoding($line, 'UTF-8')) {
                // Пробуем конвертировать из Windows-1251 или CP866
                $converted = @iconv('Windows-1251', 'UTF-8//IGNORE', $line);
                if ($converted !== false) {
                    $output[$key] = $converted;
                } else {
                    $converted = @iconv('CP866', 'UTF-8//IGNORE', $line);
                    if ($converted !== false) {
                        $output[$key] = $converted;
                    }
                }
            }
        }
    }
    
    // Проверяем, был ли создан XML файл
    $xmlPathToCheck = $xmlPath;
    if (PHP_OS_FAMILY === 'Windows') {
        $xmlPathToCheck = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'test-results.xml';
    }
    
    // Парсинг результатов из XML
    $results = parseTestResults($xmlPathToCheck, $executionTime);
    
    // Если XML файл не создан, но команда выполнилась
    if (!file_exists($xmlPathToCheck)) {
        // Очищаем вывод от невалидных UTF-8 символов
        $cleanOutput = array_map('cleanUtf8', $output);
        $outputText = implode("\n", $cleanOutput);
        
        // Логируем ошибку с детальной информацией
        logError('Не удалось создать XML файл результатов', [
            'command' => $command,
            'returnCode' => $returnCode,
            'output' => $outputText,
            'phpExecutable' => $phpExecutable ?? 'не определен',
            'phpunitPath' => $phpunitPath ?? 'не определен',
            'xmlPath' => $xmlPathToCheck,
            'executionTime' => $executionTime
        ]);
        
        // Упрощенное сообщение об ошибке
        $errorMessage = 'Не удалось запустить тесты. ';
        
        // Проверяем типичные ошибки
        $outputLower = mb_strtolower($outputText);
        if (strpos($outputLower, 'not recognized') !== false || 
            strpos($outputLower, 'не распознан') !== false || 
            strpos($outputLower, 'is not recognized') !== false) {
            // PHP не найден
            $errorMessage = 'PHP не найден. ';
            if (PHP_OS_FAMILY === 'Windows') {
                $errorMessage .= 'Установите PHP в C:\\PHP\\ или добавьте в PATH. ';
            }
            $errorMessage .= 'Или используйте start_server.bat для автоматического запуска. ';
            $errorMessage .= 'Подробности в файле: backend/logs/test_errors.log';
        } elseif (strpos($outputLower, 'phpunit') === false && strpos($outputLower, 'phpunit') === false) {
            $errorMessage = 'PHPUnit не найден. Выполните: cd backend && composer install. ';
            $errorMessage .= 'Подробности в файле: backend/logs/test_errors.log';
        } elseif (strpos($outputLower, 'permission') !== false || strpos($outputLower, 'доступ') !== false) {
            $errorMessage = 'Проблема с правами доступа. Проверьте права на папку backend/. ';
            $errorMessage .= 'Подробности в файле: backend/logs/test_errors.log';
        } else {
            $errorMessage .= 'Подробности в файле: backend/logs/test_errors.log';
        }
        
        // Возвращаем упрощенный ответ
        returnJson([
            'success' => false,
            'error' => $errorMessage,
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'time' => $executionTime,
            'groups' => []
        ]);
    }
    
    // Формирование ответа
    // Очищаем вывод от невалидных UTF-8 символов
    $cleanOutput = array_map('cleanUtf8', $output);
    
    // Логируем детали ошибок тестов
    if ($results['failed'] > 0) {
        $failedTests = [];
        foreach ($results['groups'] as $group) {
            foreach ($group['tests'] as $test) {
                if ($test['status'] === 'failed' && !empty($test['error'])) {
                    $failedTests[] = [
                        'name' => $test['name'],
                        'class' => $test['class'],
                        'error' => $test['error']
                    ];
                }
            }
        }
        
        logError('Тесты выполнены с ошибками', [
            'total' => $results['total'],
            'passed' => $results['passed'],
            'failed' => $results['failed'],
            'skipped' => $results['skipped'],
            'time' => $executionTime,
            'returnCode' => $returnCode,
            'failedTests' => $failedTests
        ]);
    }
    
    // Упрощенный ответ без технических деталей
    $response = [
        'success' => true,
        'total' => $results['total'],
        'passed' => $results['passed'],
        'failed' => $results['failed'],
        'skipped' => $results['skipped'],
        'time' => $executionTime,
        'groups' => $results['groups']
    ];
    
    returnJson($response);
    
} catch (Exception $e) {
    // Логируем исключение
    logError('Исключение при выполнении тестов', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'command' => $command ?? 'не определен',
        'phpExecutable' => $phpExecutable ?? 'не определен',
        'phpunitPath' => $phpunitPath ?? 'не определен'
    ]);
    
    // Упрощенная обработка исключений
    returnJson([
        'success' => false,
        'error' => 'Ошибка при выполнении тестов. Проверьте настройки PHPUnit. Подробности в файле: backend/logs/test_errors.log',
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'skipped' => 0,
        'time' => microtime(true) - $startTime,
        'groups' => []
    ]);
}

/**
 * Парсинг результатов тестов из XML файла
 */
function parseTestResults($xmlPath, $totalTime) {
    $results = [
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'skipped' => 0,
        'groups' => []
    ];
    
    if (!file_exists($xmlPath)) {
        return $results;
    }
    
    // Проверяем, что файл не пустой
    if (filesize($xmlPath) === 0) {
        return $results;
    }
    
    // Подавляем предупреждения при парсинге XML
    libxml_use_internal_errors(true);
    
    // Читаем XML файл и конвертируем в UTF-8 если нужно
    $xmlContent = file_get_contents($xmlPath);
    if ($xmlContent === false) {
        return $results;
    }
    
    // Проверяем и конвертируем кодировку
    if (!mb_check_encoding($xmlContent, 'UTF-8')) {
        $xmlContent = mb_convert_encoding($xmlContent, 'UTF-8', 'auto');
    }
    
    // Парсим XML
    $xml = @simplexml_load_string($xmlContent);
    
    if (!$xml) {
        // Если не удалось распарсить, логируем ошибку
        $errors = libxml_get_errors();
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = trim($error->message);
        }
        libxml_clear_errors();
        
        logError('Ошибка парсинга XML файла результатов', [
            'xmlPath' => $xmlPath,
            'fileSize' => filesize($xmlPath),
            'errors' => $errorMessages,
            'fileExists' => file_exists($xmlPath)
        ]);
        
        return $results;
    }
    
    $results['total'] = (int)$xml->testsuite['tests'];
    $results['passed'] = (int)$xml->testsuite['tests'] - (int)$xml->testsuite['failures'] - (int)$xml->testsuite['errors'] - (int)$xml->testsuite['skipped'];
    $results['failed'] = (int)$xml->testsuite['failures'] + (int)$xml->testsuite['errors'];
    $results['skipped'] = (int)$xml->testsuite['skipped'];
    
    // Группировка тестов по файлам
    $groups = [];
    
    foreach ($xml->testsuite->testsuite as $testsuite) {
        $groupName = (string)$testsuite['name'];
        $groupTests = [];
        
        foreach ($testsuite->testcase as $testcase) {
            $testName = (string)$testcase['name'];
            $testClass = (string)$testcase['class'];
            $testTime = (float)$testcase['time'];
            
            // Определение статуса
            $status = 'passed';
            $error = null;
            
            if (isset($testcase->failure)) {
                $status = 'failed';
                $error = cleanUtf8((string)$testcase->failure);
            } elseif (isset($testcase->error)) {
                $status = 'failed';
                $error = cleanUtf8((string)$testcase->error);
            } elseif (isset($testcase->skipped)) {
                $status = 'skipped';
                $error = null;
            }
            
            // Определение группы из пути к файлу
            $testGroup = 'Unknown';
            if (preg_match('/\/(Unit|Functional)\/([^\/]+)/', $testClass, $matches)) {
                $testGroup = cleanUtf8($matches[1] . ' / ' . $matches[2]);
            } elseif (preg_match('/\/(Unit|Functional)/', $testClass, $matches)) {
                $testGroup = cleanUtf8($matches[1]);
            }
            
            $groupTests[] = [
                'name' => cleanUtf8($testName),
                'class' => cleanUtf8($testClass),
                'status' => $status,
                'time' => $testTime,
                'group' => $testGroup,
                'error' => $error
            ];
        }
        
        if (!empty($groupTests)) {
            $groups[] = [
                'name' => cleanUtf8($groupName),
                'tests' => $groupTests
            ];
        }
    }
    
    $results['groups'] = $groups;
    
    return $results;
}

