<?php
/**
 * Страница регистрации нового пользователя
 */

require_once 'config.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (checkAuth()) {
    redirect('index.php');
}

$errors = [];
$success = false;

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.';
    } else {
        // Получение данных из формы
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $secret_question = $_POST['secret_question'] ?? '';
        $secret_answer = trim($_POST['secret_answer'] ?? '');
        
        // Валидация телефона
        $normalized_phone = normalizePhone($phone);
        if (!$normalized_phone || !validatePhone($normalized_phone)) {
            $errors[] = 'Неверный формат номера телефона. Используйте формат: +7-XXX-XXX-XX-XX';
        }
        
        // Валидация пароля
        $password_validation = validatePassword($password);
        if (!$password_validation['valid']) {
            $errors[] = $password_validation['error'];
        }
        
        // Проверка совпадения паролей
        if ($password !== $password_confirm) {
            $errors[] = 'Пароли не совпадают';
        }
        
        // Валидация секретного вопроса
        $questions = getSecretQuestions();
        if (!isset($questions[$secret_question])) {
            $errors[] = 'Выберите секретный вопрос';
        }
        
        // Валидация ответа на секретный вопрос
        if (empty($secret_answer) || strlen($secret_answer) < 3) {
            $errors[] = 'Ответ на секретный вопрос должен содержать минимум 3 символа';
        }
        
        // Если ошибок нет, регистрируем пользователя
        if (empty($errors)) {
            try {
                $db = getDB();
                
                // Проверка, не существует ли уже пользователь с таким телефоном
                $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
                $stmt->execute([$normalized_phone]);
                if ($stmt->fetch()) {
                    $errors[] = 'Пользователь с таким номером телефона уже зарегистрирован';
                } else {
                    // Хэширование пароля и ответа
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $hashed_answer = password_hash(strtolower(trim($secret_answer)), PASSWORD_DEFAULT);
                    
                    // Сохранение пользователя в БД
                    $stmt = $db->prepare("INSERT INTO users (phone, password, secret_question, secret_answer) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$normalized_phone, $hashed_password, $secret_question, $hashed_answer]);
                    
                    $success = true;
                }
            } catch (PDOException $e) {
                error_log("Ошибка регистрации: " . $e->getMessage());
                $errors[] = 'Произошла ошибка при регистрации. Попробуйте позже.';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$secret_questions = getSecretQuestions();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <h1>Регистрация</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Регистрация успешно завершена! <a href="login.php">Войти</a>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="phone">Номер телефона</label>
                        <input 
                            type="text" 
                            id="phone" 
                            name="phone" 
                            placeholder="+7-XXX-XXX-XX-XX" 
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                            required
                            pattern="\+7-\d{3}-\d{3}-\d{2}-\d{2}"
                        >
                        <small>Формат: +7-XXX-XXX-XX-XX</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Минимум 8 символов, буквы и цифры" 
                            required
                            minlength="8"
                        >
                        <small>Минимум 8 символов, должны быть буквы и цифры</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Подтверждение пароля</label>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            placeholder="Повторите пароль" 
                            required
                            minlength="8"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="secret_question">Секретный вопрос</label>
                        <select id="secret_question" name="secret_question" required>
                            <option value="">Выберите вопрос</option>
                            <?php foreach ($secret_questions as $key => $question): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" 
                                    <?php echo (isset($_POST['secret_question']) && $_POST['secret_question'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($question); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="secret_answer">Ответ на секретный вопрос</label>
                        <input 
                            type="text" 
                            id="secret_answer" 
                            name="secret_answer" 
                            placeholder="Ваш ответ" 
                            value="<?php echo htmlspecialchars($_POST['secret_answer'] ?? ''); ?>"
                            required
                            minlength="3"
                        >
                        <small>Используется для восстановления пароля</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
                </form>
                
                <div class="auth-links">
                    <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

