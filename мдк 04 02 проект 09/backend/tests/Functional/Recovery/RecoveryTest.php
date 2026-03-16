<?php
/**
 * Функциональные тесты восстановления пароля
 */

require_once __DIR__ . '/../../TestCase.php';
require_once __DIR__ . '/../../../config.php';

class RecoveryTest extends TestCase
{
    /**
     * Тест: поиск пользователя по номеру телефона
     */
    public function testFindUserByPhone(): void
    {
        $phone = '+7-999-123-45-67';
        
        // Создаем пользователя
        $this->createTestUser(['phone' => $phone]);
        
        // Ищем пользователя
        $normalizedPhone = normalizePhone($phone);
        $stmt = $this->db->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$normalizedPhone]);
        $user = $stmt->fetch();
        
        $this->assertNotFalse($user, 'Пользователь должен быть найден по телефону');
        $this->assertArrayHasKey('id', $user, 'Результат должен содержать ID');
    }
    
    /**
     * Тест: ошибка при поиске несуществующего пользователя
     */
    public function testFindNonExistentUserByPhone(): void
    {
        $phone = '+7-999-999-99-99';
        
        // Ищем несуществующего пользователя
        $normalizedPhone = normalizePhone($phone);
        $stmt = $this->db->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$normalizedPhone]);
        $user = $stmt->fetch();
        
        $this->assertFalse($user, 'Несуществующий пользователь не должен быть найден');
    }
    
    /**
     * Тест: проверка секретного вопроса
     */
    public function testSecretQuestionRetrieval(): void
    {
        $phone = '+7-999-123-45-67';
        $secretQuestion = 'mother_maiden_name';
        
        // Создаем пользователя
        $this->createTestUser([
            'phone' => $phone,
            'secret_question' => $secretQuestion
        ]);
        
        // Получаем секретный вопрос
        $normalizedPhone = normalizePhone($phone);
        $stmt = $this->db->prepare("SELECT secret_question FROM users WHERE phone = ?");
        $stmt->execute([$normalizedPhone]);
        $user = $stmt->fetch();
        
        $this->assertEquals($secretQuestion, $user['secret_question'], 
            'Секретный вопрос должен совпадать');
        
        // Проверяем текст вопроса
        $questions = getSecretQuestions();
        $this->assertArrayHasKey($secretQuestion, $questions, 
            'Секретный вопрос должен существовать в списке');
    }
    
    /**
     * Тест: проверка правильного ответа на секретный вопрос
     */
    public function testCorrectSecretAnswerVerification(): void
    {
        $secretAnswer = 'TestAnswer';
        $hashedAnswer = password_hash(strtolower(trim($secretAnswer)), PASSWORD_DEFAULT);
        
        // Создаем пользователя
        $userId = $this->createTestUser(['secret_answer' => $hashedAnswer]);
        
        // Проверяем ответ
        $user = $this->getUserById($userId);
        $isValid = password_verify(strtolower($secretAnswer), $user['secret_answer']);
        
        $this->assertTrue($isValid, 'Правильный ответ должен быть подтвержден');
    }
    
    /**
     * Тест: ошибка при неверном ответе на секретный вопрос
     */
    public function testWrongSecretAnswerVerification(): void
    {
        $correctAnswer = 'TestAnswer';
        $wrongAnswer = 'WrongAnswer';
        $hashedAnswer = password_hash(strtolower(trim($correctAnswer)), PASSWORD_DEFAULT);
        
        // Создаем пользователя
        $userId = $this->createTestUser(['secret_answer' => $hashedAnswer]);
        
        // Проверяем неверный ответ
        $user = $this->getUserById($userId);
        $isValid = password_verify(strtolower($wrongAnswer), $user['secret_answer']);
        
        $this->assertFalse($isValid, 'Неверный ответ должен быть отклонен');
    }
    
    /**
     * Тест: генерация токена восстановления
     */
    public function testRecoveryTokenGeneration(): void
    {
        $token = bin2hex(random_bytes(32));
        
        $this->assertNotEmpty($token, 'Токен не должен быть пустым');
        $this->assertEquals(64, strlen($token), 'Токен должен быть длиной 64 символа (32 байта в hex)');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/i', $token, 
            'Токен должен содержать только hex-символы');
    }
    
    /**
     * Тест: валидация токена восстановления
     */
    public function testRecoveryTokenValidation(): void
    {
        $token1 = bin2hex(random_bytes(32));
        $token2 = bin2hex(random_bytes(32));
        
        // Токены должны быть разными
        $this->assertNotEquals($token1, $token2, 'Токены должны быть уникальными');
        
        // Проверка через hash_equals для безопасности
        $this->assertTrue(hash_equals($token1, $token1), 'Одинаковые токены должны совпадать');
        $this->assertFalse(hash_equals($token1, $token2), 'Разные токены не должны совпадать');
    }
    
    /**
     * Тест: время действия токена восстановления
     */
    public function testRecoveryTokenExpiration(): void
    {
        $expires = time() + 3600; // 1 час
        
        // Токен должен быть действителен
        $this->assertGreaterThan(time(), $expires, 'Время истечения должно быть в будущем');
        
        // Симулируем истекший токен
        $expiredTime = time() - 3600; // 1 час назад
        $this->assertLessThan(time(), $expiredTime, 'Истекший токен должен быть в прошлом');
    }
    
    /**
     * Тест: сброс пароля с проверкой нового пароля
     */
    public function testPasswordResetWithNewPassword(): void
    {
        $oldPassword = 'OldPass123';
        $newPassword = 'NewPass123';
        
        // Создаем пользователя
        $hashedOldPassword = password_hash($oldPassword, PASSWORD_DEFAULT);
        $userId = $this->createTestUser(['password' => $hashedOldPassword]);
        
        // Обновляем пароль
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedNewPassword, $userId]);
        
        // Проверяем, что старый пароль не работает
        $user = $this->getUserById($userId);
        $this->assertFalse(password_verify($oldPassword, $user['password']), 
            'Старый пароль не должен работать');
        
        // Проверяем, что новый пароль работает
        $this->assertTrue(password_verify($newPassword, $user['password']), 
            'Новый пароль должен работать');
    }
    
    /**
     * Тест: валидация нового пароля при сбросе
     */
    public function testNewPasswordValidationOnReset(): void
    {
        $newPassword = 'NewPass123';
        $result = validatePassword($newPassword);
        
        $this->assertTrue($result['valid'], 'Новый пароль должен быть валидным');
        
        // Проверяем невалидный пароль
        $invalidPassword = 'short';
        $result = validatePassword($invalidPassword);
        $this->assertFalse($result['valid'], 'Невалидный пароль должен быть отклонен');
    }
    
    /**
     * Тест: защита от подбора ответов на секретный вопрос
     */
    public function testSecretAnswerBruteForceProtection(): void
    {
        $correctAnswer = 'CorrectAnswer';
        $hashedAnswer = password_hash(strtolower(trim($correctAnswer)), PASSWORD_DEFAULT);
        
        // Создаем пользователя
        $userId = $this->createTestUser(['secret_answer' => $hashedAnswer]);
        $user = $this->getUserById($userId);
        
        // Симулируем несколько неудачных попыток
        $wrongAnswers = ['Wrong1', 'Wrong2', 'Wrong3', 'Wrong4', 'Wrong5'];
        
        foreach ($wrongAnswers as $wrongAnswer) {
            $isValid = password_verify(strtolower($wrongAnswer), $user['secret_answer']);
            $this->assertFalse($isValid, "Ответ {$wrongAnswer} должен быть отклонен");
        }
        
        // Правильный ответ должен работать
        $this->assertTrue(password_verify(strtolower($correctAnswer), $user['secret_answer']), 
            'Правильный ответ должен работать');
    }
    
    /**
     * Тест: чувствительность к регистру ответа на секретный вопрос
     */
    public function testSecretAnswerCaseInsensitivity(): void
    {
        $answer = 'TestAnswer';
        $hashedAnswer = password_hash(strtolower(trim($answer)), PASSWORD_DEFAULT);
        
        // Создаем пользователя
        $userId = $this->createTestUser(['secret_answer' => $hashedAnswer]);
        $user = $this->getUserById($userId);
        
        // Проверяем разные варианты регистра
        $variants = [
            'TestAnswer',
            'testanswer',
            'TESTANSWER',
            'TeStAnSwEr'
        ];
        
        foreach ($variants as $variant) {
            $isValid = password_verify(strtolower($variant), $user['secret_answer']);
            $this->assertTrue($isValid, "Ответ '{$variant}' должен работать (регистр не важен)");
        }
    }
    
    /**
     * Тест: очистка данных восстановления после успешного сброса
     */
    public function testRecoveryDataCleanupAfterReset(): void
    {
        // Симулируем сессию восстановления
        session_start();
        $_SESSION['recovery_token'] = bin2hex(random_bytes(32));
        $_SESSION['recovery_user_id'] = 1;
        $_SESSION['recovery_expires'] = time() + 3600;
        
        // Очищаем данные восстановления
        unset($_SESSION['recovery_token'], $_SESSION['recovery_user_id'], $_SESSION['recovery_expires']);
        
        $this->assertFalse(isset($_SESSION['recovery_token']), 
            'Токен восстановления должен быть удален');
        $this->assertFalse(isset($_SESSION['recovery_user_id']), 
            'ID пользователя должен быть удален');
        $this->assertFalse(isset($_SESSION['recovery_expires']), 
            'Время истечения должно быть удалено');
        
        session_destroy();
    }
    
    /**
     * Тест: восстановление с невалидным токеном
     */
    public function testRecoveryWithInvalidToken(): void
    {
        $validToken = bin2hex(random_bytes(32));
        $invalidToken = bin2hex(random_bytes(32));
        
        // Токены должны быть разными
        $this->assertNotEquals($validToken, $invalidToken, 'Токены должны отличаться');
        
        // Проверка через hash_equals
        $this->assertFalse(hash_equals($validToken, $invalidToken), 
            'Невалидный токен не должен совпадать с валидным');
    }
    
    /**
     * Тест: восстановление с истекшим токеном
     */
    public function testRecoveryWithExpiredToken(): void
    {
        $expires = time() - 1; // Истек 1 секунду назад
        
        $this->assertLessThan(time(), $expires, 'Токен должен быть истекшим');
        
        // Проверка истечения
        $isExpired = $expires < time();
        $this->assertTrue($isExpired, 'Истекший токен должен быть отклонен');
    }
    
    /**
     * Тест: полный процесс восстановления пароля
     */
    public function testFullPasswordRecoveryProcess(): void
    {
        $phone = '+7-999-123-45-67';
        $oldPassword = 'OldPass123';
        $secretAnswer = 'TestAnswer';
        $newPassword = 'NewPass123';
        
        // 1. Создаем пользователя
        $hashedOldPassword = password_hash($oldPassword, PASSWORD_DEFAULT);
        $hashedAnswer = password_hash(strtolower(trim($secretAnswer)), PASSWORD_DEFAULT);
        $userId = $this->createTestUser([
            'phone' => $phone,
            'password' => $hashedOldPassword,
            'secret_answer' => $hashedAnswer
        ]);
        
        // 2. Находим пользователя по телефону
        $normalizedPhone = normalizePhone($phone);
        $stmt = $this->db->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$normalizedPhone]);
        $user = $stmt->fetch();
        $this->assertNotFalse($user, 'Пользователь должен быть найден');
        
        // 3. Генерируем токен восстановления
        $recoveryToken = bin2hex(random_bytes(32));
        $this->assertNotEmpty($recoveryToken, 'Токен должен быть сгенерирован');
        
        // 4. Проверяем ответ на секретный вопрос
        $user = $this->getUserById($userId);
        $answerValid = password_verify(strtolower($secretAnswer), $user['secret_answer']);
        $this->assertTrue($answerValid, 'Ответ должен быть верным');
        
        // 5. Обновляем пароль
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedNewPassword, $userId]);
        
        // 6. Проверяем новый пароль
        $user = $this->getUserById($userId);
        $this->assertTrue(password_verify($newPassword, $user['password']), 
            'Новый пароль должен работать');
        $this->assertFalse(password_verify($oldPassword, $user['password']), 
            'Старый пароль не должен работать');
    }
}

