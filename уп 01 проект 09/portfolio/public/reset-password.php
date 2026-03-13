<?php
// Подключение конфигурационных файлов и логики аутентификации
require_once "../backend/config.php";
require_once "../backend/auth.php";

// Проверка, что сессия восстановления была инициирована на первом этапе
if (!isset($_SESSION["recover_user_id"])) {
    // Если recover_user_id отсутствует, пользователь не прошел первый этап
    redirect("/recover.php");
}

$error = "";
$success = "";

// Обработка POST-запроса (отправка формы)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_password = $_POST["new_password"] ?? "";
    $new_password_confirm = $_POST["new_password_confirm"] ?? "";

    // Проверка совпадения нового пароля и его подтверждения
    if ($new_password !== $new_password_confirm) {
        $error = "Пароли не совпадают";
    } else {
        // Попытка сброса пароля с использованием секретного ответа
        $result = resetPassword(
            $new_password,
            $_POST["secret_answer"] ?? ""
        );
        
        if ($result["success"]) {
            // Успешный сброс
            $success = "Пароль изменен! Вы будете перенаправлены на страницу входа.";
            // Перенаправление на страницу входа через 2 секунды
            header("Refresh: 2; url=/login.php");
        } else {
            // Неудачный сброс: сохраняем сообщение об ошибке (например, неверный ответ)
            $error = $result["error"];
        }
    }
}

// Получение секретного вопроса пользователя для отображения в форме
$db = getDB();
$statement = $db->prepare("SELECT secret_question FROM users WHERE id=?");
$statement->execute([$_SESSION["recover_user_id"]]);
$user_data = $statement->fetch(PDO::FETCH_ASSOC);

// Проверка на случай, если пользователь вдруг был удален
if (!$user_data) {
    $error = "Ошибка: Пользователь для восстановления не найден.";
    // Очистка сессии восстановления
    unset($_SESSION["recover_user_id"]);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сброс пароля</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Сброс пароля</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Вопрос</label>
                        <p style="padding:10px;background:#f3f4f6;border-radius:6px">
                            <?= htmlspecialchars($user_data["secret_question"] ?? 'Неизвестный вопрос') ?>
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label>Ответ (кодовое слово)</label>
                        <input type="text" name="secret_answer" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Новый пароль</label>
                        <input type="password" name="new_password" required>
                        <small>6-10 символов</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Подтверждение</label>
                        <input type="password" name="new_password_confirm" required>
                    </div>
                    
                    <button class="btn">Изменить</button>
                </form>
            <?php endif; ?>
            
            <div class="links">
                <a href="/login.php">Вернуться</a>
            </div>
        </div>
    </div>
</body>
</html>
