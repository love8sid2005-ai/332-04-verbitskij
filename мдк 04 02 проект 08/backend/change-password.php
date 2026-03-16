<?php
/**
 * Страница изменения пароля для авторизованных пользователей
 */

require_once __DIR__ . '/config.php';

// Проверка авторизации
$user = checkAuth();
if (!$user) {
    redirect('login.php');
}

$errors = [];
$success = false;

// Обработка формы изменения пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.';
    } else {
        // Получение данных из формы
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $new_password_confirm = $_POST['new_password_confirm'] ?? '';
        
        // Валидация текущего пароля
        if (empty($current_password)) {
            $errors[] = 'Введите текущий пароль';
        }
        
        // Валидация нового пароля
        $password_validation = validatePassword($new_password);
        if (!$password_validation['valid']) {
            $errors[] = $password_validation['error'];
        }
        
        // Проверка совпадения паролей
        if ($new_password !== $new_password_confirm) {
            $errors[] = 'Новые пароли не совпадают';
        }
        
        // Проверка, что новый пароль отличается от текущего
        if ($current_password === $new_password) {
            $errors[] = 'Новый пароль должен отличаться от текущего';
        }
        
        // Если ошибок нет, проверяем текущий пароль и обновляем
        if (empty($errors)) {
            try {
                $db = getDB();
                
                // Получаем текущий пароль из БД
                $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                $user_data = $stmt->fetch();
                
                // Проверяем текущий пароль
                if ($user_data && password_verify($current_password, $user_data['password'])) {
                    // Пароль верный, обновляем на новый
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user['id']]);
                    
                    $success = true;
                } else {
                    $errors[] = 'Неверный текущий пароль';
                }
            } catch (PDOException $e) {
                error_log("Ошибка изменения пароля: " . $e->getMessage());
                $errors[] = 'Произошла ошибка при изменении пароля. Попробуйте позже.';
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
    <title>Изменение пароля</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <h1>Изменение пароля</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Пароль успешно изменен! <a href="index.php">Вернуться на главную</a>
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
                
                <p>Для изменения пароля введите текущий пароль и новый пароль.</p>
                
                <form method="POST" action="" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="current_password">Текущий пароль</label>
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            placeholder="Введите текущий пароль" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Новый пароль</label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            placeholder="От 8 до 10 символов, буквы и цифры" 
                            required
                            minlength="8"
                            maxlength="10"
                        >
                        <small>От 8 до 10 символов, только английские буквы и цифры</small>
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
                            maxlength="10"
                        >
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Изменить пароль</button>
                </form>
                
                <div class="auth-links">
                    <p><a href="index.php">Вернуться на главную</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

