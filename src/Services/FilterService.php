<?php
namespace App\Services;

use App\Database;
use App\Models;
use App\Models\BlogPost;
use PDO;
use DateTime;
use Exception;

class FilterService {
    /**
     * Applies combined filters and returns matching BlogPost objects ordered by created_date DESC.
     * $filters can contain:
     * - 'category': string (e.g. 'Result', 'All Categories')
     * - 'date_type': string ('today', 'this_week', 'this_month', 'custom', 'all')
     * - 'start_date': string ('YYYY-MM-DD' for custom)
     * - 'end_date': string ('YYYY-MM-DD' for custom)
     * - 'search': string (keywords)
     */
    public function applyFilters(array $filters): array {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT * FROM blog_posts";
        $where = [];
        $params = [];

        // 1. Category Filter (Requirement 5.4)
        if (isset($filters['category']) && $filters['category'] !== 'All Categories' && trim($filters['category']) !== '') {
            $where[] = "category = :category";
            $params[':category'] = $filters['category'];
        }

        // 2. Date Filter (Requirement 6)
        $dateType = $filters['date_type'] ?? 'all';
        if ($dateType !== 'all') {
            $dateRange = $this->calculateDateRange($dateType, [
                'start' => $filters['start_date'] ?? '',
                'end' => $filters['end_date'] ?? ''
            ]);
            
            if ($dateRange) {
                $where[] = "created_date BETWEEN :date_start AND :date_end";
                $params[':date_start'] = $dateRange['start'];
                $params[':date_end'] = $dateRange['end'];
            }
        }

        // 3. Search Query Filter (Requirement 4)
        if (isset($filters['search']) && trim($filters['search']) !== '') {
            $rawSearch = trim($filters['search']);
            $escapedSearch = $this->sanitizeSearch($rawSearch);
            
            // Search title or content case-insensitive (utf8mb4_unicode_ci handles case insensitivity)
            $where[] = "(title LIKE :search OR content LIKE :search)";
            $params[':search'] = '%' . $escapedSearch . '%';
        }

        // Combine using AND logic
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // Always order by created_date descending (Requirement 1.2, 2.6)
        $sql .= " ORDER BY created_date DESC";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();
        
        $rows = $stmt->fetchAll();
        $posts = [];
        foreach ($rows as $row) {
            $posts[] = new BlogPost($row);
        }
        return $posts;
    }

    /**
     * Sanitizes special SQL characters in search input to treat them as literal text.
     * Prevents wildcards like % and _ from taking effect, and escapes backslashes.
     */
    public function sanitizeSearch(string $search): string {
        // First escape backslash, then percent and underscore
        $search = str_replace('\\', '\\\\', $search);
        $search = str_replace('%', '\%', $search);
        $search = str_replace('_', '\_', $search);
        return $search;
    }

    /**
     * Computes start and end date bounds for different periods in server timezone.
     */
    public function calculateDateRange(string $rangeType, array $customRange = []): ?array {
        $now = new DateTime('now');
        
        switch (strtolower($rangeType)) {
            case 'today':
                // Today 00:00:00 to 23:59:59 (Property 9)
                return [
                    'start' => $now->format('Y-m-d 00:00:00'),
                    'end' => $now->format('Y-m-d 23:59:59')
                ];
                
            case 'this_week':
                // Monday 00:00:00 to Sunday 23:59:59 of current week (Property 10)
                $dayOfWeek = (int)$now->format('N'); // 1 = Monday, 7 = Sunday
                $monday = clone $now;
                $monday->modify('-' . ($dayOfWeek - 1) . ' days');
                
                $sunday = clone $monday;
                $sunday->modify('+6 days');
                
                return [
                    'start' => $monday->format('Y-m-d 00:00:00'),
                    'end' => $sunday->format('Y-m-d 23:59:59')
                ];
                
            case 'this_month':
                // 1st of month 00:00:00 to last day of month 23:59:59 (Property 11)
                return [
                    'start' => $now->format('Y-m-01 00:00:00'),
                    'end' => $now->format('Y-m-t 23:59:59')
                ];
                
            case 'custom':
                if (empty($customRange['start']) || empty($customRange['end'])) {
                    return null;
                }
                // Custom start 00:00:00 to end 23:59:59 (Property 12)
                $start = DateTime::createFromFormat('Y-m-d', $customRange['start']);
                $end = DateTime::createFromFormat('Y-m-d', $customRange['end']);
                
                if ($start && $end) {
                    return [
                        'start' => $start->format('Y-m-d 00:00:00'),
                        'end' => $end->format('Y-m-d 23:59:59')
                    ];
                }
                return null;
                
            default:
                return null;
        }
    }
}
