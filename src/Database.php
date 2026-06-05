<?php
namespace App;

use PDO;
use PDOException;
use Exception;

class Database {
    private static ?Database $instance = null;
    private ?PDO $conn = null;

    private function __construct() {
        // Load configuration
        $configPath = dirname(__DIR__) . '/config/database.php';
        if (!file_exists($configPath)) {
            throw new Exception("Configuration file not found at " . $configPath);
        }
        $config = require $configPath;

        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;

        if (($config['connection'] ?? 'mysql') === 'sqlite') {
            $dbPath = dirname(__DIR__) . '/' . ($config['database'] ?? 'blog_system') . '.sqlite';
            $dsn = "sqlite:" . $dbPath;
            $username = null;
            $password = null;
        } else {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
        }

        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            // SQLite doesn't support emulate prepares setting or charset in options the same way, but it works fine with PDO options
            $this->conn = new PDO($dsn, $username, $password, $options);
            
            // If SQLite, enable foreign keys
            if (($config['connection'] ?? 'mysql') === 'sqlite') {
                $this->conn->exec("PRAGMA foreign_keys = ON;");
            }
        } catch (PDOException $e) {
            // Log detailed error for debugging
            error_log("[DB CONNECTION FAILURE] " . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            // Check if it's an AJAX request to return appropriate content type
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
                      (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
            
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'The service is temporarily unavailable. Please try again later.'
                ]);
                exit;
            }
            
            // Display a user-friendly error page
            http_response_code(500);
            $errorMessage = "The service is temporarily unavailable. Please try again later.";
            $errorDetail = $e->getMessage();
            
            // If the templates/error.php template exists, use it
            $errorTemplate = dirname(__DIR__) . '/templates/error.php';
            if (file_exists($errorTemplate)) {
                include $errorTemplate;
            } else {
                echo "<h1>500 Internal Server Error</h1><p>" . htmlspecialchars($errorMessage) . "</p>";
            }
            exit;
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->conn;
    }

    // Prevent cloning and serialization
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
