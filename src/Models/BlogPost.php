<?php
namespace App\Models;

use App\Database;
use PDO;
use Exception;

class BlogPost {
    public ?int $id = null;
    public string $title = '';
    public string $content = '';
    public string $short_description = '';
    public ?string $category = null;
    public ?string $image_path = null;
    public ?string $created_date = null; // Stored as YYYY-MM-DD HH:MM:SS in DB

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->title = $data['title'] ?? '';
        $this->content = $data['content'] ?? '';
        $this->short_description = $data['short_description'] ?? '';
        $this->category = !empty($data['category']) ? $data['category'] : null;
        $this->image_path = !empty($data['image_path']) ? $data['image_path'] : null;
        $this->created_date = $data['created_date'] ?? null;
    }

    /**
     * Finds all blog posts ordered by created_date descending
     */
    public static function findAll(?int $limit = null, int $offset = 0): array {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT * FROM blog_posts ORDER BY created_date DESC";
        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $db->prepare($sql);
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $rows = $stmt->fetchAll();
        
        $posts = [];
        foreach ($rows as $row) {
            $posts[] = new self($row);
        }
        return $posts;
    }

    /**
     * Counts the total number of blog posts
     */
    public static function countAll(): int {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) FROM blog_posts");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Finds a single blog post by its ID
     */
    public static function findById(int $id): ?BlogPost {
        if ($id <= 0) {
            return null;
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        
        return new self($row);
    }

    /**
     * Finds blog posts by applying filters (category, date range, search)
     * Uses FilterService to handle complex filter logic
     * 
     * @param array $filters Filter criteria:
     *   - 'category': string (exact match, null/empty for all)
     *   - 'date_type': string ('today', 'this_week', 'this_month', 'custom', 'all')
     *   - 'start_date': string (YYYY-MM-DD for custom range)
     *   - 'end_date': string (YYYY-MM-DD for custom range)
     *   - 'search': string (keywords to search in title/content)
     * @return array Array of BlogPost objects matching all filter criteria
     */
    public static function findByFilters(array $filters): array {
        // Use FilterService to handle the complex filter logic
        // This delegates to the service layer as per the architecture design
        $filterService = new \App\Services\FilterService();
        return $filterService->applyFilters($filters);
    }

    /**
     * Saves the blog post to the database (insert or update)
     */
    public function save(): bool {
        $db = Database::getInstance()->getConnection();
        
        // Validation check before save (optional double-check)
        if (empty($this->title) || empty($this->content) || empty($this->short_description)) {
            throw new Exception("Missing required fields for saving BlogPost.");
        }
        
        if (strlen($this->title) > 200) {
            throw new Exception("Title exceeds 200 characters.");
        }
        if (strlen($this->content) > 50000) {
            throw new Exception("Content exceeds 50000 characters.");
        }
        if (strlen($this->short_description) > 500) {
            throw new Exception("Short description exceeds 500 characters.");
        }
        if ($this->category !== null && strlen($this->category) > 100) {
            throw new Exception("Category exceeds 100 characters.");
        }
        if ($this->image_path !== null && strlen($this->image_path) > 500) {
            throw new Exception("Image path exceeds 500 characters.");
        }

        if ($this->id === null) {
            // INSERT
            if ($this->created_date === null) {
                $this->created_date = date('Y-m-d H:i:s');
            }
            $sql = "INSERT INTO blog_posts (title, content, short_description, category, image_path, created_date)
                    VALUES (:title, :content, :short_description, :category, :image_path, :created_date)";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':title', $this->title, PDO::PARAM_STR);
            $stmt->bindValue(':content', $this->content, PDO::PARAM_STR);
            $stmt->bindValue(':short_description', $this->short_description, PDO::PARAM_STR);
            $stmt->bindValue(':category', $this->category, $this->category === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':image_path', $this->image_path, $this->image_path === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':created_date', $this->created_date, PDO::PARAM_STR);
            
            $result = $stmt->execute();
            if ($result) {
                $this->id = (int)$db->lastInsertId();
                return true;
            }
            return false;
        } else {
            // UPDATE: Original id and created_date MUST be preserved
            // Let's fetch original created_date from database to be absolutely sure we preserve it
            $stmt = $db->prepare("SELECT created_date FROM blog_posts WHERE id = :id");
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            $originalCreatedDate = $stmt->fetchColumn();
            if ($originalCreatedDate) {
                $this->created_date = $originalCreatedDate;
            }
            
            $sql = "UPDATE blog_posts 
                    SET title = :title, content = :content, short_description = :short_description, 
                        category = :category, image_path = :image_path 
                    WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindValue(':title', $this->title, PDO::PARAM_STR);
            $stmt->bindValue(':content', $this->content, PDO::PARAM_STR);
            $stmt->bindValue(':short_description', $this->short_description, PDO::PARAM_STR);
            $stmt->bindValue(':category', $this->category, $this->category === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':image_path', $this->image_path, $this->image_path === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            
            return $stmt->execute();
        }
    }

    /**
     * Deletes the blog post from the database
     */
    public function delete(): bool {
        if ($this->id === null) {
            return false;
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = :id");
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Converts properties to an associative array for JSON responses
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'short_description' => $this->short_description,
            'category' => $this->category,
            'image_path' => $this->image_path,
            'created_date' => $this->created_date ? date('Y-m-d', strtotime($this->created_date)) : null
        ];
    }
}
