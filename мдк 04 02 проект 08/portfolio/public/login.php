<?php
require_once "../backend/config.php";
require_once "../backend/auth.php";

if (checkAuth()) {
    redirect("/portfolio.php");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $phone = $_POST["phone"] ?? "";
    $password = $_POST["password"] ?? "";

    $result = loginUser($phone, $password);
    if ($result["success"]) {
        redirect("/portfolio.php");
    } else {
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
            <p class="subtitle">Войдите в свое портфолио</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="phone">Номер телефона</label>
                    <div class="phone-input">
                        <span>+7</span>
                        <input type="tel" id="phone" name="phone" placeholder="9221110500" required maxlength="10" pattern="[0-9]{10}">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" placeholder="••••••" required>
                </div>

                <button type="submit" class="btn">Войти</button>
            </form>

            <div class="links">
                <a href="/register.php">Регистрация</a>
                <a href="/recover.php">Забыли пароль?</a>
            </div>
        </div>
    </div>
</body>
</html>
