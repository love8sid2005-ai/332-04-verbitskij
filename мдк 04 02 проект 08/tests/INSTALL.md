# Инструкция по установке системы тестирования

## Быстрый старт

### 1. Установка Composer (если не установлен)

#### Windows:
```powershell
# Скачайте и установите Composer с https://getcomposer.org/download/
# Или используйте существующий composer.phar в корне проекта
```

#### Linux/Mac:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Установка зависимостей

Перейдите в директорию `backend/`:

```bash
cd backend
```

Если у вас есть `composer.phar` в корне проекта:
```bash
php ../composer.phar install
```

Или если Composer установлен глобально:
```bash
composer install
```

### 3. Проверка установки

Проверьте, что PHPUnit установлен:

```bash
vendor/bin/phpunit --version
```

Должно вывести версию PHPUnit (например, PHPUnit 10.x.x).

### 4. Запуск тестов

#### Через командную строку:
```bash
vendor/bin/phpunit
```

#### Через веб-интерфейс:
1. Запустите PHP встроенный сервер:
```bash
php -S localhost:8000
```

2. Откройте в браузере:
```
http://localhost:8000/backend/test_results.php
```

## Требования к системе

- PHP 8.0 или выше
- SQLite3
- Расширения PHP:
  - `pdo`
  - `pdo_sqlite`
  - `mbstring`
  - `json`
  - `xml`

### Проверка расширений:

```bash
php -m | grep pdo
php -m | grep sqlite
php -m | grep mbstring
```

## Структура файлов после установки

```
backend/
├── vendor/                    # Зависимости Composer
│   └── bin/
│       └── phpunit           # Исполняемый файл PHPUnit
├── tests/                     # Директория с тестами
│   ├── Unit/                 # Модульные тесты
│   ├── Functional/           # Функциональные тесты
│   ├── bootstrap.php         # Инициализация тестов
│   └── TestCase.php          # Базовый класс
├── test-database.db          # Тестовая БД (создается автоматически)
├── test-results.xml          # Результаты тестов (создается автоматически)
├── composer.json             # Конфигурация Composer
├── phpunit.xml               # Конфигурация PHPUnit
├── test_results.php          # Веб-интерфейс
└── test_runner.php           # Скрипт запуска тестов
```

## Устранение проблем

### Ошибка: "Could not find package"

**Решение:** Убедитесь, что вы находитесь в директории `backend/` и файл `composer.json` существует.

### Ошибка: "Class 'PHPUnit\Framework\TestCase' not found"

**Решение:** 
1. Удалите директорию `vendor/`
2. Запустите `composer install` заново

### Ошибка: "PDOException: could not find driver"

**Решение:** Установите расширение `pdo_sqlite`:
- Windows: Раскомментируйте `extension=pdo_sqlite` в `php.ini`
- Linux: `sudo apt-get install php-sqlite3` (Ubuntu/Debian)
- Mac: `brew install php` (если используете Homebrew)

### Ошибка: "Permission denied" при создании БД

**Решение:** Проверьте права доступа:
```bash
chmod 755 backend/
chmod 777 backend/  # Временно для тестов (не рекомендуется для продакшена)
```

### Тесты не запускаются через веб-интерфейс

**Решение:**
1. Убедитесь, что PHP сервер запущен
2. Проверьте, что путь к `test_runner.php` правильный
3. Проверьте права на выполнение PHPUnit
4. Проверьте логи ошибок PHP

## Дополнительная настройка

### Настройка таймаутов

Отредактируйте `phpunit.xml`:
```xml
<php>
    <ini name="max_execution_time" value="300"/>
</php>
```

### Настройка покрытия кода

Добавьте в `phpunit.xml`:
```xml
<coverage>
    <report>
        <html outputDirectory="coverage"/>
    </report>
</coverage>
```

Запустите с покрытием:
```bash
vendor/bin/phpunit --coverage-html coverage/
```

## Проверка работоспособности

После установки выполните:

```bash
vendor/bin/phpunit --testdox
```

Должны увидеть список всех тестов с результатами выполнения.

## Следующие шаги

1. Изучите структуру тестов в `tests/`
2. Запустите тесты через веб-интерфейс
3. Изучите результаты и сообщения об ошибках
4. Начните добавлять свои тесты

---

**Готово!** Система тестирования установлена и готова к использованию.

