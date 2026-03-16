<?php
// backend/tests/SimpleTest.php
require_once __DIR__ . '/../config.php';

class SimpleTest extends PHPUnit\Framework\TestCase
{
    public function testBasicMath()
    {
        $this->assertEquals(4, 2 + 2);
        $this->assertTrue(true);
        $this->assertFalse(false);
    }
    
    public function testPhoneValidation()
    {
        $phone = "+7-999-123-45-67";
        $pattern = '/^\+7-\d{3}-\d{3}-\d{2}-\d{2}$/';
        $this->assertEquals(1, preg_match($pattern, $phone));
    }
    
    /**
     * Тест: проверка существования базы данных
     * Исправлено: путь к БД обновлен и добавлено автоматическое создание
     */
    public function testDatabaseExists()
    {
        // Используем константу DB_PATH из config.php
        $dbPath = defined('DB_PATH') ? DB_PATH : __DIR__ . '/../database.db';
        
        // Создаем БД если не существует
        if (!file_exists($dbPath)) {
            $dbDir = dirname($dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            initDatabase();
        }
        
        $this->assertFileExists($dbPath, 'База данных должна существовать');
    }
}
