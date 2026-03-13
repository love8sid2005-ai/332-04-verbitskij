<?php
require_once "../backend/config.php";
require_once "../backend/auth.php";

if (!isset($_SESSION["recover_user_id"])) {
    redirect("/recover.php");
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $secret_answer = $_POST["secret_answer"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $new_password_confirm = $_POST["new_password_confirm"] ?? "";

    if ($new_password !== $new_password_confirm) {
        $error = "Пароли не совпадают";
    } else {
        $result = resetPassword($new_password, $secret_answer);
        if ($result["success"]) {
            $success = "Пароль изменен! Перенаправление...";
            header("Refresh: 2; url=/login.php");
        } else {
            $error = $result["error"];
        }
    }
}

$db = getDB();
$stmt = $db->prepare("SELECT secret_question FROM users WHERE id = ?");
$stmt->execute([$_SESSION["recover_user_id"]]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сброс пароля</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Сброс пароля</h1>
            <p class="subtitle">Ответьте на секретный вопрос</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (empty($success)): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Секретный вопрос</label>
                    <p>
                        <?= htmlspecialchars($user["secret_question"]) ?>
                    </p>
                </div>

                <div class="form-group">
                    <label for="secret_answer">Ответ</label>
                    <input type="text" id="secret_answer" name="secret_answer" placeholder="Ваш ответ" required>
                </div>

                <div class="form-group">
                    <label for="new_password">Новый пароль</label>
                    <input type="password" id="new_password" name="new_password" placeholder="••••••" required>
                    <small>6-10 символов</small>
                </div>

                <div class="form-group">
                    <label for="new_password_confirm">Подтверждение пароля</label>
                    <input type="password" id="new_password_confirm" name="new_password_confirm" placeholder="••••••" required>
                </div>

                <button type="submit" class="btn">Изменить пароль</button>
            </form>
            <?php endif; ?>

            <div class="links">
                <a href="/login.php">Вернуться</a>
            </div>
        </div>
    </div>
</body>
</html>
