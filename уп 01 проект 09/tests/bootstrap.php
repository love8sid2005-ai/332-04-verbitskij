<?php
/**
 * Bootstrap файл для тестов
 * Инициализирует тестовое окружение
 */

// Включаем вывод ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Определяем, что мы в тестовом окружении
define('TESTING', true);

// Определяем корневую директорию проекта
define('PROJECT_ROOT', dirname(__DIR__));

// Устанавливаем путь к тестовой БД ДО подключения Config.php
$testDbPath = PROJECT_ROOT . '/storage/database/test-database.db';
define('DB_PATH', $testDbPath);

// Загружаем основные файлы проекта
require_once PROJECT_ROOT . '/src/Config.php';

// Создаем тестовую БД если не существует
if (!file_exists($testDbPath)) {
    // Создаем директорию если не существует
    $dbDir = dirname($testDbPath);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    
    // Создаем новую тестовую БД
    try {
        $db = new PDO('sqlite:' . $testDbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            phone TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            secret_question TEXT NOT NULL,
            secret_answer TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (PDOException $e) {
        // Игнорируем ошибки при создании БД
    }
}

/**
 * Получение подключения к тестовой БД
 * @return PDO
 */
function getTestDB() {
    static $db = null;
    
    if ($db === null) {
        $dbPath = defined('DB_PATH') ? DB_PATH : PROJECT_ROOT . '/storage/database/test-database.db';
        try {
            $db = new PDO('sqlite:' . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Ошибка подключения к тестовой БД: " . $e->getMessage());
            throw new RuntimeException("Не удалось подключиться к тестовой БД");
        }
    }
    
    return $db;
}
