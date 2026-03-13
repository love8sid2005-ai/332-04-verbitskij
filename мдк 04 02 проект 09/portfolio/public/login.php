<?php
// Подключение конфигурационных файлов
require_once "../backend/config.php";
require_once "../backend/auth.php";

// Если пользователь уже авторизован, перенаправляем на страницу портфолио
if (checkAuth()) {
    redirect("/portfolio.php");
}

$error = "";

// Обработка POST-запроса (отправка формы)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Получаем телефон и пароль, используя оператор объединения с null (??) для безопасности
    $phone = $_POST["phone"] ?? "";
    $password = $_POST["password"] ?? "";
    
    // Попытка входа
    $result = loginUser($phone, $password);

    if ($result["success"]) {
        // Успешный вход: перенаправляем на портфолио
        redirect("/portfolio.php");
    } else {
        // Неудачный вход: сохраняем сообщение об ошибке
        $error = $result["error"];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Вход</h1>
            <p class="subtitle">Войдите в портфолио</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Номер телефона</label>
                    <div class="phone-input">
                        <span>+7</span>
                        <input type="tel" 
                               name="phone" 
                               placeholder="9221110500" 
                               required 
                               maxlength="10" 
                               pattern="[0-9]{10}">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" required>
                </div>
                
                <button class="btn">Войти</button>
            </form>

            <div class="links">
                <a href="/register.php">Регистрация</a>
                <a href="/recover.php">Восстановление</a>
            </div>
        </div>
    </div>
</body>
</html>
