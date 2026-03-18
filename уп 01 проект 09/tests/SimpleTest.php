<?php
/**
 * Простые тесты
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../src/Config.php';

class SimpleTest extends TestCase
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
     * Исправлено: путь к БД обновлен на storage/database/database.db
     */
    public function testDatabaseExists()
    {
        $dbPath = dirname(__DIR__) . '/storage/database/database.db';
        
        // Создаем БД если не существует
        if (!file_exists($dbPath)) {
            $dbDir = dirname($dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            initDatabase();
        }
        
        $this->assertFileExists($dbPath, 'База данных должна существовать в storage/database/database.db');
    }
}
