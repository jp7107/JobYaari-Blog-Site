<?php
namespace App\Middleware;

class AuthMiddleware {
    /**
     * Checks if the user has an active, valid administrator session.
     * Enforces the 30-minute inactivity timeout.
     * Returns true if authenticated, redirects to the login page otherwise.
     */
    public function handle(): bool {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Check if authenticated session exists
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            $this->redirectAndExit();
        }

        // Check for 30-minute inactivity (1800 seconds)
        $timeout = 1800; // 30 minutes
        $now = time();
        
        if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > $timeout)) {
            // Session expired due to inactivity
            $this->destroySession();
            
            // Re-start session to set a message for the user
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['login_error'] = "Your session has expired due to 30 minutes of inactivity. Please log in again.";
            $this->redirectAndExit();
        }

        // Update last activity timestamp
        $_SESSION['last_activity'] = $now;
        return true;
    }

    /**
     * Destroys the current session and cookies.
     */
    public function destroySession(): void {
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
    }

    /**
     * Redirects the client to the admin login page and exits execution.
     */
    private function redirectAndExit(): void {
        if (defined('TEST_ENV')) {
            throw new \Exception("Redirect to /admin/login");
        }
        header('Location: /admin/login');
        exit;
    }
}
