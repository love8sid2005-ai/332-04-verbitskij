<?php
/**
 * Страница входа в систему
 */

require_once 'config.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (checkAuth()) {
    redirect('index.php');
}

$errors = [];

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.';
    } else {
        // Получение данных из формы
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Валидация телефона
        $normalized_phone = normalizePhone($phone);
        if (!$normalized_phone || !validatePhone($normalized_phone)) {
            $errors[] = 'Неверный формат номера телефона';
        }
        
        // Если телефон валиден, проверяем пароль
        if (empty($errors)) {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id, phone, password FROM users WHERE phone = ?");
                $stmt->execute([$normalized_phone]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Пароль верный, создаем сессию
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_phone'] = $user['phone'];
                    
                    // Редирект на главную страницу
                    redirect('index.php');
                } else {
                    $errors[] = 'Неверный номер телефона или пароль';
                }
            } catch (PDOException $e) {
                error_log("Ошибка входа: " . $e->getMessage());
                $errors[] = 'Произошла ошибка при входе. Попробуйте позже.';
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
    <title>Вход</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <h1>Вход в систему</h1>
            
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
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Введите пароль" 
                        required
                    >
                </div>
                
                <button type="submit" class="btn btn-primary">Войти</button>
            </form>
            
            <div class="auth-links">
                <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
                <p><a href="recover.php">Забыли пароль?</a></p>
            </div>
        </div>
    </div>
</body>
</html>

