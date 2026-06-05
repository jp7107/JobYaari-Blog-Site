<?php
namespace Tests;

use App\Controllers\AuthController;
use App\Controllers\AdminBlogController;
use App\Models\BlogPost;
use App\Models\AdminUser;
use App\Database;
use App\Middleware\AuthMiddleware;
use PDO;
use Exception;

class IntegrationTest extends DatabaseTestCase {

    public static function setUpBeforeClass(): void {
        if (!defined('TEST_ENV')) {
            define('TEST_ENV', true);
        }
        parent::setUpBeforeClass();
    }

    protected function setUp(): void {
        $this->skipIfNoDatabase();
        parent::setUp();
        
        // Ensure session is active and clean for tests without triggering header warnings
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_FILES = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    public function testAuthenticationFlow() {
        $authController = new AuthController();

        // 1. Unauthenticated request to Admin Panel
        $middleware = new AuthMiddleware();
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Redirect to /admin/login");
        $middleware->handle();
    }

    public function testSuccessfulLogin() {
        $authController = new AuthController();

        // AdminUser is seeded by schema/database.sql ('admin' / 'Password@123')
        $_POST['username'] = 'admin';
        $_POST['password'] = 'Password@123';

        try {
            $authController->login();
            $this->fail("Expected redirect exception on login");
        } catch (Exception $e) {
            $this->assertEquals("Redirect to /admin/blogs", $e->getMessage());
        }

        $this->assertTrue($_SESSION['authenticated']);
        $this->assertEquals('admin', $_SESSION['username']);
        $this->assertNotNull($_SESSION['last_activity']);
    }

    public function testFailedLoginRecordsAttempt() {
        $authController = new AuthController();

        $_POST['username'] = 'admin';
        $_POST['password'] = 'WrongPassword';

        try {
            $authController->login();
            $this->fail("Expected redirect exception on login failure");
        } catch (Exception $e) {
            $this->assertEquals("Redirect to /admin/login", $e->getMessage());
        }

        $this->assertFalse(isset($_SESSION['authenticated']));
        $this->assertEquals("Incorrect username or password.", $_SESSION['login_error']);
    }

    public function testInactivitySessionExpiration() {
        // Mock active session
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = 'admin';
        $_SESSION['last_activity'] = time() - 2000; // > 1800 seconds ago

        $middleware = new AuthMiddleware();
        
        try {
            $middleware->handle();
            $this->fail("Expected session timeout redirect exception");
        } catch (Exception $e) {
            $this->assertEquals("Redirect to /admin/login", $e->getMessage());
        }

        $this->assertFalse(isset($_SESSION['authenticated']));
        $this->assertStringContainsString("expired", $_SESSION['login_error']);
    }

    public function testBlogCrudWorkflow() {
        $adminController = new AdminBlogController();

        // 1. Create a blog post
        $_POST['title'] = 'Integration Test Blog Title';
        $_POST['content'] = 'Integration content text.';
        $_POST['short_description'] = 'Short intro snippet.';
        $_POST['category'] = 'Result';
        $_FILES['image'] = ['error' => UPLOAD_ERR_NO_FILE];

        try {
            $adminController->store();
            $this->fail("Expected redirect to list after store");
        } catch (Exception $e) {
            $this->assertEquals("Redirect to /admin/blogs", $e->getMessage());
        }

        // Check DB
        $posts = BlogPost::findAll();
        $this->assertNotEmpty($posts);
        
        $post = null;
        foreach ($posts as $p) {
            if ($p->title === 'Integration Test Blog Title') {
                $post = $p;
                break;
            }
        }
        $this->assertNotNull($post);
        $this->assertEquals('Integration content text.', $post->content);
        $this->assertEquals('Result', $post->category);
        $id = $post->id;

        // 2. Update the blog post
        $_POST['title'] = 'Modified Integration Title';
        $_POST['content'] = 'Modified integration content.';
        $_POST['short_description'] = 'Modified short desc.';
        $_POST['category'] = 'Jobs';
        $_FILES['image'] = ['error' => UPLOAD_ERR_NO_FILE];

        try {
            $adminController->update($id);
            $this->fail("Expected redirect to list after update");
        } catch (Exception $e) {
            $this->assertEquals("Redirect to /admin/blogs", $e->getMessage());
        }

        // Verify changes
        $updated = BlogPost::findById($id);
        $this->assertEquals('Modified Integration Title', $updated->title);
        $this->assertEquals('Modified integration content.', $updated->content);
        $this->assertEquals('Jobs', $updated->category);

        // 3. Delete the blog post
        try {
            $adminController->delete($id);
            $this->fail("Expected redirect after delete");
        } catch (Exception $e) {
            $this->assertEquals("Redirect to /admin/blogs", $e->getMessage());
        }

        // Verify deletion
        $deleted = BlogPost::findById($id);
        $this->assertNull($deleted);
    }
}
