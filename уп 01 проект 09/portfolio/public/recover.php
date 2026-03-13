<?php
// Подключение конфигурационных файлов и логики аутентификации
require_once "../backend/config.php";
require_once "../backend/auth.php";

$error = "";

// Обработка POST-запроса (отправка формы)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Получаем номер телефона из POST-данных, используя оператор объединения с null
    $phone = $_POST["phone"] ?? "";
    
    // Вызываем функцию для начала восстановления доступа
    $result = recoverUser($phone);
    
    if ($result["success"]) {
        // Успешный первый этап: перенаправляем на второй этап (сброс пароля)
        redirect("/reset-password.php");
    } else {
        // Неудачный этап: сохраняем сообщение об ошибке
        $error = $result["error"];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Восстановление</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Восстановление</h1>
            <p class="subtitle">Восстановите доступ</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Номер</label>
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
                
                <button class="btn">Далее</button>
            </form>
            
            <div class="links">
                <a href="/login.php">Вернуться</a>
            </div>
        </div>
    </div>
</body>
</html>
