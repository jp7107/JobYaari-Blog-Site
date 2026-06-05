<?php
namespace App\Controllers;

use App\Models\BlogPost;

class BlogDetailController {
    /**
     * Renders the blog detail page for a specific post.
     * Property 5: Invalid Blog ID Handling
     * Requirement 3: Blog detail page
     */
    public function show(int $id): void {
        // Property 5: invalid IDs (0, negative) immediately rejected
        if ($id <= 0) {
            $this->notFound('Blog not found.');
            return;
        }

        $blogPost = BlogPost::findById($id);

        if (!$blogPost) {
            $this->notFound('Blog not found.');
            return;
        }

        include dirname(dirname(__DIR__)) . '/templates/blog/detail.php';
    }

    /**
     * Renders a 404 not-found page without DB interaction.
     */
    private function notFound(string $message): void {
        http_response_code(404);
        $errorCode = 404;
        $errorMessage = $message;
        include dirname(dirname(__DIR__)) . '/templates/error.php';
    }
}
