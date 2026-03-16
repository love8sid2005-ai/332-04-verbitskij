<?php
/**
 * Страница восстановления пароля - ввод номера телефона
 */

require_once __DIR__ . '/../src/Config.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (checkAuth()) {
    redirect('index.php');
}

$errors = [];
$phone_found = false;
$recovery_token = '';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.';
    } else {
        // Получение номера телефона
        $phone = trim($_POST['phone'] ?? '');
        
        // Валидация телефона
        $normalized_phone = normalizePhone($phone);
        if (!$normalized_phone || !validatePhone($normalized_phone)) {
            $errors[] = 'Неверный формат номера телефона';
        }
        
        // Проверка существования пользователя
        if (empty($errors)) {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
                $stmt->execute([$normalized_phone]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Генерируем токен восстановления
                    $recovery_token = bin2hex(random_bytes(32));
                    $_SESSION['recovery_token'] = $recovery_token;
                    $_SESSION['recovery_user_id'] = $user['id'];
                    $_SESSION['recovery_expires'] = time() + 3600; // 1 час
                    
                    $phone_found = true;
                } else {
                    $errors[] = 'Пользователь с таким номером телефона не найден';
                }
            } catch (PDOException $e) {
                error_log("Ошибка восстановления: " . $e->getMessage());
                $errors[] = 'Произошла ошибка. Попробуйте позже.';
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
    <title>Восстановление пароля</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <h1>Восстановление пароля</h1>
            
            <?php if ($phone_found): ?>
                <div class="alert alert-success">
                    Пользователь найден! Перейдите к следующему шагу.
                </div>
                <div class="auth-links">
                    <a href="reset-password.php?token=<?php echo htmlspecialchars($recovery_token); ?>" class="btn btn-primary">
                        Продолжить восстановление
                    </a>
                    <p><a href="login.php">Вернуться к входу</a></p>
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
                
                <p>Введите номер телефона, чтобы начать восстановление пароля.</p>
                
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
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Продолжить</button>
                </form>
                
                <div class="auth-links">
                    <p><a href="login.php">Вернуться к входу</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

