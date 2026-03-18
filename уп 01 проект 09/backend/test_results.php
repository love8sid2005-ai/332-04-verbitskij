<?php
/**
 * Веб-интерфейс для запуска и отображения результатов тестов
 */

// Установка таймаута для выполнения тестов
set_time_limit(300);

// Подключение стилей
$stylesPath = '../styles.css';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система тестирования аутентификации</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($stylesPath); ?>">
    <style>
        .test-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .test-header {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
        }
        
        .test-controls {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .btn-test {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-test-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .btn-test-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-test-secondary {
            background: var(--text-secondary);
            color: white;
        }
        
        .btn-test:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .statistics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .stat-success { color: var(--success-color); }
        .stat-error { color: var(--error-color); }
        .stat-warning { color: #f59e0b; }
        .stat-info { color: var(--primary-color); }
        
        .test-results {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
        }
        
        .test-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .test-table th,
        .test-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .test-table th {
            background: var(--bg-light);
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .test-table tr:hover {
            background: var(--bg-light);
        }
        
        .status-icon {
            font-size: 1.2rem;
            margin-right: 8px;
        }
        
        .status-success { color: var(--success-color); }
        .status-error { color: var(--error-color); }
        .status-skipped { color: #f59e0b; }
        
        .test-group {
            margin-bottom: 30px;
        }
        
        .test-group-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .error-details {
            background: #fee2e2;
            border-left: 4px solid var(--error-color);
            padding: 10px;
            margin-top: 5px;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #991b1b;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 40px;
        }
        
        .loading.active {
            display: block;
        }
        
        .spinner {
            border: 4px solid var(--border-color);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--border-color);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 20px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            transition: width 0.3s ease;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .test-table {
                font-size: 0.9rem;
            }
            
            .test-table th,
            .test-table td {
                padding: 8px;
            }
            
            .test-controls {
                flex-direction: column;
            }
            
            .btn-test {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-container">
            <div class="test-header">
                <h1>Система тестирования аутентификации</h1>
                <p style="color: var(--text-secondary); margin-top: 10px;">
                    Комплексная система автоматического тестирования для проекта аутентификации
                </p>
                
                <div class="test-controls">
                    <button class="btn-test btn-test-primary" onclick="runAllTests()" id="runAllBtn">
                        🚀 Запустить все тесты
                    </button>
                    <button class="btn-test btn-test-secondary" onclick="runUnitTests()" id="runUnitBtn">
                        🔍 Модульные тесты
                    </button>
                    <button class="btn-test btn-test-secondary" onclick="runFunctionalTests()" id="runFunctionalBtn">
                        ⚙️ Функциональные тесты
                    </button>
                    <button class="btn-test btn-test-secondary" onclick="clearResults()" id="clearBtn">
                        🗑️ Очистить результаты
                    </button>
                </div>
                
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Выполнение тестов...</p>
                </div>
            </div>
            
            <div id="statistics" style="display: none;">
                <div class="statistics">
                    <div class="stat-card">
                        <div class="stat-label">Всего тестов</div>
                        <div class="stat-value stat-info" id="totalTests">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Успешных</div>
                        <div class="stat-value stat-success" id="passedTests">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Неудачных</div>
                        <div class="stat-value stat-error" id="failedTests">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Пропущенных</div>
                        <div class="stat-value stat-warning" id="skippedTests">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Время выполнения</div>
                        <div class="stat-value stat-info" id="executionTime">0.00s</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Успешность</div>
                        <div class="stat-value stat-success" id="successRate">0%</div>
                    </div>
                </div>
                
                <div class="progress-bar">
                    <div class="progress-fill" id="progressBar" style="width: 0%;"></div>
                </div>
            </div>
            
            <div class="test-results" id="testResults" style="display: none;">
                <div class="export-buttons">
                    <a href="test_errors_viewer.php" class="btn-test btn-test-secondary" style="background: #dc3545;">🔍 Просмотр ошибок</a>
                    <button class="btn-test btn-test-secondary" onclick="exportJSON()">📥 Экспорт JSON</button>
                    <button class="btn-test btn-test-secondary" onclick="exportHTML()">📄 Экспорт HTML</button>
                </div>
                <div id="resultsContent"></div>
            </div>
        </div>
    </div>
    
    <script>
        let testResults = null;
        
        function setButtonsDisabled(disabled) {
            document.getElementById('runAllBtn').disabled = disabled;
            document.getElementById('runUnitBtn').disabled = disabled;
            document.getElementById('runFunctionalBtn').disabled = disabled;
            document.getElementById('clearBtn').disabled = disabled;
        }
        
        function showLoading() {
            document.getElementById('loading').classList.add('active');
            setButtonsDisabled(true);
        }
        
        function hideLoading() {
            document.getElementById('loading').classList.remove('active');
            setButtonsDisabled(false);
        }
        
        function runTests(suite = 'all') {
            showLoading();
            document.getElementById('statistics').style.display = 'none';
            document.getElementById('testResults').style.display = 'none';
            
            const formData = new FormData();
            formData.append('action', 'run_tests');
            formData.append('suite', suite);
            
            fetch('test_runner.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Проверяем статус ответа
                if (!response.ok) {
                    throw new Error('HTTP ошибка: ' + response.status);
                }
                // Получаем текст ответа для проверки
                return response.text();
            })
            .then(text => {
                // Пытаемся распарсить JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    // Если не удалось распарсить, показываем ошибку
                    console.error('Ошибка парсинга JSON:', text);
                    throw new Error('Невалидный JSON ответ от сервера. Возможно, PHPUnit не установлен. Проверьте консоль для деталей.');
                }
                
                hideLoading();
                
                // Проверяем наличие ошибки в ответе
                if (data.error) {
                    alert('Ошибка: ' + data.error + '\n\n' + (data.output || ''));
                    // Показываем пустые результаты
                    displayResults({
                        total: 0,
                        passed: 0,
                        failed: 0,
                        skipped: 0,
                        time: 0,
                        groups: []
                    });
                    return;
                }
                
                testResults = data;
                displayResults(data);
            })
            .catch(error => {
                hideLoading();
                console.error('Ошибка:', error);
                alert('Ошибка при выполнении тестов:\n\n' + error.message + '\n\nПроверьте:\n1. Установлен ли PHPUnit (composer install)\n2. Доступны ли права на запись в папку backend/\n3. Консоль браузера (F12) для деталей');
            });
        }
        
        function runAllTests() {
            runTests('all');
        }
        
        function runUnitTests() {
            runTests('unit');
        }
        
        function runFunctionalTests() {
            runTests('functional');
        }
        
        function displayResults(data) {
            // Статистика
            const total = data.total || 0;
            const passed = data.passed || 0;
            const failed = data.failed || 0;
            const skipped = data.skipped || 0;
            const time = (data.time || 0).toFixed(2);
            const successRate = total > 0 ? ((passed / total) * 100).toFixed(1) : 0;
            
            document.getElementById('totalTests').textContent = total;
            document.getElementById('passedTests').textContent = passed;
            document.getElementById('failedTests').textContent = failed;
            document.getElementById('skippedTests').textContent = skipped;
            document.getElementById('executionTime').textContent = time + 's';
            document.getElementById('successRate').textContent = successRate + '%';
            document.getElementById('progressBar').style.width = successRate + '%';
            
            document.getElementById('statistics').style.display = 'block';
            
            // Результаты тестов
            let html = '';
            
            if (data.groups && data.groups.length > 0) {
                data.groups.forEach(group => {
                    html += `<div class="test-group">
                        <div class="test-group-title">${group.name}</div>
                        <table class="test-table">
                            <thead>
                                <tr>
                                    <th>Название теста</th>
                                    <th>Статус</th>
                                    <th>Время</th>
                                    <th>Группа</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    
                    group.tests.forEach(test => {
                        const statusIcon = test.status === 'passed' ? '✅' : 
                                         test.status === 'failed' ? '❌' : '⚠️';
                        const statusClass = test.status === 'passed' ? 'status-success' : 
                                          test.status === 'failed' ? 'status-error' : 'status-skipped';
                        const time = (test.time || 0).toFixed(3);
                        
                        html += `<tr>
                            <td>${test.name}</td>
                            <td><span class="status-icon ${statusClass}">${statusIcon}</span>${test.status}</td>
                            <td>${time}s</td>
                            <td>${test.group || 'N/A'}</td>
                        </tr>`;
                        
                        if (test.error) {
                            html += `<tr>
                                <td colspan="4">
                                    <div class="error-details">${escapeHtml(test.error)}</div>
                                </td>
                            </tr>`;
                        }
                    });
                    
                    html += `</tbody></table></div>`;
                });
            }
            
            document.getElementById('resultsContent').innerHTML = html;
            document.getElementById('testResults').style.display = 'block';
        }
        
        function clearResults() {
            document.getElementById('statistics').style.display = 'none';
            document.getElementById('testResults').style.display = 'none';
            document.getElementById('resultsContent').innerHTML = '';
            testResults = null;
        }
        
        function exportJSON() {
            if (!testResults) {
                alert('Нет результатов для экспорта');
                return;
            }
            
            const dataStr = JSON.stringify(testResults, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'test_results_' + new Date().toISOString().slice(0, 10) + '.json';
            link.click();
        }
        
        function exportHTML() {
            if (!testResults) {
                alert('Нет результатов для экспорта');
                return;
            }
            
            const html = document.documentElement.outerHTML;
            const dataBlob = new Blob([html], {type: 'text/html'});
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'test_results_' + new Date().toISOString().slice(0, 10) + '.html';
            link.click();
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

