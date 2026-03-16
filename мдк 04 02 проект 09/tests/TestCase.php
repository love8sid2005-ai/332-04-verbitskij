<?php
/**
 * Базовый класс для всех тестов
 * Предоставляет общие методы для работы с тестовой БД
 */

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    /**
     * @var PDO Подключение к тестовой БД
     */
    protected $db;
    
    /**
     * Выполняется перед каждым тестом
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Инициализируем чистую БД для каждого теста
        $this->initTestDatabase();
        
        // Создаем подключение к тестовой БД
        $dbPath = defined('DB_PATH') ? DB_PATH : dirname(__DIR__) . '/storage/database/test-database.db';
        $this->db = new PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    
    /**
     * Выполняется после каждого теста
     */
    protected function tearDown(): void
    {
        // Очищаем данные после теста
        $this->cleanupTestData();
        parent::tearDown();
    }
    
    /**
     * Инициализация тестовой БД
     */
    protected function initTestDatabase(): void
    {
        // Используем константу DB_PATH, которая должна быть переопределена в bootstrap
        $dbPath = defined('DB_PATH') ? DB_PATH : dirname(__DIR__) . '/storage/database/test-database.db';
        
        // Создаем директорию если не существует
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        // Удаляем старую БД для чистого теста
        if (file_exists($dbPath)) {
            @unlink($dbPath);
        }
        
        // Создаем новую БД
        try {
            $db = new PDO('sqlite:' . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Создаем таблицу
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                phone TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                secret_question TEXT NOT NULL,
                secret_answer TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            
            $db->exec($sql);
        } catch (PDOException $e) {
            // Игнорируем ошибки - БД может быть уже создана
        }
    }
    
    /**
     * Очистка тестовых данных
     */
    protected function cleanupTestData(): void
    {
        if ($this->db) {
            try {
                $this->db->exec("DELETE FROM users");
            } catch (PDOException $e) {
                // Игнорируем ошибки при очистке
            }
        }
    }
    
    /**
     * Создание тестового пользователя с уникальным телефоном
     * @param array $data Данные пользователя
     * @return int ID созданного пользователя
     */
    protected function createTestUser(array $data = []): int
    {
        $defaults = [
            'phone' => '+7-999-' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT) . '-' . str_pad(rand(10, 99), 2, '0', STR_PAD_LEFT),
            'password' => password_hash('Test1234', PASSWORD_DEFAULT),
            'secret_question' => 'mother_maiden_name',
            'secret_answer' => password_hash('testanswer', PASSWORD_DEFAULT)
        ];
        
        $data = array_merge($defaults, $data);
        
        // Нормализуем телефон если нужно
        if (isset($data['phone'])) {
            $normalized = normalizePhone($data['phone']);
            if ($normalized) {
                $data['phone'] = $normalized;
            }
        }
        
        $stmt = $this->db->prepare(
            "INSERT INTO users (phone, password, secret_question, secret_answer) 
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['phone'],
            $data['password'],
            $data['secret_question'],
            $data['secret_answer']
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Получение пользователя из БД
     * @param int $id ID пользователя
     * @return array|null
     */
    protected function getUserById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Получение пользователя по телефону
     * @param string $phone Номер телефона
     * @return array|null
     */
    protected function getUserByPhone(string $phone): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Подсчет количества пользователей в БД
     * @return int
     */
    protected function getUserCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    }
}
