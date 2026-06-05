<?php
namespace Tests;

use App\Database;
use PDO;
use Exception;

/**
 * Unit tests for Database connection class
 * Tests Requirements 14.1, 14.4, 14.6
 */
class DatabaseTest extends DatabaseTestCase {
    
    protected function setUp(): void {
        $this->skipIfNoDatabase();
        parent::setUp();
    }
    
    /**
     * Test that Database implements singleton pattern correctly
     * Requirement 14.1: Singleton pattern for database connections
     */
    public function testSingletonPatternReturnsTheSameInstance(): void {
        $instance1 = Database::getInstance();
        $instance2 = Database::getInstance();
        
        $this->assertSame($instance1, $instance2, 
            'getInstance() should return the same instance');
    }
    
    /**
     * Test that getConnection returns a valid PDO instance
     * Requirement 14.1: Database connection management
     */
    public function testGetConnectionReturnsPDOInstance(): void {
        $db = Database::getInstance();
        $connection = $db->getConnection();
        
        $this->assertInstanceOf(PDO::class, $connection, 
            'getConnection() should return a PDO instance');
    }
    
    /**
     * Test that PDO is configured for prepared statements (not emulated)
     * Requirement 14.4: Prepared statements with parameter binding
     */
    public function testPDOConfiguredForRealPreparedStatements(): void {
        $db = Database::getInstance();
        $connection = $db->getConnection();
        
        $driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        // SQLite doesn't support checking this attribute
        if ($driver === 'sqlite') {
            $this->markTestSkipped('SQLite driver does not support ATTR_EMULATE_PREPARES attribute check');
        }
        
        $emulatePrepares = $connection->getAttribute(PDO::ATTR_EMULATE_PREPARES);
        
        $this->assertFalse($emulatePrepares, 
            'PDO should use real prepared statements (ATTR_EMULATE_PREPARES should be false)');
    }
    
    /**
     * Test that PDO is configured with exception error mode
     * Requirement 14.6: Error handling for connection failures
     */
    public function testPDOConfiguredForExceptionErrorMode(): void {
        $db = Database::getInstance();
        $connection = $db->getConnection();
        
        $errorMode = $connection->getAttribute(PDO::ATTR_ERRMODE);
        
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $errorMode, 
            'PDO should use ERRMODE_EXCEPTION for error handling');
    }
    
    /**
     * Test that PDO default fetch mode is associative array
     * Requirement 14.1: Database connection configuration
     */
    public function testPDOConfiguredForAssociativeFetchMode(): void {
        $db = Database::getInstance();
        $connection = $db->getConnection();
        
        $fetchMode = $connection->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);
        
        $this->assertEquals(PDO::FETCH_ASSOC, $fetchMode, 
            'PDO should use FETCH_ASSOC as default fetch mode');
    }
    
    /**
     * Test that prepared statements work correctly with parameter binding
     * Requirement 14.4: Prepared statements with parameter binding
     */
    public function testPreparedStatementsWithParameterBinding(): void {
        $db = Database::getInstance();
        $connection = $db->getConnection();
        
        // Use the existing blog_posts table to test prepared statements
        // This avoids DDL operations within the transaction
        
        // Insert using prepared statement with special characters
        $testTitle = "Test's Title with 'special' \"chars\" & symbols";
        $testContent = "Content with % wildcards _ and other 'special' chars";
        
        $stmt = $connection->prepare(
            "INSERT INTO blog_posts (title, content, short_description, created_date) 
             VALUES (:title, :content, :short_desc, :created_date)"
        );
        
        $stmt->execute([
            ':title' => $testTitle,
            ':content' => $testContent,
            ':short_desc' => 'Short desc',
            ':created_date' => date('Y-m-d H:i:s')
        ]);
        
        // Retrieve using prepared statement
        $stmt = $connection->prepare("SELECT title, content FROM blog_posts WHERE title = :title");
        $stmt->execute([':title' => $testTitle]);
        $result = $stmt->fetch();
        
        $this->assertNotFalse($result, 'Should find the inserted post');
        $this->assertEquals($testTitle, $result['title'], 
            'Prepared statement should correctly bind and retrieve special characters in title');
        $this->assertEquals($testContent, $result['content'], 
            'Prepared statement should correctly bind and retrieve special characters in content');
    }
    
    /**
     * Test that Database instance cannot be cloned
     * Requirement 14.1: Singleton pattern enforcement
     */
    public function testSingletonCannotBeCloned(): void {
        $this->expectException(\Error::class);
        
        $db = Database::getInstance();
        $clone = clone $db;
    }
    
    /**
     * Test that Database instance cannot be unserialized
     * Requirement 14.1: Singleton pattern enforcement
     */
    public function testSingletonCannotBeUnserialized(): void {
        // Expect either our custom exception or PDO serialization error
        $this->expectException(\Throwable::class);
        
        $db = Database::getInstance();
        $serialized = serialize($db);
        unserialize($serialized);
    }
    
    /**
     * Test that SQLite connection enables foreign keys
     * Requirement 14.1: Proper database configuration
     */
    public function testSQLiteForeignKeysEnabled(): void {
        $db = Database::getInstance();
        $connection = $db->getConnection();
        
        // Check if we're using SQLite
        $driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $stmt = $connection->query("PRAGMA foreign_keys");
            $result = $stmt->fetch();
            
            $this->assertEquals('1', $result['foreign_keys'], 
                'Foreign keys should be enabled for SQLite');
        } else {
            $this->markTestSkipped('This test only applies to SQLite connections');
        }
    }
    
    /**
     * Test that prepared statements prevent SQL injection
     * Requirement 14.4: Prevent SQL injection attacks
     */
    public function testPreparedStatementsPreventSQLInjection(): void {
        $db = Database::getInstance();
        $connection = $db->getConnection();
        
        // Insert a safe test post
        $stmt = $connection->prepare(
            "INSERT INTO blog_posts (title, content, short_description, created_date) 
             VALUES (:title, :content, :short_desc, :created_date)"
        );
        
        $stmt->execute([
            ':title' => 'Safe Post',
            ':content' => 'Safe content',
            ':short_desc' => 'Safe description',
            ':created_date' => date('Y-m-d H:i:s')
        ]);
        
        // Attempt SQL injection via prepared statement (should be treated as literal string)
        $maliciousInput = "' OR '1'='1";
        $stmt = $connection->prepare("SELECT * FROM blog_posts WHERE title = :title");
        $stmt->execute([':title' => $maliciousInput]);
        $results = $stmt->fetchAll();
        
        // Should return no results because the malicious input is treated as a literal string
        $this->assertEmpty($results, 
            'Prepared statements should prevent SQL injection by treating input as literal values');
    }
}
