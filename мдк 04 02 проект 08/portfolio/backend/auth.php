<?php
require_once "config.php";

function registerUser($phone, $password, $secret_question, $secret_answer)
{
    $phone = normalizePhone($phone);

    if (!validatePhone($phone)) {
        return ["success" => false, "error" => "Неверный формат номера телефона (10 цифр)"];
    }

    if (!validatePassword($password)) {
        return ["success" => false, "error" => "Пароль должен содержать 6-10 символов"];
    }

    if (empty($secret_answer)) {
        return ["success" => false, "error" => "Ответ на вопрос не может быть пустым"];
    }

    $db = getDB();

    $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        return ["success" => false, "error" => "Пользователь с таким номером уже существует"];
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $hashed_answer = password_hash(strtolower($secret_answer), PASSWORD_DEFAULT);

    try {
        $stmt = $db->prepare("
            INSERT INTO users (phone, password, secret_question, secret_answer)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$phone, $hashed_password, $secret_question, $hashed_answer]);
        return ["success" => true, "message" => "Регистрация успешна"];
    } catch (PDOException $e) {
        return ["success" => false, "error" => "Ошибка регистрации"];
    }
}

function loginUser($phone, $password)
{
    $phone = normalizePhone($phone);

    if (!validatePhone($phone)) {
        return ["success" => false, "error" => "Неверный формат номера"];
    }

    $db = getDB();

    $stmt = $db->prepare("SELECT id, password FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return ["success" => false, "error" => "Пользователь не найден"];
    }

    if (!password_verify($password, $user["password"])) {
        return ["success" => false, "error" => "Неверный пароль"];
    }

    $_SESSION["user_id"] = $user["id"];
    $_SESSION["phone"] = $phone;

    return ["success" => true, "message" => "Вы успешно вошли"];
}

function recoverUser($phone)
{
    $phone = normalizePhone($phone);

    if (!validatePhone($phone)) {
        return ["success" => false, "error" => "Неверный формат номера"];
    }

    $db = getDB();

    $stmt = $db->prepare("SELECT id, secret_question FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return ["success" => false, "error" => "Пользователь не найден"];
    }

    $_SESSION["recover_user_id"] = $user["id"];

    return ["success" => true, "question" => $user["secret_question"]];
}

function resetPassword($new_password, $secret_answer)
{
    if (!isset($_SESSION["recover_user_id"])) {
        return ["success" => false, "error" => "Сессия восстановления истекла"];
    }

    if (!validatePassword($new_password)) {
        return ["success" => false, "error" => "Пароль должен содержать 6-10 символов"];
    }

    $db = getDB();

    $stmt = $db->prepare("SELECT secret_answer FROM users WHERE id = ?");
    $stmt->execute([$_SESSION["recover_user_id"]]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify(strtolower($secret_answer), $user["secret_answer"])) {
        return ["success" => false, "error" => "Неверный ответ"];
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $_SESSION["recover_user_id"]]);

    unset($_SESSION["recover_user_id"]);

    return ["success" => true, "message" => "Пароль изменен"];
}

function logout()
{
    session_destroy();
    return true;
}
?>
