<?php
namespace App\Controllers;

use App\Models\BlogPost;
use App\Services\ValidationService;
use App\Services\FileUploadService;
use Exception;

class AdminBlogController {
    private ValidationService $validationService;
    private FileUploadService $fileUploadService;

    public function __construct() {
        $this->validationService = new ValidationService();
        $this->fileUploadService = new FileUploadService();
    }

    /**
     * Lists all blog posts paginated (20 per page) (Requirement 13)
     */
    public function list(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20; // 20 blog posts per page (Requirement 13.5)
        $offset = ($page - 1) * $limit;

        $posts = BlogPost::findAll($limit, $offset);
        $totalPosts = BlogPost::countAll();
        $totalPages = ceil($totalPosts / $limit);

        $success = $_SESSION['success_message'] ?? null;
        unset($_SESSION['success_message']);

        $error = $_SESSION['error_message'] ?? null;
        unset($_SESSION['error_message']);

        // Load dashboard listing layout
        include dirname(dirname(__DIR__)) . '/templates/admin/list.php';
    }

    /**
     * Displays the create blog form
     */
    public function create(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $title = 'Create New Blog Post';
        $action = '/admin/blogs/create';
        
        $postData = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);

        $errors = $_SESSION['form_errors'] ?? [];
        unset($_SESSION['form_errors']);

        include dirname(dirname(__DIR__)) . '/templates/admin/form.php';
    }

    /**
     * Stores a new blog post in database
     */
    public function store(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'category' => trim($_POST['category'] ?? '')
        ];
        $file = $_FILES['image'] ?? [];

        // Validate text fields
        $textValidation = $this->validationService->validateBlogPost($data);
        // Validate image file
        $imageValidation = $this->validationService->validateImage($file);

        if (!$textValidation['valid'] || !$imageValidation['valid']) {
            $_SESSION['form_errors'] = array_merge($textValidation['errors'], $imageValidation['errors']);
            $_SESSION['form_data'] = $data;
            $this->redirect('/admin/blogs/create');
        }

        try {
            $imagePath = null;
            // Handle image upload if a file was selected (Property 17)
            if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                $imagePath = $this->fileUploadService->uploadImage($file);
            }

            $blogPost = new BlogPost([
                'title' => $data['title'],
                'content' => $data['content'],
                'short_description' => $data['short_description'],
                'category' => $data['category'],
                'image_path' => $imagePath
            ]);

            if ($blogPost->save()) {
                $_SESSION['success_message'] = "Blog post created successfully!";
                $this->redirect('/admin/blogs');
            } else {
                throw new Exception("Could not insert blog post to database.");
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Redirect to ') === 0) {
                throw $e;
            }
            error_log("[BLOG STORE FAILURE] " . $e->getMessage());
            $_SESSION['form_errors'] = ['db_error' => $e->getMessage()];
            $_SESSION['form_data'] = $data;
            $this->redirect('/admin/blogs/create');
        }
    }

    /**
     * Displays the edit blog form
     */
    public function edit(int $id): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $blogPost = BlogPost::findById($id);
        if (!$blogPost) {
            $_SESSION['error_message'] = "Blog post not found.";
            $this->redirect('/admin/blogs');
        }

        $title = 'Edit Blog Post';
        $action = '/admin/blogs/edit/' . $id;

        $postData = $_SESSION['form_data'] ?? [
            'title' => $blogPost->title,
            'content' => $blogPost->content,
            'short_description' => $blogPost->short_description,
            'category' => $blogPost->category,
            'image_path' => $blogPost->image_path
        ];
        unset($_SESSION['form_data']);

        $errors = $_SESSION['form_errors'] ?? [];
        unset($_SESSION['form_errors']);

        include dirname(dirname(__DIR__)) . '/templates/admin/form.php';
    }

    /**
     * Updates an existing blog post
     */
    public function update(int $id): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $blogPost = BlogPost::findById($id);
        if (!$blogPost) {
            $_SESSION['error_message'] = "Blog post not found.";
            $this->redirect('/admin/blogs');
        }

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'category' => trim($_POST['category'] ?? '')
        ];
        $file = $_FILES['image'] ?? [];

        // Validate text fields
        $textValidation = $this->validationService->validateBlogPost($data);
        // Validate image file
        $imageValidation = $this->validationService->validateImage($file);

        if (!$textValidation['valid'] || !$imageValidation['valid']) {
            $_SESSION['form_errors'] = array_merge($textValidation['errors'], $imageValidation['errors']);
            $_SESSION['form_data'] = $data;
            $this->redirect('/admin/blogs/edit/' . $id);
        }

        try {
            // Update fields
            $blogPost->title = $data['title'];
            $blogPost->content = $data['content'];
            $blogPost->short_description = $data['short_description'];
            $blogPost->category = $data['category'];

            // Handle image replacement if a new image file is uploaded
            if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                // Delete the old image from disk
                if ($blogPost->image_path) {
                    $this->fileUploadService->deleteImage($blogPost->image_path);
                }
                // Upload new image
                $blogPost->image_path = $this->fileUploadService->uploadImage($file);
            }

            if ($blogPost->save()) {
                $_SESSION['success_message'] = "Blog post updated successfully!";
                $this->redirect('/admin/blogs');
            } else {
                throw new Exception("Could not update blog post.");
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Redirect to ') === 0) {
                throw $e;
            }
            error_log("[BLOG UPDATE FAILURE] " . $e->getMessage());
            $_SESSION['form_errors'] = ['db_error' => $e->getMessage()];
            $_SESSION['form_data'] = $data;
            $this->redirect('/admin/blogs/edit/' . $id);
        }
    }

    /**
     * Deletes a blog post and its associated image
     */
    public function delete(int $id): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $blogPost = BlogPost::findById($id);
        if (!$blogPost) {
            $_SESSION['error_message'] = "Blog post not found.";
            $this->redirect('/admin/blogs');
        }

        try {
            // Delete associated image file from server filesystem
            if ($blogPost->image_path) {
                $this->fileUploadService->deleteImage($blogPost->image_path);
            }

            // Remove database record
            if ($blogPost->delete()) {
                $_SESSION['success_message'] = "Blog post deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Could not delete blog post from database.";
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Redirect to ') === 0) {
                throw $e;
            }
            error_log("[BLOG DELETION FAILURE] " . $e->getMessage());
            $_SESSION['error_message'] = "Deletion error: " . $e->getMessage();
        }

        $this->redirect('/admin/blogs');
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
