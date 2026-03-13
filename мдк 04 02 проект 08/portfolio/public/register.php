<?php
require_once "../backend/config.php";
require_once "../backend/auth.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $phone = $_POST["phone"] ?? "";
    $password = $_POST["password"] ?? "";
    $password_confirm = $_POST["password_confirm"] ?? "";
    $secret_question = $_POST["secret_question"] ?? "";
    $secret_answer = $_POST["secret_answer"] ?? "";

    if ($password !== $password_confirm) {
        $error = "Пароли не совпадают";
    } else {
        $result = registerUser($phone, $password, $secret_question, $secret_answer);
        if ($result["success"]) {
            $success = "Регистрация успешна! Перенаправление...";
            header("Refresh: 2; url=/login.php");
        } else {
            $error = $result["error"];
        }
    }
}

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
            <p class="subtitle">Создайте новый аккаунт</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (empty($success)): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="phone">Номер телефона</label>
                    <div class="phone-input">
                        <span>+7</span>
                        <input type="tel" id="phone" name="phone" placeholder="9221110500" required maxlength="10" pattern="[0-9]{10}">
                    </div>
                    <small>10 цифр (например: 9221110500)</small>
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" placeholder="••••••" required>
                    <small>6-10 символов</small>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Подтверждение пароля</label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="••••••" required>
                </div>

                <div class="form-group">
                    <label for="secret_question">Секретный вопрос</label>
                    <select id="secret_question" name="secret_question" required>
                        <option value="">-- Выберите вопрос --</option>
                        <?php foreach ($questions as $q): ?>
                            <option value="<?= htmlspecialchars($q) ?>"><?= htmlspecialchars($q) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="secret_answer">Ответ на вопрос</label>
                    <input type="text" id="secret_answer" name="secret_answer" placeholder="Ваш ответ" required>
                </div>

                <button type="submit" class="btn">Зарегистрироваться</button>
            </form>
            <?php endif; ?>

            <div class="links">
                <a href="/login.php">Уже есть аккаунт?</a>
            </div>
        </div>
    </div>
</body>
</html>
