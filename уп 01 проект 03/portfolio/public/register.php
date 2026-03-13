<?php
// Подключение конфигурационных файлов и логики аутентификации
require_once "../backend/config.php";
require_once "../backend/auth.php";

$error = "";
$success = "";

// Обработка POST-запроса (отправка формы)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Получение данных из POST-запроса, с использованием оператора объединения с null
    $password = $_POST["password"] ?? "";
    $password_confirm = $_POST["password_confirm"] ?? "";
    
    // Проверка совпадения паролей
    if ($password !== $password_confirm) {
        $error = "Пароли не совпадают";
    } else {
        // Попытка регистрации пользователя
        $result = registerUser(
            $_POST["phone"] ?? "",
            $password,
            $_POST["secret_question"] ?? "",
            $_POST["secret_answer"] ?? ""
        );
        
        if ($result["success"]) {
            // Успешная регистрация
            $success = "Успешно! Вы будете перенаправлены на страницу входа.";
            // Перенаправление на страницу входа через 2 секунды
            header("Refresh: 2; url=/login.php");
        } else {
            // Неудачная регистрация: сохраняем сообщение об ошибке
            $error = $result["error"];
        }
    }
}

// Получение списка секретных вопросов для вывода в форму
$questions = getSecretQuestions();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Регистрация</h1>
            <p class="subtitle">Создайте аккаунт</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php else: ?>
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
                    
                    <div class="form-group">
                        <label>Пароль</label>
                        <input type="password" name="password" required>
                        <small>6-10 символов</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Подтверждение</label>
                        <input type="password" name="password_confirm" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Вопрос</label>
                        <select name="secret_question" required>
                            <option>-- Выберите --</option>
                            <?php foreach ($questions as $question): ?>
                                <option value="<?= htmlspecialchars($question) ?>"><?= htmlspecialchars($question) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Ответ</label>
                        <input type="text" name="secret_answer" required>
                    </div>
                    
                    <button class="btn">Зарегистрироваться</button>
                </form>
            <?php endif; ?>
            
            <div class="links">
                <a href="/login.php">Есть аккаунт?</a>
            </div>
        </div>
    </div>
</body>
</html>
