<?php
require_once "../backend/config.php";
require_once "../backend/auth.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $phone = $_POST["phone"] ?? "";
    $result = recoverUser($phone);
    if ($result["success"]) {
        redirect("/reset-password.php");
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
    <title>Восстановление</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Восстановление доступа</h1>
            <p class="subtitle">Восстановите доступ к аккаунту</p>

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
                <button type="submit" class="btn">Далее</button>
            </form>

            <div class="links">
                <a href="/login.php">Вернуться</a>
            </div>
        </div>
    </div>
</body>
</html>
