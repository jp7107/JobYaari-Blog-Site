<?php
declare(strict_types=1);

// -----------------------------------------------------------------------
// Bootstrap: load Composer autoloader and .env
// -----------------------------------------------------------------------
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Router;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// -----------------------------------------------------------------------
// Error / Exception handlers (design.md §Error Handling Strategy)
// -----------------------------------------------------------------------
function isAjaxRequest(): bool {
    return (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) || (
        isset($_SERVER['HTTP_ACCEPT']) &&
        str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')
    );
}

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    error_log("[ERROR] [$errno] $errstr in $errfile:$errline");

    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'An unexpected error occurred. Please try again.'
        ]);
        exit;
    }

    $errorCode    = 500;
    $errorMessage = 'An unexpected server error occurred.';
    include dirname(__DIR__) . '/templates/error.php';
    exit;
});

set_exception_handler(function (Throwable $e): void {
    error_log('[EXCEPTION] ' . $e->getMessage() . "\n" . $e->getTraceAsString());

    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'An unexpected error occurred. Please try again.'
        ]);
        exit;
    }

    $errorCode    = 500;
    $errorMessage = 'An unexpected server error occurred.';
    include dirname(__DIR__) . '/templates/error.php';
    exit;
});

// -----------------------------------------------------------------------
// Dispatch request
// -----------------------------------------------------------------------
$router = Router::create();
$router->dispatch();
