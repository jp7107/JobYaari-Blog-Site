<?php
namespace App;

use App\Controllers\AuthController;
use App\Controllers\AdminBlogController;
use App\Controllers\BlogListingController;
use App\Controllers\BlogDetailController;
use App\Middleware\AuthMiddleware;

class Router {
    private array $routes = [];

    /**
     * Register a GET route.
     */
    public function get(string $pattern, callable $handler): void {
        $this->routes[] = ['GET', $pattern, $handler];
    }

    /**
     * Register a POST route.
     */
    public function post(string $pattern, callable $handler): void {
        $this->routes[] = ['POST', $pattern, $handler];
    }

    /**
     * Dispatch the current HTTP request to the matching route.
     */
    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri    = '/' . trim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        foreach ($this->routes as [$routeMethod, $pattern, $handler]) {
            if ($routeMethod !== $method) {
                continue;
            }

            // Convert :param placeholders to named regex groups
            $regex = preg_replace('#:([a-zA-Z_]+)#', '(?P<$1>[^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches)) {
                // Extract only named captures
                $params = array_filter(
                    $matches,
                    fn($k) => !is_int($k),
                    ARRAY_FILTER_USE_KEY
                );
                call_user_func_array($handler, $params);
                return;
            }
        }

        // 404 fallback
        http_response_code(404);
        $errorCode    = 404;
        $errorMessage = 'The page you are looking for could not be found.';
        include dirname(__DIR__) . '/templates/error.php';
    }

    /**
     * Builds and returns a configured router with all application routes.
     */
    public static function create(): self {
        $router = new self();

        $auth      = new AuthController();
        $adminBlog = new AdminBlogController();
        $listing   = new BlogListingController();
        $detail    = new BlogDetailController();
        $middleware = new AuthMiddleware();

        // ---------------------------------------------------------------
        // Public frontend routes
        // ---------------------------------------------------------------
        $router->get('/', fn() => $listing->index());
        $router->get('/blogs', fn() => $listing->index());
        $router->post('/blogs/filter', fn() => $listing->filter());
        $router->get('/blogs/:id', function (string $id) use ($detail) {
            $detail->show((int)$id);
        });

        // ---------------------------------------------------------------
        // Admin auth routes
        // ---------------------------------------------------------------
        $router->get('/admin/login', fn() => $auth->showLogin());
        $router->post('/admin/login', fn() => $auth->login());
        $router->get('/admin/logout', fn() => $auth->logout());

        // ---------------------------------------------------------------
        // Admin blog CRUD — all guarded by AuthMiddleware
        // ---------------------------------------------------------------
        $router->get('/admin', function () use ($middleware, $adminBlog) {
            $middleware->handle();
            header('Location: /admin/blogs');
            exit;
        });

        $router->get('/admin/blogs', function () use ($middleware, $adminBlog) {
            $middleware->handle();
            $adminBlog->list();
        });

        $router->get('/admin/blogs/create', function () use ($middleware, $adminBlog) {
            $middleware->handle();
            $adminBlog->create();
        });

        $router->post('/admin/blogs/create', function () use ($middleware, $adminBlog) {
            $middleware->handle();
            $adminBlog->store();
        });

        $router->get('/admin/blogs/edit/:id', function (string $id) use ($middleware, $adminBlog) {
            $middleware->handle();
            $adminBlog->edit((int)$id);
        });

        $router->post('/admin/blogs/edit/:id', function (string $id) use ($middleware, $adminBlog) {
            $middleware->handle();
            $adminBlog->update((int)$id);
        });

        $router->post('/admin/blogs/delete/:id', function (string $id) use ($middleware, $adminBlog) {
            $middleware->handle();
            $adminBlog->delete((int)$id);
        });

        return $router;
    }
}
