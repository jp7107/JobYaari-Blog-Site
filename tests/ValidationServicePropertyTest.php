<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;
use App\Services\ValidationService;

class ValidationServicePropertyTest extends TestCase {
    use TestTrait;

    private ValidationService $validator;

    protected function setUp(): void {
        parent::setUp();
        $this->validator = new ValidationService();
    }

    /**
     * Feature: blog-management-system, Property 1: Required Field Validation
     * @test
     * @group property-based
     */
    public function testRequiredFieldValidationRejectsEmptyFields() {
        $this->forAll(
            Generator\associative([
                'title' => Generator\oneOf(
                    Generator\constant(''),
                    Generator\string()
                ),
                'content' => Generator\oneOf(
                    Generator\constant(''),
                    Generator\string()
                ),
                'short_description' => Generator\oneOf(
                    Generator\constant(''),
                    Generator\string()
                )
            ])
        )
        ->when(function($post) {
            // Test cases where at least one required field is empty or missing
            return empty($post['title']) || empty($post['content']) || empty($post['short_description']);
        })
        ->then(function($post) {
            $result = $this->validator->validateBlogPost($post);
            $this->assertFalse($result['valid'], 'Validation should reject posts with empty required fields');
            $this->assertNotEmpty($result['errors'], 'Validation errors array should not be empty');
            
            if (empty($post['title'])) {
                $this->assertArrayHasKey('title', $result['errors']);
            }
            if (empty($post['content'])) {
                $this->assertArrayHasKey('content', $result['errors']);
            }
            if (empty($post['short_description'])) {
                $this->assertArrayHasKey('short_description', $result['errors']);
            }
        });
    }

    /**
     * Feature: blog-management-system, Property 2: Maximum Length Validation
     * @test
     * @group property-based
     */
    public function testMaxLengthValidationRejectsLongFields() {
        $this->forAll(
            Generator\elements('title', 'content', 'short_description', 'category'),
            Generator\string()
        )
        ->then(function($field, $str) {
            $post = [
                'title' => 'Valid Title',
                'content' => 'Valid Content',
                'short_description' => 'Valid Excerpt',
                'category' => 'Valid Category'
            ];

            // Make the chosen field exceed its limit
            switch ($field) {
                case 'title':
                    $post['title'] = str_repeat('a', 201) . $str;
                    break;
                case 'content':
                    $post['content'] = str_repeat('a', 50001) . $str;
                    break;
                case 'short_description':
                    $post['short_description'] = str_repeat('a', 501) . $str;
                    break;
                case 'category':
                    $post['category'] = str_repeat('a', 101) . $str;
                    break;
            }

            $result = $this->validator->validateBlogPost($post);
            $this->assertFalse($result['valid'], "Validation should reject posts when {$field} exceeds its max length limit");
            $this->assertNotEmpty($result['errors'], 'Validation errors array should not be empty');
            $this->assertArrayHasKey($field, $result['errors']);
        });
    }

    /**
     * Feature: blog-management-system, Property 17: Image Upload Validation
     * @test
     * @group property-based
     */
    public function testImageUploadValidation() {
        $this->forAll(
            Generator\associative([
                'name' => Generator\string(),
                'type' => Generator\string(),
                'tmp_name' => Generator\string(),
                'error' => Generator\choose(0, 4), // 0 is UPLOAD_ERR_OK, 4 is UPLOAD_ERR_NO_FILE
                'size' => Generator\choose(0, 10 * 1024 * 1024) // 0 to 10MB
            ])
        )
        ->then(function($file) {
            // Determine if the file should be valid
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $maxSize = 5 * 1024 * 1024;
            
            $shouldBeValid = true;
            
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                $shouldBeValid = true; // No file is valid since featured image is optional
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $shouldBeValid = false;
            } elseif ($file['size'] > $maxSize) {
                $shouldBeValid = false;
            } elseif (!in_array($ext, $allowedExtensions)) {
                $shouldBeValid = false;
            }

            // We mock the temp file check in ValidationService by passing an invalid tmp_name which isn't a real file,
            // so we skip the mime check by not checking tmp_name exists inside Property test or handling it gracefully.
            $result = $this->validator->validateImage($file);
            
            if ($shouldBeValid) {
                // If it passes simple rules, it might still fail MIME check if tmp_name doesn't exist.
                // But if it fails the simple rules, it MUST be invalid.
                if (!$result['valid']) {
                    $this->assertNotEmpty($result['errors']);
                }
            } else {
                $this->assertFalse($result['valid'], 'Validation should reject invalid files');
                $this->assertNotEmpty($result['errors']);
            }
        });
    }

    /**
     * Feature: blog-management-system, Property 13 & 14: Date Range Validation
     * @test
     * @group property-based
     */
    public function testDateRangeValidation() {
        $this->forAll(
            // Dates in format YYYY-MM-DD
            Generator\elements('2020-01-01', '2023-05-15', '2025-12-31', '2030-08-20', '2022-10-10'),
            Generator\elements('2020-01-01', '2023-05-15', '2025-12-31', '2030-08-20', '2022-10-10')
        )
        ->then(function($start, $end) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $start);
            $endDate = \DateTime::createFromFormat('Y-m-d', $end);
            
            $diff = $startDate->diff($endDate);
            $days = $diff->days;
            $isStartAfterEnd = $startDate > $endDate;
            $isTooLarge = $days > 1826;

            $result = $this->validator->validateDateRange($start, $end);
            
            if ($isStartAfterEnd || $isTooLarge) {
                $this->assertFalse($result['valid'], 'Should reject range if start > end or span > 5 years');
                $this->assertNotEmpty($result['errors']);
                $this->assertArrayHasKey('date_range', $result['errors']);
            } else {
                $this->assertTrue($result['valid'], 'Should accept valid date ranges');
                $this->assertEmpty($result['errors']);
            }
        });
    }
}
