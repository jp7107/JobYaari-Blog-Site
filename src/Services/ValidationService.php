<?php
namespace App\Services;

use App\Database;
use PDO;
use Exception;
use DateTime;

class ValidationService {
    /**
     * Validates blog post fields
     */
    public function validateBlogPost(array $data, bool $isUpdate = false): array {
        $errors = [];
        
        // Required field validation (Property 1)
        if (!isset($data['title']) || trim($data['title']) === '') {
            $errors['title'] = 'Title is required.';
        }
        if (!isset($data['content']) || trim($data['content']) === '') {
            $errors['content'] = 'Content is required.';
        }
        if (!isset($data['short_description']) || trim($data['short_description']) === '') {
            $errors['short_description'] = 'Short description is required.';
        }

        // Maximum length validation (Property 2)
        if (isset($data['title']) && strlen($data['title']) > 200) {
            $errors['title'] = 'Title cannot exceed 200 characters.';
        }
        if (isset($data['content']) && strlen($data['content']) > 50000) {
            $errors['content'] = 'Content cannot exceed 50000 characters.';
        }
        if (isset($data['short_description']) && strlen($data['short_description']) > 500) {
            $errors['short_description'] = 'Short description cannot exceed 500 characters.';
        }
        if (isset($data['category']) && strlen($data['category']) > 100) {
            $errors['category'] = 'Category cannot exceed 100 characters.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validates an uploaded image (Property 17)
     */
    public function validateImage(array $file): array {
        $errors = [];

        // If no file was uploaded, it is valid (image is optional)
        if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['valid' => true, 'errors' => []];
        }

        // Check if there was an upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['image'] = 'File upload failed with error code ' . $file['error'];
            return ['valid' => false, 'errors' => $errors];
        }

        // Validate file size (max 5MB = 5,242,880 bytes)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $errors['image'] = 'Image size cannot exceed 5MB.';
        }

        // Validate extension/mime type (jpg, jpeg, png, gif)
        $filename = $file['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($ext, $allowedExtensions)) {
            $errors['image'] = 'Invalid image extension. Only JPG, JPEG, PNG, and GIF are allowed.';
        } else {
            // Also check actual MIME type using fileinfo if possible
            if (isset($file['tmp_name']) && file_exists($file['tmp_name'])) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($mimeType, $allowedMimeTypes)) {
                    $errors['image'] = 'Invalid file type. The file must be a valid image.';
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validates a custom date range (Property 13, 14)
     */
    public function validateDateRange(string $start, string $end): array {
        $errors = [];

        $startDate = DateTime::createFromFormat('Y-m-d', $start);
        $endDate = DateTime::createFromFormat('Y-m-d', $end);

        if (!$startDate) {
            $errors['start_date'] = 'Invalid start date format (must be YYYY-MM-DD).';
        }
        if (!$endDate) {
            $errors['end_date'] = 'Invalid end date format (must be YYYY-MM-DD).';
        }

        if ($startDate && $endDate) {
            // Property 13: start date must be before or equal to end date
            if ($startDate > $endDate) {
                $errors['date_range'] = 'The start date must be before or equal to the end date.';
            }

            // Property 14: date range is too large (max 5 years = 1826 days)
            $diff = $startDate->diff($endDate);
            $days = $diff->days;
            if ($days > 1826) {
                $errors['date_range'] = 'The date range is too large. It cannot exceed 5 years.';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validates administrator login attempts (Property 16)
     * Returns true if login is allowed (not locked out), false if account is locked.
     */
    public function validateLoginAttempt(string $username): bool {
        if (empty($username)) {
            return true;
        }

        $db = Database::getInstance()->getConnection();
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        // Count failures in the last 15 minutes dynamically based on DB driver (Property 16)
        if ($driver === 'sqlite') {
            $sql = "SELECT COUNT(*) FROM login_attempts 
                    WHERE username = :username AND attempt_time > datetime('now', '-15 minutes')";
        } else {
            $sql = "SELECT COUNT(*) FROM login_attempts 
                    WHERE username = :username AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        $failedAttempts = (int)$stmt->fetchColumn();
        
        return $failedAttempts < 5;
    }

    /**
     * Records a failed login attempt
     */
    public function recordFailedLogin(string $username, string $ipAddress): void {
        if (empty($username)) {
            return;
        }

        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO login_attempts (username, attempt_time, ip_address) 
                VALUES (:username, CURRENT_TIMESTAMP, :ip_address)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':ip_address', $ipAddress, PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * Clears failed login attempts on successful login
     */
    public function clearFailedLogins(string $username): void {
        if (empty($username)) {
            return;
        }

        $db = Database::getInstance()->getConnection();
        $sql = "DELETE FROM login_attempts WHERE username = :username";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
    }
}
