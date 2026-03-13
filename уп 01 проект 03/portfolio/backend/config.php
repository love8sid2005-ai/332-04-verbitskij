<?php
/**
 * Файл конфигурации и общих функций (config.php)
 * Содержит базовые настройки, инициализацию базы данных, управление сессиями и валидацию.
 */

// 1. КОНФИГУРАЦИЯ
// Путь к файлу базы данных SQLite
define("DB_PATH", __DIR__ . "/database.db");

// Инициализация сессии
session_start();

// 2. РАБОТА С БАЗОЙ ДАННЫХ

/**
 * Устанавливает соединение с базой данных SQLite.
 * @return PDO
 */
function getDB(): PDO {
    // Создаем новый объект PDO для подключения к SQLite
    $db = new PDO("sqlite:" . DB_PATH);
    // Устанавливаем режим обработки ошибок: выброс исключений
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

/**
 * Создает таблицу users, если файл базы данных не существует.
 * Вызывается при первом запуске приложения.
 */
function initDatabase(): void {
    // Если файл базы данных уже существует, выходим
    if (file_exists(DB_PATH)) return;

    try {
        $db = getDB();
        // Запрос на создание таблицы users
        $db->exec("CREATE TABLE users(
            id INTEGER PRIMARY KEY AUTOINCREMENT, 
            phone TEXT UNIQUE NOT NULL, 
            password TEXT NOT NULL, 
            secret_question TEXT NOT NULL, 
            secret_answer TEXT NOT NULL, 
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (PDOException $e) {
        // В реальном приложении здесь должно быть логирование ошибки
        error_log("Ошибка инициализации базы данных: " . $e->getMessage());
        // Можно не прерывать выполнение, если это не критично
    }
}

// 3. ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ АУТЕНТИФИКАЦИИ И СЕССИИ

/**
 * Выполняет HTTP-перенаправление на указанный путь.
 * @param string $p Путь для перенаправления.
 */
function redirect(string $p): void {
    header("Location: " . $p);
    exit;
}

/**
 * Проверяет, авторизован ли пользователь.
 * @return bool
 */
function checkAuth(): bool {
    // Проверяем наличие и непустое значение 'user_id' в сессии
    return isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]);
}

/**
 * Получает данные текущего авторизованного пользователя.
 * @return array|null Данные пользователя (id, phone, created_at) или null, если не авторизован.
 */
function getCurrentUser(): ?array {
    if (!checkAuth()) return null;

    $db = getDB();
    // Извлекаем только безопасные данные (без пароля и секретного ответа)
    $stmt = $db->prepare("SELECT id, phone, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION["user_id"]]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 4. ФУНКЦИИ ВАЛИДАЦИИ И ФОРМАТИРОВАНИЯ

/**
 * Нормализует номер телефона, добавляя префикс "+7", если его нет.
 * @param string $p Номер телефона.
 * @return string Нормализованный номер.
 */
function normalizePhone(string $p): string {
    $p = trim($p);
    // Если номер не начинается с "+7" и не пуст, добавляем префикс
    if (strpos($p, "+7") !== 0 && !empty($p)) {
        $p = "+7" . $p;
    }
    return $p;
}

/**
 * Валидирует формат номера телефона (должен быть строго "+7" и 10 цифр после него).
 * @param string $p Номер телефона.
 * @return bool
 */
function validatePhone(string $p): bool {
    // Проверка с помощью регулярного выражения: начало с +7 и ровно 10 цифр
    return preg_match("/^\+7\d{10}$/", $p) === 1;
}

/**
 * Валидирует длину пароля (от 6 до 10 символов).
 * @param string $p Пароль.
 * @return bool
 */
function validatePassword(string $p): bool {
    $length = strlen($p);
    return $length >= 6 && $length <= 10;
}

/**
 * Возвращает список доступных секретных вопросов.
 * @return string[]
 */
function getSecretQuestions(): array {
    return [
        "Как зовут вашего первого домашнего питомца?", 
        "В каком городе вы родились?", 
        "Какой ваш любимый цвет?"
    ];
}

// 5. ИНИЦИАЛИЗАЦИЯ
// Вызов функции инициализации БД (если файл БД не существует)
initDatabase();
?>
