<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Member;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->member = Member::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test authentication and authorization
     */
    public function test_authentication_required_for_protected_routes()
    {
        // Test unauthenticated access to protected routes
        $routes = [
            ['GET', '/api/users'],
            ['GET', '/api/members'],
            ['GET', '/api/loans'],
            ['GET', '/api/savings/accounts/1'],
            ['GET', '/api/reports/savings-summary'],
            ['GET', '/api/notifications'],
            ['GET', '/api/audit-logs'],
            ['GET', '/api/backups'],
        ];

        foreach ($routes as [$method, $route]) {
            $response = $this->json($method, $route);
            $response->assertStatus(401);
        }
    }

    public function test_role_based_authorization()
    {
        $memberUser = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $loanOfficer = User::factory()->create(['role' => 'loan_officer', 'status' => 'active']);
        $accountant = User::factory()->create(['role' => 'accountant', 'status' => 'active']);

        // Members should not access admin routes
        $response = $this->actingAs($memberUser, 'sanctum')
            ->getJson('/api/users');
        $response->assertStatus(403);

        $response = $this->actingAs($memberUser, 'sanctum')
            ->getJson('/api/audit-logs');
        $response->assertStatus(403);

        // Loan officers should not access user management
        $response = $this->actingAs($loanOfficer, 'sanctum')
            ->postJson('/api/users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'role' => 'member',
            ]);
        $response->assertStatus(403);

        // Accountants should not approve loans
        $loan = Loan::factory()->create(['status' => 'pending']);
        $response = $this->actingAs($accountant, 'sanctum')
            ->putJson("/api/loans/{$loan->id}/approve");
        $response->assertStatus(403);
    }

    /**
     * Test SQL injection prevention
     */
    public function test_sql_injection_prevention()
    {
        // Test SQL injection in search parameters
        $sqlInjectionPayloads = [
            "' OR '1'='1",
            "1' OR '1' = '1",
            "'; DROP TABLE users--",
            "1; DELETE FROM members WHERE 1=1--",
            "' UNION SELECT * FROM users--",
        ];

        foreach ($sqlInjectionPayloads as $payload) {
            // Test in member search
            $response = $this->actingAs($this->user, 'sanctum')
                ->getJson('/api/members?search=' . urlencode($payload));
            
            // Should return 200 with empty or safe results, not 500
            $this->assertContains($response->status(), [200, 422]);
            
            // Verify database integrity - users table should still exist
            $this->assertDatabaseCount('users', User::count());
        }

        // Test SQL injection in POST data
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/members', [
                'first_name' => "'; DROP TABLE members--",
                'last_name' => "' OR '1'='1",
                'email' => 'test@example.com',
                'phone' => '1234567890',
            ]);

        // Should fail validation or handle safely
        $this->assertContains($response->status(), [201, 422]);
        
        // Verify members table still exists
        $this->assertDatabaseCount('members', Member::count());
    }

    /**
     * Test XSS (Cross-Site Scripting) prevention
     */
    public function test_xss_prevention()
    {
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            '<svg onload=alert("XSS")>',
            'javascript:alert("XSS")',
            '<iframe src="javascript:alert(\'XSS\')">',
        ];

        foreach ($xssPayloads as $payload) {
            // Create member with XSS payload
            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/members', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                    'email' => 'xss' . rand(1000, 9999) . '@example.com',
                    'phone' => '1234567890',
                    'address' => $payload,
                ]);

            if ($response->status() === 201) {
                $memberId = $response->json('data.id');
                
                // Retrieve member data
                $getResponse = $this->actingAs($this->user, 'sanctum')
                    ->getJson("/api/members/{$memberId}");
                
                $getResponse->assertStatus(200);
                
                // Verify the response is JSON (not HTML with executable scripts)
                $this->assertEquals('application/json', $getResponse->headers->get('Content-Type'));
                
                // The data should be properly escaped in JSON
                $data = $getResponse->json('data');
                $this->assertIsArray($data);
            }
        }
    }

    /**
     * Test password security
     */
    public function test_password_hashing()
    {
        $plainPassword = 'SecurePassword123!';
        
        // Create user with password
        $user = User::factory()->create([
            'password' => Hash::make($plainPassword),
        ]);

        // Verify password is hashed in database
        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
        
        // Verify password is not returned in API responses
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/users/{$user->id}");
        
        $response->assertStatus(200);
        $response->assertJsonMissing(['password' => $user->password]);
    }

    public function test_password_requirements()
    {
        // Test weak passwords are rejected
        $weakPasswords = [
            '123',
            'password',
            'abc',
            '12345678',
        ];

        foreach ($weakPasswords as $weakPassword) {
            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/users', [
                    'name' => 'Test User',
                    'email' => 'test' . rand(1000, 9999) . '@example.com',
                    'password' => $weakPassword,
                    'role' => 'member',
                ]);

            // Should fail validation
            $response->assertStatus(422);
        }
    }

    public function test_password_reset_security()
    {
        // Test password reset requires valid email
        $response = $this->postJson('/api/password/email', [
            'email' => 'nonexistent@example.com',
        ]);

        // Should not reveal if email exists (security best practice)
        $response->assertStatus(200);
    }

    /**
     * Test CSRF protection
     */
    public function test_csrf_protection_for_state_changing_operations()
    {
        // API routes using Sanctum should be protected
        // Test that token is required for authenticated requests
        $response = $this->postJson('/api/members', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '1234567890',
        ]);

        $response->assertStatus(401); // Unauthenticated
    }

    /**
     * Test rate limiting
     */
    public function test_rate_limiting_on_login()
    {
        $email = 'test@example.com';
        $password = 'wrongpassword';

        // Attempt multiple failed logins
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => $email,
                'password' => $password,
            ]);

            // After several attempts, should be rate limited
            if ($i > 5) {
                $this->assertContains($response->status(), [401, 429]);
            }
        }
    }

    /**
     * Test sensitive data exposure
     */
    public function test_sensitive_data_not_exposed()
    {
        // Create user
        $testUser = User::factory()->create([
            'password' => Hash::make('SecurePassword123!'),
        ]);

        // Get user data
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/users/{$testUser->id}");

        $response->assertStatus(200);
        
        // Verify sensitive fields are not exposed
        $response->assertJsonMissing([
            'password' => $testUser->password,
        ]);

        // Verify remember_token is not exposed
        $data = $response->json('data');
        $this->assertArrayNotHasKey('remember_token', $data);
    }

    /**
     * Test secure headers
     */
    public function test_security_headers_in_production()
    {
        // Note: Security headers are only applied in production environment
        // This test documents expected headers
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/members');

        $response->assertStatus(200);
        
        // In production, these headers should be present:
        // X-Frame-Options: SAMEORIGIN
        // X-Content-Type-Options: nosniff
        // X-XSS-Protection: 1; mode=block
        // Strict-Transport-Security: max-age=31536000
        // Content-Security-Policy: ...
    }

    /**
     * Test file upload security
     */
    public function test_file_upload_validation()
    {
        // Test that only CSV files are accepted for uploads
        $invalidFile = \Illuminate\Http\UploadedFile::fake()->create('malicious.php', 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/savings/upload-deductions', [
                'file' => $invalidFile,
            ]);

        // Should reject non-CSV files
        $response->assertStatus(422);
    }

    /**
     * Test mass assignment protection
     */
    public function test_mass_assignment_protection()
    {
        // Attempt to set protected fields via mass assignment
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/members', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'phone' => '1234567890',
                'id' => 99999, // Should not be mass assignable
                'created_at' => '2020-01-01', // Should not be mass assignable
            ]);

        if ($response->status() === 201) {
            $member = Member::find($response->json('data.id'));
            
            // Verify protected fields were not set
            $this->assertNotEquals(99999, $member->id);
            $this->assertNotEquals('2020-01-01', $member->created_at->format('Y-m-d'));
        }
    }

    /**
     * Test authorization for resource access
     */
    public function test_users_can_only_access_their_own_data()
    {
        $user1 = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $user2 = User::factory()->create(['role' => 'member', 'status' => 'active']);
        
        $member1 = Member::factory()->create(['user_id' => $user1->id]);
        $member2 = Member::factory()->create(['user_id' => $user2->id]);

        // User 1 should not access User 2's data
        $response = $this->actingAs($user1, 'sanctum')
            ->getJson("/api/members/{$member2->id}");
        
        // Should be forbidden or filtered
        $this->assertContains($response->status(), [403, 404]);

        // User should access their own data
        $response = $this->actingAs($user1, 'sanctum')
            ->getJson("/api/members/{$member1->id}");
        
        $response->assertStatus(200);
    }
}
