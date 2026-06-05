<?php
namespace App\Controllers;

use App\Models\AdminUser;
use App\Services\ValidationService;

class AuthController {
    private ValidationService $validationService;

    public function __construct() {
        $this->validationService = new ValidationService();
    }

    /**
     * Renders the login page template
     */
    public function showLogin(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // If already authenticated, redirect to admin blogs dashboard
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            $this->redirect('/admin/blogs');
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        $usernameVal = $_SESSION['login_username'] ?? '';
        unset($_SESSION['login_username']);

        // Load the login template
        include dirname(dirname(__DIR__)) . '/templates/admin/login.php';
    }

    /**
     * Processes a login attempt
     */
    public function login(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $_SESSION['login_username'] = $username;

        // Requirement 9.4: Check for empty username or password
        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = "All fields are required.";
            $this->redirect('/admin/login');
        }

        // Requirement 9.8: Lockout verification (Property 16)
        if (!$this->validationService->validateLoginAttempt($username)) {
            $_SESSION['login_error'] = "This account is temporarily locked due to too many failed login attempts. Please try again after 15 minutes.";
            $this->redirect('/admin/login');
        }

        // Authenticate user
        $user = AdminUser::authenticate($username, $password);

        if ($user) {
            // Authentication successful!
            // Clear failed attempts for this username
            $this->validationService->clearFailedLogins($username);
            
            // Set session variables (Property 16, Requirement 9.6)
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['last_activity'] = time();
            $_SESSION['created_at'] = time();
            
            unset($_SESSION['login_username']);

            $this->redirect('/admin/blogs');
        } else {
            // Authentication failed
            $this->validationService->recordFailedLogin($username, $ipAddress);

            // Double check if this attempt locked the account
            if (!$this->validationService->validateLoginAttempt($username)) {
                $_SESSION['login_error'] = "Incorrect credentials. Too many failed attempts. The account has been locked for 15 minutes.";
            } else {
                $_SESSION['login_error'] = "Incorrect username or password.";
            }

            $this->redirect('/admin/login');
        }
    }

    /**
     * Processes logout by destroying session
     */
    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies") && !headers_sent()) {
            $params = session_get_cookie_params();
            @setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        @session_destroy();
        
        // Restart session to set logout message
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['login_error'] = "You have been successfully logged out.";
        
        $this->redirect('/admin/login');
    }

    /**
     * Redirect helper supporting test environment exceptions
     */
    private function redirect(string $url): void {
        if (defined('TEST_ENV')) {
            throw new \Exception("Redirect to " . $url);
        }
        header('Location: ' . $url);
        exit;
    }
}
