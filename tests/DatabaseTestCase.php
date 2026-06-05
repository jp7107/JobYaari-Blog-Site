<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;
use App\Database;
use PDO;
use Exception;

class DatabaseTestCase extends TestCase {
    protected static ?PDO $db = null;
    private bool $inTransaction = false;

    public static function setUpBeforeClass(): void {
        // Enforce SQLite connection for tests to avoid external DB dependencies
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_NAME'] = 'test_database';

        // Load environment variables if .env exists
        $envPath = dirname(__DIR__);
        if (file_exists($envPath . '/.env')) {
            $dotenv = Dotenv::createImmutable($envPath);
            $dotenv->safeLoad();
        }

        try {
            // Test connection via App\Database
            self::$db = Database::getInstance()->getConnection();
            
            // Check if tables exist. If not, auto-create them from the schema file
            if (!self::tableExists('blog_posts') || !self::tableExists('admin_users') || !self::tableExists('login_attempts')) {
                self::runSchemaMigration();
            }
        } catch (Exception $e) {
            // Database is not accessible, which is fine for unit tests but might skip integration/model tests
            error_log("[TEST SETUP] Database connection not available. Integration tests will be skipped. Error: " . $e->getMessage());
        }
    }

    protected function setUp(): void {
        parent::setUp();
        
        // Start transaction for database isolation if db is connected
        if (self::$db) {
            self::$db->beginTransaction();
            $this->inTransaction = true;
        }
    }

    protected function tearDown(): void {
        // Rollback transaction to clean up test data
        if (self::$db && $this->inTransaction) {
            try {
                self::$db->rollBack();
            } catch (Exception $e) {
                // Ignore rollback errors
            }
            $this->inTransaction = false;
        }
        
        parent::tearDown();
    }

    private static function tableExists(string $tableName): bool {
        if (!self::$db) {
            return false;
        }
        try {
            $result = self::$db->query("SELECT 1 FROM {$tableName} LIMIT 1");
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    private static function runSchemaMigration(): void {
        if (!self::$db) {
            return;
        }
        $sqlPath = dirname(__DIR__) . '/schema/database.sql';
        if (!file_exists($sqlPath)) {
            return;
        }

        $sql = file_get_contents($sqlPath);
        $driver = self::$db->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            // Remove CREATE DATABASE and USE statements
            $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS\s+\w+.*;/i', '', $sql);
            $sql = preg_replace('/USE\s+\w+.*;/i', '', $sql);

            // Process CREATE TABLE statements to make them SQLite compatible
            $sql = preg_replace('/id\s+INT\s+AUTO_INCREMENT\s+PRIMARY\s+KEY/i', 'id INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
            $sql = preg_replace('/DATETIME/i', 'TEXT', $sql);
            $sql = preg_replace('/\) ENGINE=InnoDB[^;]*/i', ')', $sql);

            // Split statement by statement
            $statements = explode(';', $sql);
            $newStatements = [];

            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (empty($stmt)) {
                    continue;
                }

                if (stripos($stmt, 'CREATE TABLE') !== false) {
                    // Extract inline index definitions
                    preg_match_all('/(?:FULLTEXT\s+)?INDEX\s+(\w+)\s*\(([^)]+)\)/i', $stmt, $matches, PREG_SET_ORDER);

                    // Remove inline index definitions from CREATE TABLE syntax
                    $stmt = preg_replace('/,\s*(?:FULLTEXT\s+)?INDEX\s+\w+\s*\([^)]+\)/i', '', $stmt);
                    $stmt = preg_replace('/(?:FULLTEXT\s+)?INDEX\s+\w+\s*\([^)]+\)\s*,?/i', '', $stmt);
                    $stmt = preg_replace('/,\s*\)/', ')', $stmt); // Clean up trailing comma

                    $newStatements[] = $stmt;

                    // Re-add indices as CREATE INDEX queries
                    preg_match('/CREATE\s+TABLE\s+(\w+)/i', $stmt, $tableMatch);
                    $tableName = $tableMatch[1] ?? 'unknown';

                    foreach ($matches as $match) {
                        $indexName = $match[1];
                        $columns = $match[2];
                        $newStatements[] = "CREATE INDEX IF NOT EXISTS {$indexName} ON {$tableName} ({$columns})";
                    }
                } else {
                    $newStatements[] = $stmt;
                }
            }

            foreach ($newStatements as $stmt) {
                self::$db->exec($stmt);
            }
        } else {
            // MySQL
            $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS\s+\w+.*;/i', '', $sql);
            $sql = preg_replace('/USE\s+\w+.*;/i', '', $sql);
            self::$db->exec($sql);
        }
    }

    protected function skipIfNoDatabase(): void {
        if (!self::$db) {
            $this->markTestSkipped('Database connection is not available for this test.');
        }
    }
}
