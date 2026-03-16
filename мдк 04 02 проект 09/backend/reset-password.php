<?php
/**
 * Страница сброса пароля - секретный вопрос и новый пароль
 */

require_once 'config.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (checkAuth()) {
    redirect('index.php');
}

$errors = [];
$success = false;
$user = null;
$secret_question_text = '';

// Проверка токена восстановления
$token = $_GET['token'] ?? '';
if (empty($token) || !isset($_SESSION['recovery_token']) || 
    !hash_equals($_SESSION['recovery_token'], $token) ||
    !isset($_SESSION['recovery_user_id']) ||
    !isset($_SESSION['recovery_expires']) ||
    $_SESSION['recovery_expires'] < time()) {
    
    // Токен недействителен или истек
    unset($_SESSION['recovery_token'], $_SESSION['recovery_user_id'], $_SESSION['recovery_expires']);
    redirect('recover.php');
}

$user_id = $_SESSION['recovery_user_id'];

// Получение данных пользователя
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, phone, secret_question FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        redirect('recover.php');
    }
    
    // Получение текста секретного вопроса
    $questions = getSecretQuestions();
    $secret_question_text = $questions[$user['secret_question']] ?? 'Секретный вопрос';
} catch (PDOException $e) {
    error_log("Ошибка получения данных пользователя: " . $e->getMessage());
    redirect('recover.php');
}

// Обработка формы сброса пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.';
    } else {
        // Получение данных из формы
        $secret_answer = trim($_POST['secret_answer'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $new_password_confirm = $_POST['new_password_confirm'] ?? '';
        
        // Валидация ответа на секретный вопрос
        if (empty($secret_answer)) {
            $errors[] = 'Введите ответ на секретный вопрос';
        }
        
        // Валидация нового пароля
        $password_validation = validatePassword($new_password);
        if (!$password_validation['valid']) {
            $errors[] = $password_validation['error'];
        }
        
        // Проверка совпадения паролей
        if ($new_password !== $new_password_confirm) {
            $errors[] = 'Пароли не совпадают';
        }
        
        // Если ошибок нет, проверяем ответ и обновляем пароль
        if (empty($errors)) {
            try {
                $db = getDB();
                
                // Получаем хэшированный ответ из БД
                $stmt = $db->prepare("SELECT secret_answer FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_data = $stmt->fetch();
                
                // Проверяем ответ на секретный вопрос
                if ($user_data && password_verify(strtolower($secret_answer), $user_data['secret_answer'])) {
                    // Ответ верный, обновляем пароль
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    
                    // Очищаем данные восстановления
                    unset($_SESSION['recovery_token'], $_SESSION['recovery_user_id'], $_SESSION['recovery_expires']);
                    
                    $success = true;
                } else {
                    $errors[] = 'Неверный ответ на секретный вопрос';
                }
            } catch (PDOException $e) {
                error_log("Ошибка сброса пароля: " . $e->getMessage());
                $errors[] = 'Произошла ошибка при сбросе пароля. Попробуйте позже.';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сброс пароля</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <h1>Сброс пароля</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Пароль успешно изменен! <a href="login.php">Войти</a>
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
                
                <p>Для сброса пароля ответьте на секретный вопрос и установите новый пароль.</p>
                
                <form method="POST" action="" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label>Секретный вопрос</label>
                        <div class="secret-question-display">
                            <?php echo htmlspecialchars($secret_question_text); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="secret_answer">Ответ на секретный вопрос</label>
                        <input 
                            type="text" 
                            id="secret_answer" 
                            name="secret_answer" 
                            placeholder="Введите ваш ответ" 
                            required
                            minlength="3"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Новый пароль</label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            placeholder="Минимум 8 символов, буквы и цифры" 
                            required
                            minlength="8"
                        >
                        <small>Минимум 8 символов, должны быть буквы и цифры</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password_confirm">Подтверждение нового пароля</label>
                        <input 
                            type="password" 
                            id="new_password_confirm" 
                            name="new_password_confirm" 
                            placeholder="Повторите новый пароль" 
                            required
                            minlength="8"
                        >
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Изменить пароль</button>
                </form>
                
                <div class="auth-links">
                    <p><a href="recover.php">Начать заново</a></p>
                    <p><a href="login.php">Вернуться к входу</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

