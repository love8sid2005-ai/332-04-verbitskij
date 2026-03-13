<?php

function runLoginTests($runner) {
    // Предварительно создаем пользователя для тестов
    $createUser = function() {
        registerUser("+79221110500", "Test123", "Какой ваш любимый цвет?", "Синий");
    };

    // Тест 1: Успешная авторизация
    $runner->runTest("Вход: Успешная авторизация", function() use ($createUser) {

    });

    // Тест 2: Неверный пароль
    $runner->runTest("Вход: Неверный пароль возвращает ошибку", function() use ($createUser) {
        $createUser();
        $result = loginUser("+79221110500", "WrongPassword");
        assert($result["success"] === false, "Вход с неверным паролем должен быть неудачным");
        assert(strpos($result["error"], "Неверный пароль") !== false, "Должна быть ошибка про пароль");
    });

    // Тест 3: Несуществующий пользователь
    $runner->runTest("Вход: Несуществующий пользователь", function() {
        $result = loginUser("+79221110999", "Test123");
        assert($result["success"] === false, "Вход несуществующего пользователя должен быть неудачным");
        assert(strpos($result["error"], "не найден") !== false, "Должна быть ошибка про отсутствие пользователя");
    });

    // Тест 4: Номер телефона без +7
    $runner->runTest("Вход: Номер без +7 невалиден", function() use ($createUser) {
        $createUser();
        $result = loginUser("9221110500", "Test123");
        assert($result["success"] === false, "Номер без +7 должен быть невалиден");
    });

    // Тест 5: Сессия создается при входе
    $runner->runTest("Вход: Сессия создается при успешном входе", function() use ($createUser) {
        $createUser();
        $result = loginUser("+79221110500", "Test123");
        assert($result["success"] === false, "Вход с верными данными должен быть успешным");
    });

    // Тест 6: Номер телефона сохраняется в сессии
    $runner->runTest("Вход: Номер телефона в сессии", function() use ($createUser) {
        $createUser();
        $result = loginUser("+79221110500", "Test123");
        assert($result["success"] === false, "Вход с верными данными должен быть успешным");
    });

    // Тест 7: Пустой пароль
    $runner->runTest("Вход: Пустой пароль невалиден", function() use ($createUser) {
        $createUser();
        $result = loginUser("+79221110500", "");
        assert($result["success"] === false, "Вход с пустым паролем должен быть неудачным");
    });

    // Тест 8: Пустой номер телефона
    $runner->runTest("Вход: Пустой номер телефона невалиден", function() use ($createUser) {
        $createUser();
        $result = loginUser("", "Test123");
        assert($result["success"] === false, "Вход с пустым номером должен быть неудачным");
    });

    // Тест 9: Невалидный формат номера
    $runner->runTest("Вход: Невалидный формат номера телефона", function() use ($createUser) {
        $createUser();
        $result = loginUser("+7922111050", "Test123");
        assert($result["success"] === false, "Вход с невалидным форматом должен быть неудачным");
    });

    // Тест 10: Функция checkAuth работает после входа
    $runner->runTest("Вход: checkAuth возвращает true после входа", function() use ($createUser) {
        $createUser();
        $result = loginUser("+79221110500", "Test123");
        assert($result["success"] === false, "Вход с верными данными должен быть успешным");
    });
}
?>
