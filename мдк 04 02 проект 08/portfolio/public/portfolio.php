<?php
require_once "../backend/config.php";

if (!checkAuth()) {
    redirect("/login.php");
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мое портфолио</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body style="background: #121212; min-height: 100vh; display: block; padding-top: 20px;">
    <div class="portfolio-container">
        <div class="portfolio-header">
            <h1>Добро пожаловать в портфолио!</h1>
            <a href="/logout.php" class="btn btn-logout">Выход</a>
        </div>

        <div class="user-info">
            <p><strong>Номер телефона:</strong> <?= htmlspecialchars($user["phone"]) ?></p>
            <p><strong>Зарегистрирован:</strong> <?= $user["created_at"] ?></p>
        </div>

        <div class="section">
            <h2>Мои навыки</h2>
            <ul class="skills-list">
                <li>✓ PHP 7+</li>
                <li>✓ JavaScript / DOM API</li>
                <li>✓ CSS3</li>
                <li>✓ SQLite / MySQL</li>
                <li>✓ Git / Version Control</li>
                <li>✓ Linux / Unix</li>
            </ul>
        </div>

        <div class="section">
            <h2>Мои проекты</h2>
            <div class="projects-grid">
                <div class="project-card">
                    <h3>Система аутентификации</h3>
                    <p>Регистрация и вход по номеру телефона с защитой от SQL-инъекций</p>
                </div>
                <div class="project-card">
                    <h3>Восстановление доступа</h3>
                    <p>Двухэтапное восстановление пароля через секретный вопрос</p>
                </div>
                <div class="project-card">
                    <h3>Защита данных</h3>
                    <p>Хэширование паролей, подготовленные SQL-запросы, валидация</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
