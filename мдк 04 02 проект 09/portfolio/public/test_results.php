<?php
require_once "../backend/config.php";
require_once "../backend/auth.php";

class TestRunner {
    public $results = [];
    public $stats = ["total" => 0, "passed" => 0, "failed" => 0];
    private $testDb;

    public function setUp() {
        $this->testDb = __DIR__ . "/../backend/test_database.db";
        if (file_exists($this->testDb)) unlink($this->testDb);
        
        $db = new PDO("sqlite:" . $this->testDb);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("CREATE TABLE users(id INTEGER PRIMARY KEY AUTOINCREMENT, phone TEXT UNIQUE NOT NULL, password TEXT NOT NULL, secret_question TEXT NOT NULL, secret_answer TEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    }

    public function runTest($name, $fn) {
        $this->stats["total"]++;
        $start = microtime(true);
        
        try {
            $_SESSION = [];
            $this->setUp();
            call_user_func($fn);
            $time = round((microtime(true) - $start) * 1000, 2);
            $this->results[] = ["name" => $name, "status" => "passed", "time" => $time];
            $this->stats["passed"]++;
        } catch (Exception $e) {
            $time = round((microtime(true) - $start) * 1000, 2);
            $this->results[] = ["name" => $name, "status" => "failed", "message" => $e->getMessage(), "time" => $time];
            $this->stats["failed"]++;
        }
    }
}

$runner = new TestRunner();
$category = $_GET["cat"] ?? "main";
$runTests = $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["run_tests"]);

if ($runTests) {
    $testPath = dirname(__DIR__) . "/tests/";
    
    if ($category === "validation") {
        $file = $testPath . "ValidationTests.php";
        if (!file_exists($file)) {
            die("Файл не найден: " . $file);
        }
        require_once $file;
        runValidationTests($runner);
    } elseif ($category === "login") {
        $file = $testPath . "LoginTests.php";
        if (!file_exists($file)) {
            die("Файл не найден: " . $file);
        }
        require_once $file;
        runLoginTests($runner);
    } elseif ($category === "recovery") {
        $file = $testPath . "RecoveryTests.php";
        if (!file_exists($file)) {
            die("Файл не найден: " . $file);
        }
        require_once $file;
        runRecoveryTests($runner);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система тестирования</title>
    <link rel="stylesheet" href="/css/styles.css">

<style>
:root {
    --primary: #42a5f5;
    --primary-dark: #1e88e5;
    --success: #66bb6a;
    --error: #ef5350;

    --bg: #121212;
    --surface: #1e1e1e;
    --surface-elev: #222;

    --text: rgba(255, 255, 255, 0.87);
    --text-secondary: rgba(255, 255, 255, 0.6);
    --border: rgba(255, 255, 255, 0.12);

    --shadow: 0px 2px 6px rgba(0, 0, 0, 0.35);
    --shadow-lg: 0px 6px 18px rgba(0, 0, 0, 0.45);
}

body {
    background: var(--bg);
    min-height: 100vh;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--text);
}

.test-container {
    max-width: 1200px;
    margin: 0 auto;
}

.test-header {
    background: var(--surface-elev);
    padding: 24px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-lg);
}

.test-header h1 {
    margin: 0 0 16px 0;
    font-size: 32px;
    color: var(--text);
}

.test-header p {
    color: var(--text-secondary);
    margin: 8px 0;
}

.test-categories {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin: 20px 0;
}

.category-btn {
    padding: 16px;
    border-radius: 8px;
    border: 2px solid var(--border);
    background: var(--surface);
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    text-align: center;
    font-weight: 600;
    color: var(--text);
}

.category-btn:hover {
    border-color: var(--primary);
    background: rgba(66, 165, 245, 0.1);
}

.category-btn.active {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
}

.category-info {
    color: var(--text-secondary);
    font-size: 14px;
    margin-top: 8px;
}

.test-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
    margin: 20px 0;
}

.stat {
    background: var(--surface);
    padding: 12px;
    border-radius: 6px;
    border-left: 4px solid var(--primary);
}

.stat-value {
    font-size: 28px;
    font-weight: bold;
    color: var(--text);
}

.stat-label {
    font-size: 12px;
    color: var(--text-secondary);
    margin-top: 4px;
}

.stat.passed {
    border-left-color: var(--success);
}

.stat.failed {
    border-left-color: var(--error);
}

.btn-run {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-run:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.test-results {
    background: var(--surface);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: var(--shadow);
    margin-top: 20px;
}

.test-item {
    padding: 16px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--surface);
}

.test-item.passed {
    background: rgba(102, 187, 106, 0.15);
}

.test-item.failed {
    background: rgba(239, 83, 80, 0.15);
}

.test-name {
    font-weight: 500;
    color: var(--text);
}

.test-message.error {
    font-size: 12px;
    color: var(--error);
    margin-top: 4px;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge.passed {
    background: rgba(102, 187, 106, 0.2);
    color: #ffffff; /* текст белый */
}

.badge.failed {
    background: rgba(239, 83, 80, 0.2);
    color: #991b1b;
}

.test-time {
    font-size: 12px;
    color: var(--text-secondary);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-secondary);
    background: var(--surface);
    border-radius: 8px;
}
</style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1>🧪 Система тестирования портфолио</h1>
            <p style="color: #6b7280; margin: 8px 0;">Выбери категорию тестов и запусти тестирование</p>
            
            <div class="test-categories">
                <a href="?cat=validation" class="category-btn <?= $category === 'validation' ? 'active' : '' ?>">
                    ✓ Валидация
                    <div class="category-info">10 тестов</div>
                </a>
                <a href="?cat=login" class="category-btn <?= $category === 'login' ? 'active' : '' ?>">
                    🔐 Вход
                    <div class="category-info">10 тестов</div>
                </a>
                <a href="?cat=recovery" class="category-btn <?= $category === 'recovery' ? 'active' : '' ?>">
                    🔄 Восстановление
                    <div class="category-info">15 тестов</div>
                </a>
            </div>

            <?php if ($runTests): ?>
            <div class="test-stats">
                <div class="stat">
                    <div class="stat-value"><?= $runner->stats["total"] ?></div>
                    <div class="stat-label">Всего тестов</div>
                </div>
                <div class="stat passed">
                    <div class="stat-value"><?= $runner->stats["passed"] ?></div>
                    <div class="stat-label">Пройдено</div>
                </div>
                <div class="stat <?= $runner->stats["failed"] > 0 ? 'failed' : '' ?>">
                    <div class="stat-value"><?= $runner->stats["failed"] ?></div>
                    <div class="stat-label">Ошибок</div>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" style="margin-top: 16px;">
                <button type="submit" name="run_tests" class="btn-run">▶ Запустить тесты</button>
            </form>
        </div>

        <?php if ($runTests && count($runner->results) > 0): ?>
        <div class="test-results">
            <?php foreach ($runner->results as $result): ?>
            <div class="test-item <?= $result["status"] ?>">
                <div>
                    <div class="test-name"><?= $result["status"] === "passed" ? "✓" : "✗" ?> <?= htmlspecialchars($result["name"]) ?></div>
                    <?php if ($result["status"] === "failed"): ?>
                        <div class="test-message error"><?= htmlspecialchars($result["message"]) ?></div>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <span class="badge <?= $result["status"] ?>"><?= $result["status"] === "passed" ? "OK" : "FAIL" ?></span>
                    <div class="test-time"><?= $result["time"] ?>ms</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php elseif (!$runTests): ?>
        <div class="empty-state">
            <h2>Выбери категорию и запусти тесты</h2>
            <p>Система запустит все тесты и покажет результаты</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
