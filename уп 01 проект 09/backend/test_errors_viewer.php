<?php
/**
 * Веб-интерфейс для просмотра ошибок тестов
 * Отображает детальную информацию о каждом упавшем тесте
 */

mb_internal_encoding('UTF-8');
header('Content-Type: text/html; charset=utf-8');

// Пути к файлам
$xmlPath = __DIR__ . '/test-results.xml';
$logPath = __DIR__ . '/logs/test_errors.log';

// Функция для очистки UTF-8
function cleanUtf8($string) {
    if (!is_string($string)) {
        return $string;
    }
    return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
}

// Парсинг XML результатов тестов
function parseFailedTests($xmlPath) {
    $failedTests = [];
    
    if (!file_exists($xmlPath) || filesize($xmlPath) === 0) {
        return $failedTests;
    }
    
    libxml_use_internal_errors(true);
    $xml = @simplexml_load_file($xmlPath);
    
    if (!$xml) {
        return $failedTests;
    }
    
    // Проходим по всем тестам
    foreach ($xml->xpath('//testcase') as $testcase) {
        $status = 'passed';
        $error = '';
        $failure = '';
        
        // Проверяем наличие ошибки
        if (isset($testcase->error)) {
            $status = 'error';
            $error = (string)$testcase->error;
        }
        
        // Проверяем наличие провала
        if (isset($testcase->failure)) {
            $status = 'failed';
            $failure = (string)$testcase->failure;
        }
        
        if ($status !== 'passed') {
            $failedTests[] = [
                'name' => (string)$testcase['name'],
                'class' => (string)$testcase['class'],
                'file' => (string)$testcase['file'],
                'line' => (string)$testcase['line'],
                'status' => $status,
                'message' => $error ?: $failure,
                'time' => isset($testcase['time']) ? (float)$testcase['time'] : 0
            ];
        }
    }
    
    return $failedTests;
}

// Получаем статистику
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$skippedTests = 0;
$executionTime = 0;

if (file_exists($xmlPath) && filesize($xmlPath) > 0) {
    libxml_use_internal_errors(true);
    $xml = @simplexml_load_file($xmlPath);
    
    if ($xml) {
        $testsuite = $xml->xpath('//testsuite[@name="All Tests"]');
        if (!empty($testsuite)) {
            $suite = $testsuite[0];
            $totalTests = (int)$suite['tests'];
            $passedTests = $totalTests - (int)$suite['errors'] - (int)$suite['failures'];
            $failedTests = (int)$suite['errors'] + (int)$suite['failures'];
            $skippedTests = (int)$suite['skipped'];
            $executionTime = (float)$suite['time'];
        }
    }
}

// Получаем список упавших тестов
$failedTestsList = parseFailedTests($xmlPath);

// Группируем по классам
$groupedTests = [];
foreach ($failedTestsList as $test) {
    $className = $test['class'];
    if (!isset($groupedTests[$className])) {
        $groupedTests[$className] = [];
    }
    $groupedTests[$className][] = $test;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибки тестов - Детальный просмотр</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.2);
            padding: 15px 25px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .stat-card .label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .stat-card .value {
            font-size: 2em;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .content {
            padding: 30px;
        }
        
        .nav-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .test-group {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .test-group-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 2px solid #667eea;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s;
        }
        
        .test-group-header:hover {
            background: #e9ecef;
        }
        
        .test-group-header h2 {
            color: #333;
            font-size: 1.5em;
        }
        
        .test-group-header .count {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .test-group-content {
            display: none;
            padding: 20px;
        }
        
        .test-group-content.expanded {
            display: block;
        }
        
        .test-item {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .test-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .test-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .test-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
        
        .test-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .status-error {
            background: #dc3545;
            color: white;
        }
        
        .status-failed {
            background: #fd7e14;
            color: white;
        }
        
        .test-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        
        .info-label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: bold;
            color: #333;
            word-break: break-all;
        }
        
        .error-message {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            line-height: 1.6;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .no-errors {
            text-align: center;
            padding: 60px 20px;
            color: #28a745;
        }
        
        .no-errors-icon {
            font-size: 5em;
            margin-bottom: 20px;
        }
        
        .no-errors h2 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .toggle-icon {
            transition: transform 0.3s;
        }
        
        .toggle-icon.expanded {
            transform: rotate(180deg);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Детальный просмотр ошибок тестов</h1>
            <div class="stats">
                <div class="stat-card">
                    <div class="label">Всего тестов</div>
                    <div class="value"><?= $totalTests ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Пройдено</div>
                    <div class="value" style="color: #28a745;"><?= $passedTests ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Ошибок</div>
                    <div class="value" style="color: #dc3545;"><?= $failedTests ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Время</div>
                    <div class="value"><?= number_format($executionTime, 2) ?>s</div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="nav-buttons">
                <a href="test_results.php" class="btn btn-primary">← Назад к результатам</a>
                <a href="test_errors_viewer.php" class="btn btn-secondary">🔄 Обновить</a>
            </div>
            
            <?php if (empty($failedTestsList)): ?>
                <div class="no-errors">
                    <div class="no-errors-icon">✅</div>
                    <h2>Все тесты пройдены успешно!</h2>
                    <p>Нет ошибок для отображения.</p>
                </div>
            <?php else: ?>
                <?php foreach ($groupedTests as $className => $tests): ?>
                    <div class="test-group">
                        <div class="test-group-header" onclick="toggleGroup(this)">
                            <h2><?= htmlspecialchars($className) ?></h2>
                            <div>
                                <span class="count"><?= count($tests) ?> ошибок</span>
                                <span class="toggle-icon">▼</span>
                            </div>
                        </div>
                        <div class="test-group-content">
                            <?php foreach ($tests as $test): ?>
                                <div class="test-item">
                                    <div class="test-item-header">
                                        <div class="test-name"><?= htmlspecialchars($test['name']) ?></div>
                                        <div class="test-status status-<?= $test['status'] ?>">
                                            <?= $test['status'] === 'error' ? 'Ошибка' : 'Провал' ?>
                                        </div>
                                    </div>
                                    <div class="test-info">
                                        <div class="info-item">
                                            <div class="info-label">Файл</div>
                                            <div class="info-value"><?= htmlspecialchars(basename($test['file'])) ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Строка</div>
                                            <div class="info-value"><?= htmlspecialchars($test['line']) ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Время выполнения</div>
                                            <div class="info-value"><?= number_format($test['time'], 4) ?>s</div>
                                        </div>
                                    </div>
                                    <div class="error-message">
<?= htmlspecialchars(cleanUtf8($test['message'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function toggleGroup(header) {
            const content = header.nextElementSibling;
            const icon = header.querySelector('.toggle-icon');
            
            content.classList.toggle('expanded');
            icon.classList.toggle('expanded');
        }
        
        // Автоматически раскрываем первую группу
        document.addEventListener('DOMContentLoaded', function() {
            const firstGroup = document.querySelector('.test-group-header');
            if (firstGroup) {
                toggleGroup(firstGroup);
            }
        });
    </script>
</body>
</html>

