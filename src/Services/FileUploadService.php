<?php
namespace App\Services;

use Exception;

class FileUploadService {
    private string $uploadDir;
    private string $publicDir;

    public function __construct() {
        // Base public path
        $this->publicDir = dirname(dirname(__DIR__)) . '/public/';
        $this->uploadDir = $this->publicDir . 'uploads/';
        
        // Ensure uploads directory exists and is 755 (Requirement 15.3)
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        } else {
            chmod($this->uploadDir, 0755);
        }
    }

    /**
     * Uploads and stores the image file securely.
     * Returns the relative path to be stored in the database (e.g. 'uploads/filename.png').
     */
    public function uploadImage(array $file): string {
        // Double check upload status
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed with error code: " . ($file['error'] ?? 'unknown'));
        }

        // Check write permission of uploads directory (Requirement 15.7)
        if (!is_writable($this->uploadDir)) {
            error_log("[UPLOAD FAILURE] Uploads directory is not writable: " . $this->uploadDir);
            throw new Exception("Insufficient permissions to write to the uploads directory.");
        }

        // Generate unique filename (Property 18)
        $uniqueName = $this->generateUniqueFilename($file['name']);
        $targetFile = $this->uploadDir . $uniqueName;

        // Move uploaded file to uploads directory
        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            throw new Exception("Could not save the uploaded file to the server.");
        }

        // Set uploaded file permissions to 644 (Requirement 15.3)
        chmod($targetFile, 0644);

        // Return relative path for database
        return 'uploads/' . $uniqueName;
    }

    /**
     * Deletes the image from the server filesystem.
     * $path is the relative database path (e.g. 'uploads/filename.png').
     */
    public function deleteImage(string $path): bool {
        if (empty($path)) {
            return false;
        }

        // Resolve absolute path
        $absolutePath = $this->publicDir . $path;

        // Ensure we only delete files inside the public/uploads directory for security
        $realUploadDir = realpath($this->uploadDir);
        $realFileDir = realpath(dirname($absolutePath));
        
        if ($realUploadDir && $realFileDir && strpos($realFileDir, $realUploadDir) === 0 && file_exists($absolutePath)) {
            return unlink($absolutePath);
        }

        return false;
    }

    /**
     * Generates a unique collision-free filename.
     */
    public function generateUniqueFilename(string $originalName): string {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        // Use high entropy values: time, random bytes, and a clean basename prefix
        $prefix = preg_replace("/[^a-zA-Z0-9]/", "", pathinfo($originalName, PATHINFO_FILENAME));
        $prefix = substr($prefix, 0, 30); // limit prefix size
        
        return $prefix . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    }
}
