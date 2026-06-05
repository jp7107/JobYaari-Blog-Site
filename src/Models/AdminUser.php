<?php
namespace App\Models;

use App\Database;
use PDO;

class AdminUser {
    public ?int $id = null;
    public string $username = '';
    public string $password_hash = '';
    public ?string $created_at = null;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->username = $data['username'] ?? '';
        $this->password_hash = $data['password_hash'] ?? '';
        $this->created_at = $data['created_at'] ?? null;
    }

    /**
     * Authenticates an administrator.
     * Returns the AdminUser object on success, or null on failure.
     */
    public static function authenticate(string $username, string $password): ?AdminUser {
        $user = self::findByUsername($username);
        if ($user && $user->verifyPassword($password)) {
            return $user;
        }
        return null;
    }

    /**
     * Finds a user by their username.
     */
    public static function findByUsername(string $username): ?AdminUser {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = :username");
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        
        return new self($row);
    }

    /**
     * Verifies the password using PHP's native password_verify function.
     */
    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password_hash);
    }
}
