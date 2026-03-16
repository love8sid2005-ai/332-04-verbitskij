<?php
/**
 * Главная страница - доступна только авторизованным пользователям
 */

require_once __DIR__ . '/../src/Config.php';

// Проверка авторизации
$user = checkAuth();
if (!$user) {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная страница</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="container">
        <div class="main-card">
            <header class="main-header">
                <h1>Добро пожаловать!</h1>
                <a href="logout.php" class="btn btn-secondary">Выйти</a>
            </header>
            
            <div class="welcome-section">
                <h2>Привет, <?php echo htmlspecialchars($user['phone']); ?>!</h2>
                <p class="welcome-text">Вы успешно вошли в систему аутентификации.</p>
            </div>
            
            <div class="info-blocks">
                <div class="info-block">
                    <h3>📱 Ваш профиль</h3>
                    <p><strong>Номер телефона:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <p><strong>Дата регистрации:</strong> 
                        <?php 
                        $created_at = new DateTime($user['created_at']);
                        echo $created_at->format('d.m.Y H:i');
                        ?>
                    </p>
                </div>
                
                <div class="info-block">
                    <h3>🔒 Безопасность</h3>
                    <p>Ваши данные защищены современными методами шифрования.</p>
                    <ul>
                        <li>Пароли хранятся в хэшированном виде</li>
                        <li>Используются CSRF-токены</li>
                        <li>Безопасные сессии</li>
                        <li>Подготовленные SQL-запросы</li>
                    </ul>
                </div>
                
                <div class="info-block">
                    <h3>⚙️ Функции системы</h3>
                    <p>Доступные возможности:</p>
                    <ul>
                        <li>Регистрация по номеру телефона</li>
                        <li>Безопасный вход в систему</li>
                        <li>Восстановление пароля через секретный вопрос</li>
                        <li>Защита от несанкционированного доступа</li>
                    </ul>
                </div>
            </div>
            
            <div class="actions-section">
                <a href="change-password.php" class="btn btn-outline">Изменить пароль</a>
            </div>
        </div>
    </div>
</body>
</html>
