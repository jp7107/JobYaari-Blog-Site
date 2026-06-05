<?php
namespace Tests;

use Eris\Generator;
use Eris\TestTrait;
use App\Services\ValidationService;
use App\Database;

class AuthPropertyTest extends DatabaseTestCase {
    use TestTrait;

    private ValidationService $validationService;

    protected function setUp(): void {
        $this->skipIfNoDatabase();
        parent::setUp();
        $this->validationService = new ValidationService();
    }

    /**
     * Feature: blog-management-system, Property 16: Login Rate Limiting
     * @test
     * @group property-based
     */
    public function testLoginRateLimitingLockout() {
        $this->forAll(
            Generator\choose(0, 10) // number of failed attempts
        )
        ->then(function($failedCount) {
            $username = 'test_lockout_user_' . uniqid();
            $ip = '127.0.0.1';

            // Clear any historical entries (though each test is transaction isolated)
            $this->validationService->clearFailedLogins($username);

            // Simulate $failedCount failed login attempts
            for ($i = 0; $i < $failedCount; $i++) {
                // Assert lockout state before the 5th attempt
                if ($i < 5) {
                    $this->assertTrue(
                        $this->validationService->validateLoginAttempt($username),
                        "Account should NOT be locked after {$i} failed attempts"
                    );
                } else {
                    $this->assertFalse(
                        $this->validationService->validateLoginAttempt($username),
                        "Account MUST be locked after {$i} failed attempts (>= 5)"
                    );
                }

                $this->validationService->recordFailedLogin($username, $ip);
            }

            // Verify final status
            $isAllowed = $this->validationService->validateLoginAttempt($username);
            if ($failedCount >= 5) {
                $this->assertFalse($isAllowed, "Account should be locked out at final step when failures >= 5");
            } else {
                $this->assertTrue($isAllowed, "Account should not be locked out at final step when failures < 5");
            }
        });
    }
}
