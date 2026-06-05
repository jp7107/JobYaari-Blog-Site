<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;
use App\Services\FileUploadService;

class FileUploadServicePropertyTest extends TestCase {
    use TestTrait;

    private FileUploadService $fileUploadService;

    protected function setUp(): void {
        parent::setUp();
        $this->fileUploadService = new FileUploadService();
    }

    /**
     * Feature: blog-management-system, Property 18: Unique Filename Generation
     * @test
     * @group property-based
     */
    public function testUniqueFilenameGeneration() {
        $this->forAll(
            Generator\string()
        )
        ->then(function($filename) {
            // Ensure the input filename is clean enough (contains extension or simple alphanumeric characters)
            if (empty($filename)) {
                $filename = 'image.png';
            }
            
            // Add a fake extension if it doesn't have one to resemble a file
            if (strpos($filename, '.') === false) {
                $filename .= '.jpg';
            }

            // Generate two filenames for the same input
            $name1 = $this->fileUploadService->generateUniqueFilename($filename);
            $name2 = $this->fileUploadService->generateUniqueFilename($filename);

            // They must be different (unique)
            $this->assertNotEquals($name1, $name2, 'Simultaneous generations for the same filename must be unique');
            
            // The generated filename should preserve the extension
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $this->assertStringEndsWith('.' . $ext, $name1, 'The unique filename must preserve the file extension');
            $this->assertStringEndsWith('.' . $ext, $name2, 'The unique filename must preserve the file extension');
        });
    }
}
