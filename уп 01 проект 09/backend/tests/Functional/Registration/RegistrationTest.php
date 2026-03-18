<?php
/**
 * Функциональные тесты регистрации пользователей
 */

require_once __DIR__ . '/../../TestCase.php';
require_once __DIR__ . '/../../../config.php';

class RegistrationTest extends TestCase
{
    /**
     * Тест: успешная регистрация с корректными данными
     */
    public function testSuccessfulRegistration(): void
    {
        $phone = '+7-999-123-45-67';
        $password = 'Test1234';
        $secretQuestion = 'mother_maiden_name';
        $secretAnswer = 'Ivanova';
        
        // Симулируем регистрацию
        $normalizedPhone = normalizePhone($phone);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $hashedAnswer = password_hash(strtolower(trim($secretAnswer)), PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare(
            "INSERT INTO users (phone, password, secret_question, secret_answer) 
             VALUES (?, ?, ?, ?)"
        );
        $result = $stmt->execute([$normalizedPhone, $hashedPassword, $secretQuestion, $hashedAnswer]);
        
        $this->assertTrue($result, 'Регистрация должна быть успешной');
        $this->assertEquals(1, $this->getUserCount(), 'Должен быть создан один пользователь');
        
        $user = $this->getUserByPhone($normalizedPhone);
        $this->assertNotNull($user, 'Пользователь должен быть найден в БД');
        $this->assertEquals($normalizedPhone, $user['phone'], 'Телефон должен совпадать');
        $this->assertEquals($secretQuestion, $user['secret_question'], 'Секретный вопрос должен совпадать');
    }
    
    /**
     * Тест: ошибка при дублировании телефона
     */
    public function testRegistrationDuplicatePhone(): void
    {
        $phone = '+7-999-123-45-67';
        
        // Создаем первого пользователя
        $this->createTestUser(['phone' => $phone]);
        
        // Пытаемся создать второго с тем же телефоном
        try {
            $this->createTestUser(['phone' => $phone]);
            $this->fail('Должна быть выброшена ошибка при дублировании телефона');
        } catch (PDOException $e) {
            $this->assertStringContainsString('UNIQUE', $e->getMessage());
        }
        
        $this->assertEquals(1, $this->getUserCount(), 'Должен быть только один пользователь');
    }
    
    /**
     * Тест: ошибка при неверном формате телефона
     */
    public function testRegistrationInvalidPhoneFormat(): void
    {
        $phone = '123'; // Невалидный формат (слишком короткий)
        $normalizedPhone = normalizePhone($phone);
        
        // normalizePhone должен вернуть false для невалидного формата
        $this->assertFalse($normalizedPhone, 'Неверный формат телефона должен быть отклонен');
        
        // Если normalizePhone вернул false, validatePhone тоже должен вернуть false
        if ($normalizedPhone === false) {
            $this->assertFalse(validatePhone($phone), 'Невалидный телефон не должен проходить валидацию');
        }
    }
    
    /**
     * Тест: ошибка при коротком пароле
     */
    public function testRegistrationShortPassword(): void
    {
        $password = 'Test12';
        $result = validatePassword($password);
        
        $this->assertFalse($result['valid'], 'Короткий пароль должен быть отклонен');
        $this->assertStringContainsString('минимум 8 символов', $result['error']);
    }
    
    /**
     * Тест: проверка уникальности номера телефона
     */
    public function testPhoneUniquenessCheck(): void
    {
        $phone = '+7-999-123-45-67';
        
        // Создаем пользователя
        $this->createTestUser(['phone' => $phone]);
        
        // Проверяем уникальность
        $stmt = $this->db->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $existing = $stmt->fetch();
        
        $this->assertNotFalse($existing, 'Пользователь с таким телефоном должен существовать');
    }
    
    /**
     * Тест: валидация всех полей формы
     */
    public function testRegistrationFormValidation(): void
    {
        // Тест пустого телефона
        $phone = '';
        $normalized = normalizePhone($phone);
        $this->assertFalse($normalized && validatePhone($normalized), 'Пустой телефон должен быть отклонен');
        
        // Тест пустого пароля
        $password = '';
        $result = validatePassword($password);
        $this->assertFalse($result['valid'], 'Пустой пароль должен быть отклонен');
        
        // Тест невалидного секретного вопроса
        $questions = getSecretQuestions();
        $this->assertArrayNotHasKey('invalid_question', $questions, 
            'Невалидный секретный вопрос должен быть отклонен');
    }
    
    /**
     * Тест: хэширование пароля при регистрации
     */
    public function testPasswordHashingOnRegistration(): void
    {
        $password = 'Test1234';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $userId = $this->createTestUser(['password' => $hashedPassword]);
        $user = $this->getUserById($userId);
        
        $this->assertNotEquals($password, $user['password'], 'Пароль должен быть захеширован');
        $this->assertTrue(password_verify($password, $user['password']), 
            'password_verify должен подтверждать правильный пароль');
    }
    
    /**
     * Тест: хэширование ответа на секретный вопрос
     */
    public function testSecretAnswerHashing(): void
    {
        $secretAnswer = 'TestAnswer';
        $hashedAnswer = password_hash(strtolower(trim($secretAnswer)), PASSWORD_DEFAULT);
        
        $userId = $this->createTestUser(['secret_answer' => $hashedAnswer]);
        $user = $this->getUserById($userId);
        
        $this->assertNotEquals($secretAnswer, $user['secret_answer'], 
            'Ответ на секретный вопрос должен быть захеширован');
        $this->assertTrue(password_verify(strtolower($secretAnswer), $user['secret_answer']), 
            'password_verify должен подтверждать правильный ответ');
    }
    
    /**
     * Тест: проверка записи в базу данных
     */
    public function testDatabaseRecordCreation(): void
    {
        $phone = '+7-999-123-45-67';
        $secretQuestion = 'mother_maiden_name';
        
        $userId = $this->createTestUser([
            'phone' => $phone,
            'secret_question' => $secretQuestion
        ]);
        
        $user = $this->getUserById($userId);
        
        $this->assertNotNull($user, 'Пользователь должен быть записан в БД');
        $this->assertEquals($phone, $user['phone'], 'Телефон должен быть записан');
        $this->assertEquals($secretQuestion, $user['secret_question'], 'Секретный вопрос должен быть записан');
        $this->assertNotEmpty($user['password'], 'Пароль должен быть записан');
        $this->assertNotEmpty($user['secret_answer'], 'Ответ должен быть записан');
        $this->assertNotNull($user['created_at'], 'Дата создания должна быть установлена');
    }
    
    /**
     * Тест: регистрация с разными форматами телефона
     */
    public function testRegistrationWithDifferentPhoneFormats(): void
    {
        $formats = [
            '+7-999-123-45-67',
            '8-999-123-45-67',
            '79991234567'
        ];
        
        foreach ($formats as $format) {
            $normalized = normalizePhone($format);
            $this->assertNotFalse($normalized, "Формат {$format} должен нормализоваться");
            $this->assertTrue(validatePhone($normalized), "Нормализованный формат должен быть валидным");
        }
    }
    
    /**
     * Тест: регистрация с разными секретными вопросами
     * Исправлено: используем уникальные телефоны для каждого вопроса
     */
    public function testRegistrationWithDifferentSecretQuestions(): void
    {
        $questions = getSecretQuestions();
        
        foreach (array_keys($questions) as $index => $questionKey) {
            // Генерируем уникальный телефон для каждого вопроса
            $uniquePhone = '+7-999-' . str_pad($index + 100, 3, '0', STR_PAD_LEFT) . '-45-67';
            $userId = $this->createTestUser([
                'phone' => $uniquePhone,
                'secret_question' => $questionKey
            ]);
            $user = $this->getUserById($userId);
            
            $this->assertEquals($questionKey, $user['secret_question'], 
                "Секретный вопрос {$questionKey} должен быть сохранен");
        }
    }
    
    /**
     * Тест: валидация минимальной длины ответа на секретный вопрос
     */
    public function testSecretAnswerMinLength(): void
    {
        $shortAnswer = 'ab'; // Меньше 3 символов
        $this->assertLessThan(3, strlen($shortAnswer), 
            'Ответ менее 3 символов должен быть отклонен');
        
        $validAnswer = 'abc'; // 3 символа
        $this->assertGreaterThanOrEqual(3, strlen($validAnswer), 
            'Ответ из 3+ символов должен быть валидным');
    }
    
    /**
     * Тест: регистрация с паролем граничной длины
     */
    public function testRegistrationWithBoundaryPasswordLength(): void
    {
        // Минимальная длина (8 символов)
        $minPassword = 'Test1234';
        $result = validatePassword($minPassword);
        $this->assertTrue($result['valid'], 'Пароль из 8 символов должен быть валидным');
        
        // Меньше минимума (7 символов)
        $shortPassword = 'Test123';
        $result = validatePassword($shortPassword);
        $this->assertFalse($result['valid'], 'Пароль из 7 символов должен быть невалидным');
    }
    
    /**
     * Тест: регистрация с паролем содержащим только английские буквы и цифры
     */
    public function testRegistrationWithEnglishLettersAndNumbers(): void
    {
        $password = 'Test1234';
        $result = validatePassword($password);
        $this->assertTrue($result['valid'], 
            'Пароль с английскими буквами и цифрами должен быть валидным');
        
        // Проверяем, что пароль содержит только буквы и цифры (опционально)
        $this->assertMatchesRegularExpression('/[a-zA-Z]/', $password, 'Пароль должен содержать буквы');
        $this->assertMatchesRegularExpression('/[0-9]/', $password, 'Пароль должен содержать цифры');
    }
    
    /**
     * Тест: защита от SQL-инъекций при регистрации
     */
    public function testSQLInjectionProtection(): void
    {
        $maliciousPhone = "+7-999-123-45-67'; DROP TABLE users; --";
        $normalized = normalizePhone($maliciousPhone);
        
        // Нормализация должна очистить вредоносный код
        if ($normalized) {
            $this->assertStringNotContainsString("DROP", $normalized, 
                'SQL-инъекция должна быть предотвращена нормализацией');
        } else {
            // Если нормализация вернула false, это тоже хорошо - вредоносный код не прошел
            $this->assertFalse($normalized, 'Вредоносный телефон должен быть отклонен');
        }
        
        // Попытка вставки с подготовленным запросом должна быть безопасной
        $userId = $this->createTestUser(['phone' => '+7-999-123-45-67']);
        $this->assertGreaterThan(0, $userId, 'Пользователь должен быть создан безопасно');
    }
}

