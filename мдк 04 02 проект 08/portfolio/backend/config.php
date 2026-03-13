<?php
define("DB_PATH", __DIR__ . "/database.db");

session_start();

function getDB()
{
    try {
        $db = new PDO("sqlite:" . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die("Ошибка подключения к БД: " . $e->getMessage());
    }
}

function initDatabase()
{
    if (file_exists(DB_PATH)) {
        return;
    }

    $db = new PDO("sqlite:" . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        phone TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        secret_question TEXT NOT NULL,
        secret_answer TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
    ";

    $db->exec($sql);
}

function redirect($path)
{
    header("Location: " . $path);
    exit;
}

function checkAuth()
{
    return isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]);
}

function getCurrentUser()
{
    if (!checkAuth()) {
        return null;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, phone, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION["user_id"]]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function normalizePhone($phone)
{
    $phone = trim($phone);
    if (strpos($phone, "+7") !== 0) {
        $phone = "+7" . $phone;
    }
    return $phone;
}

function validatePhone($phone)
{
    $pattern = "/^\+7\d{10}$/";
    return preg_match($pattern, $phone) === 1;
}

function validatePassword($password)
{
    // Минимум 6 символов, максимум 10
    // Может содержать буквы (любые), цифры, и базовые спецсимволы
    if (strlen($password) < 6 || strlen($password) > 10) {
        return false;
    }
    return true;
}

function getSecretQuestions()
{
    return [
        "Как зовут вашего первого домашнего питомца?",
        "В каком городе вы родились?",
        "Какой ваш любимый цвет?"
    ];
}

initDatabase();
?>
