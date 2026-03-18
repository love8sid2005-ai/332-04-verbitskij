<?php
/**
 * Модульные тесты валидации пароля
 */

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../../config.php';

class PasswordValidationTest extends TestCase
{
    /**
     * Тест: валидный пароль с буквами и цифрами
     */
    public function testValidPasswordWithLettersAndNumbers(): void
    {
        $password = 'Test1234';
        $result = validatePassword($password);
        $this->assertTrue($result['valid'], 'Пароль с буквами и цифрами должен быть валидным');
        $this->assertEmpty($result['error'], 'Не должно быть ошибок для валидного пароля');
    }
    
    /**
     * Тест: валидный пароль минимальной длины (8 символов)
     */
    public function testValidPasswordMinLength(): void
    {
        $password = 'Test1234';
        $result = validatePassword($password);
        $this->assertTrue($result['valid'], 'Пароль из 8 символов должен быть валидным');
    }
    
    /**
     * Тест: невалидный пароль - слишком короткий (менее 8 символов)
     */
    public function testInvalidPasswordTooShort(): void
    {
        $password = 'Test123';
        $result = validatePassword($password);
        $this->assertFalse($result['valid'], 'Пароль менее 8 символов должен быть невалидным');
        $this->assertStringContainsString('минимум 8 символов', $result['error']);
    }
    
    /**
     * Тест: невалидный пароль - только цифры
     */
    public function testInvalidPasswordOnlyNumbers(): void
    {
        $password = '12345678';
        $result = validatePassword($password);
        $this->assertFalse($result['valid'], 'Пароль только с цифрами должен быть невалидным');
        $this->assertStringContainsString('букву', $result['error']);
    }
    
    /**
     * Тест: невалидный пароль - только буквы
     */
    public function testInvalidPasswordOnlyLetters(): void
    {
        $password = 'TestTest';
        $result = validatePassword($password);
        $this->assertFalse($result['valid'], 'Пароль только с буквами должен быть невалидным');
        $this->assertStringContainsString('цифру', $result['error']);
    }
    
    /**
     * Тест: невалидный пароль с русскими буквами
     * Исправлено: пароли должны содержать только английские буквы и цифры
     */
    public function testInvalidPasswordWithRussianLetters(): void
    {
        $password = 'Тест1234';
        $result = validatePassword($password);
        $this->assertFalse($result['valid'], 'Пароль с русскими буквами должен быть невалидным (только английские буквы разрешены)');
        $this->assertStringContainsString('только английские буквы', $result['error']);
    }
    
    /**
     * Тест: валидный пароль максимальной длины (10 символов)
     */
    public function testValidPasswordMaxLength(): void
    {
        $password = 'Test123456';
        $result = validatePassword($password);
        $this->assertTrue($result['valid'], 'Пароль из 10 символов должен быть валидным');
    }
    
    /**
     * Тест: валидный пароль с разным регистром
     */
    public function testValidPasswordMixedCase(): void
    {
        $password = 'TeSt1234';
        $result = validatePassword($password);
        $this->assertTrue($result['valid'], 'Пароль с разным регистром должен быть валидным');
    }
    
    /**
     * Тест: хэширование пароля
     */
    public function testPasswordHashing(): void
    {
        $password = 'Test1234';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $this->assertNotEmpty($hash, 'Хэш пароля не должен быть пустым');
        $this->assertNotEquals($password, $hash, 'Хэш не должен совпадать с исходным паролем');
        $this->assertTrue(password_verify($password, $hash), 'password_verify должен подтверждать правильный пароль');
    }
    
    /**
     * Тест: проверка password_verify с неверным паролем
     */
    public function testPasswordVerifyWrongPassword(): void
    {
        $password = 'Test1234';
        $wrongPassword = 'Wrong123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $this->assertFalse(password_verify($wrongPassword, $hash), 'password_verify должен отклонять неверный пароль');
    }
    
    /**
     * Тест: граничный случай - пустой пароль
     */
    public function testInvalidPasswordEmpty(): void
    {
        $password = '';
        $result = validatePassword($password);
        $this->assertFalse($result['valid'], 'Пустой пароль должен быть невалидным');
    }
}

