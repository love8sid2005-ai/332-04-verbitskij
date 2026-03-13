<?php
/**
 * Файл логики аутентификации (auth.php)
 * Содержит функции для регистрации, входа, восстановления и сброса пароля.
 */
require_once "config.php";

function registerUser(string $ph, string $pw, string $q, string $a): array {
    // 1. Валидация и нормализация данных
    $ph = normalizePhone($ph);
    if (!validatePhone($ph)) return ["success" => false, "error" => "Неверный формат номера"];
    if (!validatePassword($pw)) return ["success" => false, "error" => "Пароль должен быть 6-10 символов"];
    if (empty($a)) return ["success" => false, "error" => "Ответ не может быть пустым"];
    
    // 2. Проверка уникальности номера телефона
    $db = getDB();
    $statement = $db->prepare("SELECT id FROM users WHERE phone = ?");
    $statement->execute([$ph]);
    if ($statement->fetch()) {
        return ["success" => false, "error" => "Пользователь с таким номером уже существует"];
    }
    
    // 3. Хеширование паролей и ответов
    $hashed_password = password_hash($pw, PASSWORD_DEFAULT);
    // Секретный ответ приводится к нижнему регистру перед хешированием для регистронезависимой проверки
    $hashed_answer = password_hash(strtolower($a), PASSWORD_DEFAULT);
    
    // 4. Вставка данных в БД
    try {
        $statement = $db->prepare("INSERT INTO users(phone, password, secret_question, secret_answer) VALUES(?, ?, ?, ?)");
        $statement->execute([$ph, $hashed_password, $q, $hashed_answer]);
        return ["success" => true];
    } catch (PDOException $e) {
        // Логируем ошибку, но пользователю показываем общее сообщение
        error_log("Ошибка при регистрации: " . $e->getMessage());
        return ["success" => false, "error" => "Ошибка регистрации"];
    }
}

/**
 * Выполняет вход пользователя в систему.
 * @param string $ph Номер телефона.
 * @param string $pw Пароль.
 * @return array Результат операции ("success" => bool, "error" => string)
 */
function loginUser(string $ph, string $pw): array {
    // 1. Валидация номера
    $ph = normalizePhone($ph);
    if (!validatePhone($ph)) {
        // Общее сообщение для предотвращения утечки информации
        return ["success" => false, "error" => "Неверный пароль или пользователь не найден"];
    }
    
    // 2. Поиск пользователя в БД
    $db = getDB();
    $statement = $db->prepare("SELECT id, password FROM users WHERE phone = ?");
    $statement->execute([$ph]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    
    // 3. Проверка существования пользователя
    if (!$user) {
        // Общее сообщение
        return ["success" => false, "error" => "Неверный пароль или пользователь не найден"];
    }
    
    // 4. Проверка пароля
    if (!password_verify($pw, $user["password"])) {
        // Общее сообщение
        return ["success" => false, "error" => "Неверный пароль или пользователь не найден"];
    }
    
    // 5. Успешный вход: создание сессии
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["phone"] = $ph; // Храним номер для удобства
    return ["success" => true];
}

/**
 * Инициирует процесс восстановления пароля (первый этап).
 * @param string $ph Номер телефона.
 * @return array Результат операции ("success" => bool, "error" => string, "question" => string)
 */
function recoverUser(string $ph): array {
    // 1. Валидация номера
    $ph = normalizePhone($ph);
    if (!validatePhone($ph)) {
        // Общее сообщение
        return ["success" => false, "error" => "Пользователь не найден"];
    }
    
    // 2. Поиск пользователя и секретного вопроса
    $db = getDB();
    $statement = $db->prepare("SELECT id, secret_question FROM users WHERE phone = ?");
    $statement->execute([$ph]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    
    // 3. Проверка существования пользователя
    if (!$user) {
        // Общее сообщение
        return ["success" => false, "error" => "Пользователь не найден"];
    }
    
    // 4. Успех: сохранение ID для второго этапа и возврат вопроса
    $_SESSION["recover_user_id"] = $user["id"];
    // Возвращаем true и вопрос
    return ["success" => true, "question" => $user["secret_question"]];
}

/**
 * Сбрасывает пароль пользователя (второй этап).
 * @param string $pw Новый пароль.
 * @param string $a Секретный ответ (кодовое слово).
 * @return array Результат операции ("success" => bool, "error" => string)
 */
function resetPassword(string $pw, string $a): array {
    // 1. Проверка сессии восстановления
    if (!isset($_SESSION["recover_user_id"])) {
        return ["success" => false, "error" => "Сессия восстановления истекла. Начните заново."];
    }
    
    // 2. Валидация нового пароля
    if (!validatePassword($pw)) {
        return ["success" => false, "error" => "Новый пароль должен быть 6-10 символов"];
    }
    
    $user_id = $_SESSION["recover_user_id"];
    $db = getDB();
    
    // 3. Получение хешированного секретного ответа из БД
    $statement = $db->prepare("SELECT secret_answer FROM users WHERE id = ?");
    $statement->execute([$user_id]);
    $user_data = $statement->fetch(PDO::FETCH_ASSOC);

    // 4. Проверка секретного ответа
    // Сравниваем введенный ответ (приведенный к нижнему регистру) с хешем из БД
    if (!password_verify(strtolower($a), $user_data["secret_answer"])) {
        return ["success" => false, "error" => "Неверный кодовое слово"];
    }
    
    // 5. Обновление пароля
    $hashed_password = password_hash($pw, PASSWORD_DEFAULT);
    $statement = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $statement->execute([$hashed_password, $user_id]);
    
    // 6. Очистка сессии восстановления
    unset($_SESSION["recover_user_id"]);
    
    return ["success" => true];
}

/**
 * Завершает сессию пользователя (выход).
 * @return bool
 */
function logout(): bool {
    // Удаление всех данных сессии
    session_destroy();
    return true;
}
?>
