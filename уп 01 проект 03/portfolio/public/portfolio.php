<?php
// Подключение конфигурационных файлов и логики аутентификации
require_once "../backend/config.php";
require_once "../backend/auth.php";

// Проверка аутентификации. Если пользователь не авторизован, перенаправляем на страницу входа.
if (!checkAuth()) {
    redirect("/login.php");
}

// Получение данных текущего пользователя ($u)
$u = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Портфолио</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding-top:20px">
    <div class="portfolio-container">
        <div class="portfolio-header">
            <h1>Добро пожаловать!</h1>
            <a href="/logout.php" class="btn" style="width:auto;padding:8px 16px">Выход</a>
        </div>
        
        <div class="user-info">
            <p><strong>Номер:</strong> <?= htmlspecialchars($u["phone"]) ?></p>
            <p><strong>Дата:</strong> <?= $u["created_at"] ?></p>
        </div>
        
        <div class="section">
            <h2>Навыки</h2>
            <ul class="skills-list">
                <li>✓ PHP 7+</li>
                <li>✓ JavaScript</li>
                <li>✓ CSS3</li>
                <li>✓ SQLite</li>
                <li>✓ Security</li>
            </ul>
        </div>
    </div>
</body>
</html>
