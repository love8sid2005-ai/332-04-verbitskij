<?php
/**
 * Модульные тесты валидации номера телефона
 */

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../../config.php';

class PhoneValidationTest extends TestCase
{
    /**
     * Тест: валидный формат +7-XXX-XXX-XX-XX
     */
    public function testValidPhoneFormatPlus7(): void
    {
        $phone = '+7-999-123-45-67';
        $this->assertTrue(validatePhone($phone), 'Формат +7-XXX-XXX-XX-XX должен быть валидным');
    }
    
    /**
     * Тест: нормализация номера с 8
     */
    public function testNormalizePhoneFrom8(): void
    {
        $phone = '8-999-123-45-67';
        $normalized = normalizePhone($phone);
        $this->assertEquals('+7-999-123-45-67', $normalized, 'Номер с 8 должен нормализоваться в +7');
    }
    
    /**
     * Тест: нормализация номера без дефисов
     */
    public function testNormalizePhoneWithoutDashes(): void
    {
        $phone = '89991234567';
        $normalized = normalizePhone($phone);
        $this->assertEquals('+7-999-123-45-67', $normalized, 'Номер без дефисов должен нормализоваться');
    }
    
    /**
     * Тест: нормализация международного формата
     */
    public function testNormalizeInternationalFormat(): void
    {
        $phone = '79991234567';
        $normalized = normalizePhone($phone);
        $this->assertEquals('+7-999-123-45-67', $normalized, 'Международный формат должен нормализоваться');
    }
    
    /**
     * Тест: невалидный номер - слишком короткий
     */
    public function testInvalidPhoneTooShort(): void
    {
        $phone = '+7-999-123-45';
        $this->assertFalse(validatePhone($phone), 'Слишком короткий номер должен быть невалидным');
    }
    
    /**
     * Тест: невалидный номер - слишком длинный
     */
    public function testInvalidPhoneTooLong(): void
    {
        $phone = '+7-999-123-45-67-89';
        $this->assertFalse(validatePhone($phone), 'Слишком длинный номер должен быть невалидным');
    }
    
    /**
     * Тест: невалидный номер - неправильный формат
     */
    public function testInvalidPhoneWrongFormat(): void
    {
        $phone = '+7-999-123-45-6X';
        $this->assertFalse(validatePhone($phone), 'Номер с буквами должен быть невалидным');
    }
    
    /**
     * Тест: невалидный номер - без плюса
     */
    public function testInvalidPhoneWithoutPlus(): void
    {
        $phone = '7-999-123-45-67';
        $this->assertFalse(validatePhone($phone), 'Номер без + должен быть невалидным');
    }
    
    /**
     * Тест: нормализация с пробелами
     */
    public function testNormalizePhoneWithSpaces(): void
    {
        $phone = '8 999 123 45 67';
        $normalized = normalizePhone($phone);
        $this->assertEquals('+7-999-123-45-67', $normalized, 'Номер с пробелами должен нормализоваться');
    }
    
    /**
     * Тест: невалидный номер - неправильное количество цифр
     */
    public function testInvalidPhoneWrongDigitCount(): void
    {
        $phone = '+7-99-123-45-67';
        $this->assertFalse(validatePhone($phone), 'Номер с неправильным количеством цифр должен быть невалидным');
    }
}

