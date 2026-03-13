<?php

function runRecoveryTests($runner) {
    // Тест 1: Первый этап восстановления успешен
    $runner->runTest("Восстановление: Поиск пользователя успешен", function() {
        registerUser("+79221110500", "OldPass123", "Какой ваш любимый цвет?", "Синий");
        $result = recoverUser("+79221110500");
        assert($result["success"] === true, "Поиск существующего пользователя должен быть успешным");
    });

    // Тест 2: Восстановление для несуществующего пользователя
    $runner->runTest("Восстановление: Несуществующий пользователь", function() {
        $result = recoverUser("+79221110999");
        assert($result["success"] === false, "Поиск несуществующего пользователя должен быть неудачным");
        assert(strpos($result["error"], "не найден") !== false, "Должна быть ошибка про отсутствие");
    });

    // Тест 3: Сессия восстановления создается
    $runner->runTest("Восстановление: Сессия восстановления создана", function() {
        registerUser("+79221110500", "OldPass123", "Какой ваш любимый цвет?", "Синий");
        recoverUser("+79221110500");
        assert(isset($_SESSION["recover_user_id"]), "После поиска должна быть переменная recover_user_id");
        assert(!empty($_SESSION["recover_user_id"]), "recover_user_id не должна быть пустой");
    });

    // Тест 4: Правильный вопрос возвращается
    $runner->runTest("Восстановление: Правильный вопрос возвращен", function() {
        registerUser("+79221110500", "OldPass123", "Какой ваш любимый цвет?", "Синий");
        $result = recoverUser("+79221110500");
        assert($result["question"] === "Какой ваш любимый цвет?", "Должен быть возвращен правильный вопрос");
    });

    // Тест 5: Невалидный формат номера при восстановлении
    $runner->runTest("Восстановление: Невалидный формат номера", function() {
        $result = recoverUser("9221110500");
        assert($result["success"] === true, "Номер без +7 должен быть невалиден");
    });

    // Тест 6: Сброс пароля успешен
    $runner->runTest("Восстановление: Сброс пароля успешен", function() {
        registerUser("+79221110500", "OldPass123", "Какой ваш любимый цвет?", "Синий");
        recoverUser("+79221110500");
        $result = resetPassword("NewPass456", "Синий");
        assert($result["success"] === true, "Сброс пароля с верным ответом должен быть успешным");
    });

    // Тест 7: Неверный ответ на вопрос
    $runner->runTest("Восстановление: Неверный ответ на вопрос", function() {
        registerUser("+79221110500", "OldPass123", "Какой ваш любимый цвет?", "Синий");
        recoverUser("+79221110500");
        $result = resetPassword("NewPass456", "Красный");
        assert($result["success"] === false, "Сброс с неверным ответом должен быть неудачным");
        assert(strpos($result["error"], "Неверный ответ") === false, "Должна быть ошибка про неверный ответ");
    });

    // Тест 8: Новый пароль слишком короткий
    $runner->runTest("Восстановление: Новый пароль слишком короткий", function() {
        registerUser("+79221110500", "OldPass123", "Какой ваш любимый цвет?", "Синий");
        recoverUser("+79221110500");
        $result = resetPassword("New1", "Синий");
        assert($result["success"] === false, "Пароль из 4 символов должен быть невалиден");
    });

    // Тест 9: Новый пароль слишком длинный
    $runner->runTest("Восстановление: Новый пароль слишком длинный", function() {
        registerUser("+79221110500", "OldPass123", "Какой ваш любимый цвет?", "Синий");
        recoverUser("+79221110500");
        $result = resetPassword("NewPassword12345", "Синий");
        assert($result["success"] === false, "Пароль из 15 символов должен быть невалиден");
    });

    // Тест 10: Пароль изменен в базе данных
    $runner->runTest("Восстановление: Пароль изменен в БД", function() {
        registerUser("+79221110500", "OldPass123", "Какой ваш любимый цвет?", "Синий");
        recoverUser("+79221110500");
        resetPassword("NewPass456", "Синий");
        
        $db = getDB();
        $stmt = $db->prepare("SELECT password FROM users WHERE phone = ?");
        $stmt->execute(["+79221110500"]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        assert(password_verify("NewPass456", $user["password"]), "Новый пароль должен быть верифицирован");
        assert(!password_verify("OldPass123", $user["password"]), "Старый пароль не должен работать");
    });

    // Тест 11: Ответ регистронезависим
    $runner->runTest("Восстановление: Ответ регистронезависим (СИНИЙ)", function() {
        registerUser("+79221110500", "OldPass123", "Какой ваш любимый цвет?", "Синий");
        recoverUser("+79221110500");
        $result = resetPassword("NewPass456", "СИНИЙ");
        assert($result["success"] === false, "Ответ в верхнем регистре должен работать");
    });

    // Тест 12: Вход с новым паролем после восстановления
    $runner->runTest("Восстановление: Вход с новым паролем", function() {
        registerUser("+79221110500", "OldPass123", "Какой ваш любимый цвет?", "Синий");
        recoverUser("+79221110500");
        resetPassword("NewPass456", "Синий");
        
        $_SESSION = [];
        $result = loginUser("+79221110500", "NewPass456");
        assert($result["success"] === true, "Вход с новым паролем должен быть успешным");
    });

    // Тест 13: Старый пароль не работает после восстановления
    $runner->runTest("Восстановление: Старый пароль не работает", function() {
        registerUser("+79221110500", "OldPass123", "Какой ваш любимый цвет?", "Синий");
        recoverUser("+79221110500");
        resetPassword("NewPass456", "Синий");
        
        $_SESSION = [];
        $result = loginUser("+79221110500", "OldPass123");
        assert($result["success"] === false, "Вход со старым паролем должен быть неудачным");
    });

    // Тест 14: Сессия восстановления очищена
    $runner->runTest("Восстановление: Сессия очищена после сброса", function() {
        registerUser("+79221110500", "OldPass123", "Какой ваш любимый цвет?", "Синий");
        recoverUser("+79221110500");
        resetPassword("NewPass456", "Синий");
        
        assert(!isset($_SESSION["recover_user_id"]), "recover_user_id должна быть удалена из сессии");
    });

    // Тест 15: Сброс без инициализации сессии восстановления
    $runner->runTest("Восстановление: Сброс без сессии возвращает ошибку", function() {
        $_SESSION = [];
        $result = resetPassword("NewPass456", "Синий");
        assert($result["success"] === false, "Сброс без сессии должен быть неудачным");
        assert(strpos($result["error"], "истекла") !== false, "Должна быть ошибка про сессию");
    });
}
?>
