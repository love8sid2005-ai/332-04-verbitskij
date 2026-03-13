<?php

function runRegistrationTests($runner) {
    // Тест 1: Успешная регистрация
    $runner->runTest("Регистрация: Успешная регистрация", function() {
        $result = registerUser("+79221110500", "Test123", "Какой ваш любимый цвет?", "Синий");
        assert($result["success"] === true, "Регистрация должна быть успешной");
    });

    // Тест 2: Невалидный номер телефона
    $runner->runTest("Регистрация: Невалидный номер телефона", function() {
        $result = registerUser("9221110500", "Test123", "Какой ваш любимый цвет?", "Синий");
        assert($result["success"] === false, "Регистрация с невалидным номером должна быть неудачной");
    });

    // Тест 3: Пароль слишком короткий
    $runner->runTest("Регистрация: Пароль слишком короткий", function() {
        $result = registerUser("+79221110500", "Test1", "Какой ваш любимый цвет?", "Синий");
        assert($result["success"] === false, "Регистрация с коротким паролем должна быть неудачной");
    });

    // Тест 4: Пароль слишком длинный
    $runner->runTest("Регистрация: Пароль слишком длинный", function() {
        $result = registerUser("+79221110500", "Test12345678", "Какой ваш любимый цвет?", "Синий");
        assert($result["success"] === false, "Регистрация с длинным паролем должна быть неудачной");
    });

    // Тест 5: Дублирующийся номер телефона
    $runner->runTest("Регистрация: Дублирующийся номер телефона", function() {
        registerUser("+79221110500", "Test123", "Какой ваш любимый цвет?", "Синий");
        $result = registerUser("+79221110500", "Test456", "Какой ваш любимый цвет?", "Красный");
        assert($result["success"] === false, "Регистрация с дублирующимся номером должна быть неудачной");
        assert(strpos($result["error"], "существует") !== false, "Должна быть ошибка про существующего пользователя");
    });

    // Тест 6: Пустой ответ на секретный вопрос
    $runner->runTest("Регистрация: Пустой ответ на вопрос", function() {
        $result = registerUser("+79221110500", "Test123", "Какой ваш любимый цвет?", "");
        assert($result["success"] === false, "Регистрация с пустым ответом должна быть неудачной");
    });

    // Тест 7: Хеширование пароля
    $runner->runTest("Регистрация: Пароль хэшируется в БД", function() {
        registerUser("+79221110500", "Test123", "Какой ваш любимый цвет?", "Синий");
        
        $db = getDB();
        $stmt = $db->prepare("SELECT password FROM users WHERE phone = ?");
        $stmt->execute(["+79221110500"]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        assert($user["password"] !== "Test123", "Пароль не должен храниться в открытом виде");
        assert(password_verify("Test123", $user["password"]), "Пароль должен быть верифицирован");
    });

    // Тест 8: Хеширование ответа на вопрос
    $runner->runTest("Регистрация: Ответ на вопрос хэшируется", function() {
        registerUser("+79221110500", "Test123", "Какой ваш любимый цвет?", "Синий");
        
        $db = getDB();
        $stmt = $db->prepare("SELECT secret_answer FROM users WHERE phone = ?");
        $stmt->execute(["+79221110500"]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        assert($user["secret_answer"] !== "Синий", "Ответ не должен храниться в открытом виде");
        assert(password_verify("синий", $user["secret_answer"]), "Ответ должен быть верифицирован (нижний регистр)");
    });

    // Тест 9: Номер сохраняется в БД
    $runner->runTest("Регистрация: Номер сохранен в БД", function() {
        registerUser("+79221110500", "Test123", "Какой ваш любимый цвет?", "Синий");
        
        $db = getDB();
        $stmt = $db->prepare("SELECT phone FROM users WHERE phone = ?");
        $stmt->execute(["+79221110500"]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        assert($user !== false, "Пользователь должен быть в БД");
        assert($user["phone"] === "+79221110500", "Номер должен быть сохранен корректно");
    });

    // Тест 10: Секретный вопрос сохраняется
    $runner->runTest("Регистрация: Секретный вопрос сохранен", function() {
        $question = "Как зовут вашего первого домашнего питомца?";
        registerUser("+79221110500", "Test123", $question, "Мурзик");
        
        $db = getDB();
        $stmt = $db->prepare("SELECT secret_question FROM users WHERE phone = ?");
        $stmt->execute(["+79221110500"]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        assert($user["secret_question"] === $question, "Вопрос должен быть сохранен корректно");
    });

    // Тест 11: Множество пользователей
    $runner->runTest("Регистрация: Множество пользователей", function() {
        registerUser("+79221110500", "Test123", "Какой ваш любимый цвет?", "Синий");
        registerUser("+79221110501", "Test456", "В каком городе вы родились?", "Москва");
        registerUser("+79221110502", "Test789", "Как зовут вашего первого домашнего питомца?", "Барсик");
        
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        assert($result["count"] === 3, "В БД должно быть 3 пользователя");
    });

    // Тест 12: Граничный случай - пароль 6 символов
    $runner->runTest("Регистрация: Пароль из 6 символов валиден", function() {
        $result = registerUser("+79221110500", "Tes123", "Какой ваш любимый цвет?", "Синий");
        assert($result["success"] === true, "Пароль из 6 символов должен быть валиден");
    });

    // Тест 13: Граничный случай - пароль 10 символов
    $runner->runTest("Регистрация: Пароль из 10 символов валиден", function() {
        $result = registerUser("+79221110500", "Tes1234567", "Какой ваш любимый цвет?", "Синий");
        assert($result["success"] === true, "Пароль из 10 символов должен быть валиден");
    });

    // Тест 14: Ответ на вопрос регистронезависим
    $runner->runTest("Регистрация: Ответ регистронезависим", function() {
        registerUser("+79221110500", "Test123", "Какой ваш любимый цвет?", "СИНИЙ");
        
        $db = getDB();
        $stmt = $db->prepare("SELECT secret_answer FROM users WHERE phone = ?");
        $stmt->execute(["+79221110500"]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        assert(password_verify("синий", $user["secret_answer"]), "Ответ должен быть верифицирован независимо от регистра");
    });

    // Тест 15: Дата создания сохраняется
    $runner->runTest("Регистрация: Дата создания сохранена", function() {
        registerUser("+79221110500", "Test123", "Какой ваш любимый цвет?", "Синий");
        
        $db = getDB();
        $stmt = $db->prepare("SELECT created_at FROM users WHERE phone = ?");
        $stmt->execute(["+79221110500"]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        assert($user["created_at"] !== null, "Дата должна быть сохранена");
        assert(preg_match("/\d{4}-\d{2}-\d{2}/", $user["created_at"]), "Дата должна быть в формате YYYY-MM-DD");
    });
}
?>
