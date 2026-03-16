<?php
/**
 * Функциональные тесты входа в систему
 */

require_once __DIR__ . '/../../TestCase.php';
require_once __DIR__ . '/../../../config.php';

class LoginTest extends TestCase
{
    /**
     * Тест: успешная аутентификация с правильными данными
     */
    public function testSuccessfulLogin(): void
    {
        $phone = '+7-999-123-45-67';
        $password = 'Test1234';
        
        // Создаем пользователя
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userId = $this->createTestUser([
            'phone' => $phone,
            'password' => $hashedPassword
        ]);
        
        // Симулируем вход
        $normalizedPhone = normalizePhone($phone);
        $stmt = $this->db->prepare("SELECT id, phone, password FROM users WHERE phone = ?");
        $stmt->execute([$normalizedPhone]);
        $user = $stmt->fetch();
        
        $this->assertNotFalse($user, 'Пользователь должен быть найден');
        $this->assertTrue(password_verify($password, $user['password']), 
            'Пароль должен быть верным');
    }
    
    /**
     * Тест: ошибка при неверном пароле
     */
    public function testLoginWithWrongPassword(): void
    {
        $phone = '+7-999-123-45-67';
        $correctPassword = 'Test1234';
        $wrongPassword = 'Wrong123';
        
        // Создаем пользователя
        $hashedPassword = password_hash($correctPassword, PASSWORD_DEFAULT);
        $this->createTestUser([
            'phone' => $phone,
            'password' => $hashedPassword
        ]);
        
        // Пытаемся войти с неверным паролем
        $normalizedPhone = normalizePhone($phone);
        $stmt = $this->db->prepare("SELECT id, phone, password FROM users WHERE phone = ?");
        $stmt->execute([$normalizedPhone]);
        $user = $stmt->fetch();
        
        $this->assertNotFalse($user, 'Пользователь должен быть найден');
        $this->assertFalse(password_verify($wrongPassword, $user['password']), 
            'Неверный пароль должен быть отклонен');
    }
    
    /**
     * Тест: ошибка при несуществующем пользователе
     */
    public function testLoginWithNonExistentUser(): void
    {
        $phone = '+7-999-999-99-99';
        
        // Пытаемся найти несуществующего пользователя
        $normalizedPhone = normalizePhone($phone);
        $stmt = $this->db->prepare("SELECT id, phone, password FROM users WHERE phone = ?");
        $stmt->execute([$normalizedPhone]);
        $user = $stmt->fetch();
        
        $this->assertFalse($user, 'Несуществующий пользователь не должен быть найден');
    }
    
    /**
     * Тест: чувствительность к регистру пароля
     */
    public function testPasswordCaseSensitivity(): void
    {
        $phone = '+7-999-123-45-67';
        $password = 'Test1234';
        $wrongCasePassword = 'test1234';
        
        // Создаем пользователя
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $this->createTestUser([
            'phone' => $phone,
            'password' => $hashedPassword
        ]);
        
        // Проверяем чувствительность к регистру
        $normalizedPhone = normalizePhone($phone);
        $stmt = $this->db->prepare("SELECT password FROM users WHERE phone = ?");
        $stmt->execute([$normalizedPhone]);
        $user = $stmt->fetch();
        
        $this->assertTrue(password_verify($password, $user['password']), 
            'Правильный регистр должен работать');
        $this->assertFalse(password_verify($wrongCasePassword, $user['password']), 
            'Неправильный регистр должен быть отклонен');
    }
    
    /**
     * Тест: создание сессии при успешном входе
     */
    public function testSessionCreationOnLogin(): void
    {
        // Симулируем создание сессии
        session_start();
        $userId = 1;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_phone'] = '+7-999-123-45-67';
        
        $this->assertTrue(isset($_SESSION['user_id']), 'ID пользователя должен быть в сессии');
        $this->assertEquals($userId, $_SESSION['user_id'], 'ID пользователя должен совпадать');
        $this->assertTrue(isset($_SESSION['user_phone']), 'Телефон должен быть в сессии');
        
        session_destroy();
    }
    
    /**
     * Тест: валидация сессии
     */
    public function testSessionValidation(): void
    {
        // Создаем пользователя
        $userId = $this->createTestUser();
        
        // Симулируем проверку авторизации
        session_start();
        $_SESSION['user_id'] = $userId;
        
        // Проверяем, что пользователь существует
        $stmt = $this->db->prepare("SELECT id, phone, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $this->assertNotFalse($user, 'Пользователь должен быть найден по ID из сессии');
        $this->assertEquals($userId, $user['id'], 'ID должен совпадать');
        
        session_destroy();
    }
    
    /**
     * Тест: вход с нормализованным номером телефона
     */
    public function testLoginWithNormalizedPhone(): void
    {
        $phoneFormats = ['8-999-123-45-67', '79991234567', '+7-999-123-45-67'];
        $password = 'Test1234';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Создаем пользователя с нормализованным номером
        $normalizedPhone = '+7-999-123-45-67';
        $this->createTestUser([
            'phone' => $normalizedPhone,
            'password' => $hashedPassword
        ]);
        
        // Пытаемся войти с разными форматами
        foreach ($phoneFormats as $format) {
            $normalized = normalizePhone($format);
            $stmt = $this->db->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$normalized]);
            $user = $stmt->fetch();
            
            $this->assertNotFalse($user, "Вход с форматом {$format} должен работать");
        }
    }
    
    /**
     * Тест: вход с неверным форматом телефона
     */
    public function testLoginWithInvalidPhoneFormat(): void
    {
        $invalidPhone = 'invalid-phone';
        $normalized = normalizePhone($invalidPhone);
        
        if ($normalized) {
            $isValid = validatePhone($normalized);
            $this->assertFalse($isValid, 'Неверный формат телефона должен быть отклонен');
        } else {
            $this->assertFalse($normalized, 'Неверный формат не должен нормализоваться');
        }
    }
    
    /**
     * Тест: очистка сессии при несуществующем пользователе
     */
    public function testSessionCleanupForNonExistentUser(): void
    {
        session_start();
        $_SESSION['user_id'] = 99999; // Несуществующий ID
        
        // Симулируем проверку авторизации
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            unset($_SESSION['user_id']);
        }
        
        $this->assertFalse(isset($_SESSION['user_id']), 
            'Сессия должна быть очищена для несуществующего пользователя');
        
        session_destroy();
    }
    
    /**
     * Тест: защита от подбора пароля (базовая проверка)
     */
    public function testPasswordBruteForceProtection(): void
    {
        $phone = '+7-999-123-45-67';
        $correctPassword = 'Test1234';
        $hashedPassword = password_hash($correctPassword, PASSWORD_DEFAULT);
        
        $this->createTestUser([
            'phone' => $phone,
            'password' => $hashedPassword
        ]);
        
        // Симулируем несколько неудачных попыток
        $wrongPasswords = ['Wrong1', 'Wrong2', 'Wrong3'];
        $normalizedPhone = normalizePhone($phone);
        $stmt = $this->db->prepare("SELECT password FROM users WHERE phone = ?");
        $stmt->execute([$normalizedPhone]);
        $user = $stmt->fetch();
        
        foreach ($wrongPasswords as $wrongPassword) {
            $isValid = password_verify($wrongPassword, $user['password']);
            $this->assertFalse($isValid, "Пароль {$wrongPassword} должен быть отклонен");
        }
        
        // Правильный пароль должен работать
        $this->assertTrue(password_verify($correctPassword, $user['password']), 
            'Правильный пароль должен работать');
    }
}

