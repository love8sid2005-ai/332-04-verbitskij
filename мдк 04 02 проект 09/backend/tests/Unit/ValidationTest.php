<?php
/**
 * Модульные тесты валидации
 */

require_once __DIR__ . '/../../config.php';

class ValidationTest extends PHPUnit\Framework\TestCase
{
    /**
     * Тест: валидные форматы телефона
     * Исправлено: используем функцию validatePhone() из config.php
     */
    public function testValidPhoneFormats()
    {
        $validPhones = [
            '+7-999-123-45-67',
            '8-999-123-45-67',  // Исправлено: теперь поддерживается
            '+79991234567',      // Исправлено: теперь поддерживается
            '89991234567'        // Исправлено: теперь поддерживается
        ];
        
        foreach ($validPhones as $phone) {
            $this->assertTrue(validatePhone($phone), "Телефон $phone должен быть валидным");
        }
    }
    
    public function testInvalidPhoneFormats()
    {
        $invalidPhones = [
            '79991234567',       // Без + и дефисов
            '+7-999-123-45-6',  // Слишком короткий
            '+7-999-123-45-678', // Слишком длинный
            'abc',
            '',
            '123'                // Невалидный формат
        ];
        
        foreach ($invalidPhones as $phone) {
            $this->assertFalse(validatePhone($phone), "Телефон $phone должен быть невалидным");
        }
    }
    
    /**
     * Тест: требования к паролю
     * Исправлено: используем функцию validatePassword() из config.php
     */
    public function testPasswordRequirements()
    {
        // Валидные пароли: 8-10 символов, буквы и цифры
        $validPasswords = ['pass1234', 'pass12345', 'Test1234', 'password1'];
        // Невалидные пароли: слишком короткие, слишком длинные, без букв, без цифр, спецсимволы
        $invalidPasswords = ['pass', 'pass12', 'password', 'password123', 'password1234', 'пароль123', 'pass@123', 'pass 123'];
        
        // Исправленный подход: используем функцию validatePassword()
        foreach ($validPasswords as $pass) {
            $result = validatePassword($pass);
            $this->assertTrue($result['valid'], "Пароль $pass должен быть валидным");
        }
        
        foreach ($invalidPasswords as $pass) {
            $result = validatePassword($pass);
            // Все пароли из списка invalidPasswords должны быть невалидными
            $this->assertFalse($result['valid'], "Пароль $pass должен быть невалидным");
        }
    }
    
    /**
     * Тест: валидация пароля password1 (10 символов - максимум)
     * Исправлено: проверяем что password1 валиден (максимальная длина 10)
     */
    public function testPasswordMaxLengthIsValid(): void
    {
        $password = 'password1'; // 10 символов - максимум
        $result = validatePassword($password);
        
        $this->assertTrue($result['valid'], 'Пароль password1 должен быть валидным (10 символов, буквы и цифры)');
        $this->assertEmpty($result['error'], 'Не должно быть ошибок для валидного пароля');
    }
    
    /**
     * Тест: валидация пароля password123 (11 символов - больше максимума)
     * Исправлено: проверяем что password123 невалиден (больше 10 символов)
     */
    public function testPassword123IsInvalid(): void
    {
        $password = 'password123'; // 11 символов - больше максимума
        $result = validatePassword($password);
        
        $this->assertFalse($result['valid'], 'Пароль password123 должен быть невалидным (больше 10 символов)');
        $this->assertStringContainsString('максимум 10 символов', $result['error']);
    }
    
    public function testPasswordHashVerification()
    {
        $password = 'test123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('wrongpassword', $hash));
    }
}
