<?php
/**
 * Скрипт выхода из системы
 * Уничтожает сессию и перенаправляет на страницу входа
 */

require_once 'config.php';

// Уничтожение всех данных сессии
$_SESSION = [];

// Удаление cookie сессии
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Уничтожение сессии
session_destroy();

// Редирект на страницу входа
redirect('login.php');

