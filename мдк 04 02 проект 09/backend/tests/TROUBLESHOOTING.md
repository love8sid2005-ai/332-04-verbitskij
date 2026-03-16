# Устранение ошибки "Unexpected end of JSON input"

## 🔍 Причины ошибки

Ошибка "Unexpected end of JSON input" возникает, когда:
1. PHPUnit не установлен
2. Команда exec() не может выполниться
3. XML файл результатов не создается
4. Происходит фатальная ошибка PHP до вывода JSON

## ✅ Решения

### 1. Установите PHPUnit

**Проверьте, установлен ли PHPUnit:**
```bash
cd backend
vendor/bin/phpunit --version
```

**Если не установлен, выполните:**
```bash
cd backend
composer install
```

### 2. Проверьте права доступа

Убедитесь, что PHP может:
- Выполнять команды через `exec()`
- Записывать файлы в папку `backend/`

**Проверка:**
```bash
# Windows
cd backend
php -r "exec('php --version', $out); print_r($out);"

# Linux/Mac
cd backend
php -r "exec('php --version', $out); print_r($out);"
```

### 3. Проверьте конфигурацию PHP

Убедитесь, что в `php.ini` разрешено:
```ini
allow_url_fopen = On
disable_functions =  ; (должно быть пусто или не содержать exec)
```

### 4. Проверьте логи ошибок

**Включите отображение ошибок:**
```php
// В начале test_runner.php (временно для отладки)
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

**Или проверьте логи:**
- Windows: `C:\xampp\php\logs\php_error_log` или `C:\wamp\logs\php_error.log`
- Linux: `/var/log/php_errors.log` или `/var/log/apache2/error.log`

### 5. Ручная проверка

**Попробуйте запустить PHPUnit вручную:**
```bash
cd backend
vendor/bin/phpunit --version
```

**Если работает, попробуйте запустить тесты:**
```bash
vendor/bin/phpunit --testsuite "All Tests"
```

### 6. Проверьте путь к PHPUnit

Откройте `backend/test_runner.php` и проверьте путь:
```php
$phpunitPath = __DIR__ . '/vendor/bin/phpunit';
```

Убедитесь, что файл существует:
```bash
# Windows
dir backend\vendor\bin\phpunit

# Linux/Mac
ls -la backend/vendor/bin/phpunit
```

## 🔧 Быстрое решение

1. **Удалите старые файлы результатов:**
   ```bash
   cd backend
   del test-results.xml  # Windows
   rm test-results.xml   # Linux/Mac
   ```

2. **Переустановите зависимости:**
   ```bash
   cd backend
   rm -rf vendor        # Linux/Mac
   rmdir /s vendor      # Windows
   composer install
   ```

3. **Проверьте работу:**
   ```bash
   vendor/bin/phpunit --version
   ```

4. **Попробуйте снова через веб-интерфейс**

## 🐛 Отладка

### Включите детальное логирование

Добавьте в начало `test_runner.php`:
```php
error_log("PHPUnit path: " . $phpunitPath);
error_log("Command: " . $command);
error_log("Output: " . implode("\n", $output));
error_log("Return code: " . $returnCode);
```

### Проверьте ответ сервера

Откройте консоль браузера (F12) и посмотрите:
- Вкладка "Network" → выберите запрос к `test_runner.php` → вкладка "Response"
- Вкладка "Console" → посмотрите на ошибки JavaScript

### Тестирование напрямую

Откройте в браузере:
```
http://localhost:8000/backend/test_runner.php
```

Должна быть ошибка "Метод не разрешен" (это нормально, так как нужен POST запрос).

## 📝 Частые проблемы

### Проблема: "PHPUnit не найден"

**Решение:**
```bash
cd backend
composer install
```

### Проблема: "Permission denied"

**Решение (Linux/Mac):**
```bash
chmod +x backend/vendor/bin/phpunit
```

### Проблема: "exec() has been disabled"

**Решение:**
Отредактируйте `php.ini`:
```ini
disable_functions =  ; Удалите exec из списка
```

### Проблема: Пустой ответ от сервера

**Решение:**
1. Проверьте логи PHP
2. Убедитесь, что нет фатальных ошибок
3. Проверьте, что `test_runner.php` доступен через веб-сервер

## 💡 Альтернативный способ запуска

Если веб-интерфейс не работает, используйте командную строку:

```bash
cd backend
vendor/bin/phpunit
```

Результаты будут в консоли.

---

**Если проблема не решена**, проверьте:
1. Версию PHP (должна быть 8.0+)
2. Установлен ли Composer
3. Доступность интернета (для загрузки зависимостей)
4. Логи ошибок PHP

