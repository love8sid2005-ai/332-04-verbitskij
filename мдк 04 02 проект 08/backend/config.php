<?php
/**
 * Конфигурационный файл системы аутентификации
 * Содержит функции для работы с БД, валидации и безопасности
 */

// Настройки сессии для безопасности
// Не запускаем сессии в CLI режиме (для тестов)
if (php_sapi_name() !== 'cli' && !defined('TESTING')) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Установить в 1 для HTTPS
    session_start();
}

// Путь к базе данных (определяется только если еще не определена)
// Если DB_PATH уже определена (например, в bootstrap.php для тестов), используем её
if (!defined('DB_PATH')) {
    define('DB_PATH', __DIR__ . '/database.db');
}

/**
 * Инициализация базы данных
 * Создает БД и таблицу users, если они не существуют
 */
function initDatabase() {
    try {
        $db = getDB();
        
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            phone TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            secret_question TEXT NOT NULL,
            secret_answer TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $db->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Ошибка инициализации БД: " . $e->getMessage());
        return false;
    }
}

/**
 * Получение PDO-подключения к базе данных
 * @return PDO
 */
function getDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            $db = new PDO('sqlite:' . DB_PATH);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Ошибка подключения к БД: " . $e->getMessage());
            die("Ошибка подключения к базе данных");
        }
    }
    
    return $db;
}

/**
 * Редирект на указанный URL
 * @param string $url
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Проверка авторизации пользователя
 * @return bool|array Возвращает false или данные пользователя
 */
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, phone, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            return $user;
        }
        
        // Если пользователь не найден, очищаем сессию
        unset($_SESSION['user_id']);
        return false;
    } catch (PDOException $e) {
        error_log("Ошибка проверки авторизации: " . $e->getMessage());
        return false;
    }
}

/**
 * Валидация номера телефона
 * Поддерживает форматы: +7-XXX-XXX-XX-XX, 8-XXX-XXX-XX-XX, +79991234567, 89991234567
 * @param string $phone
 * @return bool
 */
function validatePhone($phone) {
    if (empty($phone)) {
        return false;
    }
    
    // Удаляем все пробелы
    $phone = str_replace(' ', '', $phone);
    
    // Проверяем формат +7-XXX-XXX-XX-XX
    $pattern1 = '/^\+7-\d{3}-\d{3}-\d{2}-\d{2}$/';
    if (preg_match($pattern1, $phone) === 1) {
        return true;
    }
    
    // Проверяем формат 8-XXX-XXX-XX-XX
    $pattern2 = '/^8-\d{3}-\d{3}-\d{2}-\d{2}$/';
    if (preg_match($pattern2, $phone) === 1) {
        return true;
    }
    
    // Проверяем формат +79991234567 (11 цифр после +7)
    $pattern3 = '/^\+7\d{10}$/';
    if (preg_match($pattern3, $phone) === 1) {
        return true;
    }
    
    // Проверяем формат 89991234567 (11 цифр, начинается с 8)
    $pattern4 = '/^8\d{10}$/';
    if (preg_match($pattern4, $phone) === 1) {
        return true;
    }
    
    return false;
}

/**
 * Нормализация номера телефона (приведение к формату +7-XXX-XXX-XX-XX)
 * @param string $phone
 * @return string|false
 */
function normalizePhone($phone) {
    // Удаляем все нецифровые символы кроме +
    $phone = preg_replace('/[^\d+]/', '', $phone);
    
    // Если начинается с 8, заменяем на +7
    if (substr($phone, 0, 1) === '8') {
        $phone = '+7' . substr($phone, 1);
    }
    
    // Если начинается с 7 без +, добавляем +
    if (substr($phone, 0, 1) === '7' && substr($phone, 0, 2) !== '+7') {
        $phone = '+' . $phone;
    }
    
    // Если не начинается с +7, добавляем
    if (substr($phone, 0, 2) !== '+7') {
        $phone = '+7' . $phone;
    }
    
    // Проверяем, что номер содержит 11 цифр после +7
    $digits = substr($phone, 2);
    if (strlen($digits) !== 10) {
        return false;
    }
    
    // Форматируем: +7-XXX-XXX-XX-XX
    $formatted = '+7-' . substr($digits, 0, 3) . '-' . 
                 substr($digits, 3, 3) . '-' . 
                 substr($digits, 6, 2) . '-' . 
                 substr($digits, 8, 2);
    
    return $formatted;
}

/**
 * Валидация пароля
 * Требования: от 8 до 10 символов, только английские буквы и цифры
 * @param string $password
 * @return array ['valid' => bool, 'error' => string]
 */
function validatePassword($password) {
    // Сначала проверяем формат (только английские буквы и цифры)
    // Это важно, чтобы для паролей с русскими буквами возвращалось правильное сообщение
    if (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
        return ['valid' => false, 'error' => 'Пароль должен содержать только английские буквы и цифры'];
    }
    
    // Затем проверяем длину
    if (strlen($password) < 8) {
        return ['valid' => false, 'error' => 'Пароль должен содержать минимум 8 символов'];
    }
    
    if (strlen($password) > 10) {
        return ['valid' => false, 'error' => 'Пароль должен содержать максимум 10 символов'];
    }
    
    // Проверяем наличие букв
    if (!preg_match('/[a-zA-Z]/', $password)) {
        return ['valid' => false, 'error' => 'Пароль должен содержать хотя бы одну букву'];
    }
    
    // Проверяем наличие цифр
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'error' => 'Пароль должен содержать хотя бы одну цифру'];
    }
    
    return ['valid' => true, 'error' => ''];
}

/**
 * Генерация CSRF-токена
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Проверка CSRF-токена
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Получение списка секретных вопросов
 * @return array
 */
function getSecretQuestions() {
    return [
        'mother_maiden_name' => 'Девичья фамилия матери',
        'first_pet' => 'Имя первого домашнего питомца',
        'birth_city' => 'Город рождения',
        'school_name' => 'Название школы',
        'favorite_teacher' => 'Имя любимого учителя',
        'childhood_nickname' => 'Детское прозвище'
    ];
}

