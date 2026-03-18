# Инструкция по установке PHPUnit

## Проблема
Система тестирования показывает ошибку: "PHPUnit не найден. Установите зависимости: composer install"

## Решение

### Вариант 1: Использование composer.phar из корня проекта

Откройте терминал (PowerShell или CMD) в папке `backend` и выполните:

```bash
cd backend
php ..\composer.phar install
```

### Вариант 2: Использование composer.bat

Если у вас есть `composer.bat` в корне проекта:

```bash
cd backend
..\composer.bat install
```

### Вариант 3: Глобальный Composer

Если Composer установлен глобально:

```bash
cd backend
composer install
```

## Проверка установки

После установки проверьте:

```bash
cd backend
vendor\bin\phpunit --version
```

Должна вывестись версия PHPUnit (например, `PHPUnit 10.x.x`).

## После установки

1. Обновите страницу тестов в браузере
2. Нажмите "Запустить все тесты"
3. Тесты должны выполниться успешно

## Если PHP не найден в PATH

Если команда `php` не работает, используйте полный путь к PHP:

```bash
"C:\xampp\php\php.exe" ..\composer.phar install
```

Или найдите путь к PHP через:
- XAMPP: `C:\xampp\php\php.exe`
- WAMP: `C:\wamp\bin\php\php8.x.x\php.exe`
- OpenServer: `C:\OpenServer\modules\php\php8.x.x\php.exe`

## Альтернатива: Установка через веб-интерфейс

Если у вас есть доступ к серверу через веб, можно создать временный скрипт установки.

---

**После установки PHPUnit тесты будут работать!** 🎉

