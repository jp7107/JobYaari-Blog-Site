<?php
namespace App\Controllers;

use App\Models\BlogPost;
use App\Services\FilterService;
use App\Services\ValidationService;

class BlogListingController {
    private FilterService $filterService;
    private ValidationService $validationService;

    public function __construct() {
        $this->filterService = new FilterService();
        $this->validationService = new ValidationService();
    }

    /**
     * Renders the blog listing page with all posts (server-side initial render)
     * Requirement 2: Blog listing page
     */
    public function index(): void {
        $posts = BlogPost::findAll();
        $categories = $this->getCategories($posts);
        include dirname(dirname(__DIR__)) . '/templates/blog/listing.php';
    }

    /**
     * Handles AJAX filter requests and returns JSON response
     * Requirement 4, 5, 6, 7: Filtering, search, categories, date ranges
     */
    public function filter(): void {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input === null) {
                // Fall back to POST
                $input = $_POST;
            }

            $filters = [
                'category'   => $input['category'] ?? '',
                'date_type'  => $input['date_range']['type'] ?? 'all',
                'start_date' => $input['date_range']['start'] ?? '',
                'end_date'   => $input['date_range']['end'] ?? '',
                'search'     => $input['search'] ?? ''
            ];

            // Validate custom date range server-side (Property 13, 14)
            if ($filters['date_type'] === 'custom' &&
                !empty($filters['start_date']) &&
                !empty($filters['end_date'])) {

                $dateValidation = $this->validationService->validateDateRange(
                    $filters['start_date'],
                    $filters['end_date']
                );
                if (!$dateValidation['valid']) {
                    echo json_encode([
                        'success' => false,
                        'error'   => implode(', ', $dateValidation['errors']),
                        'code'    => 'INVALID_DATE_RANGE'
                    ]);
                    return;
                }
            }

            $posts = $this->filterService->applyFilters($filters);

            echo json_encode([
                'success' => true,
                'data'    => array_map(fn($p) => $p->toArray(), $posts),
                'total'   => count($posts)
            ]);
        } catch (\Exception $e) {
            error_log('[FILTER ERROR] ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error'   => 'An unexpected error occurred. Please try again.',
                'code'    => 'FILTER_ERROR'
            ]);
        }
    }

    /**
     * Extracts unique, sorted categories from a list of blog posts.
     */
    private function getCategories(array $posts): array {
        $cats = [];
        foreach ($posts as $post) {
            if (!empty($post->category)) {
                $cats[$post->category] = true;
            }
        }
        $result = array_keys($cats);
        sort($result);
        return $result;
    }
}
