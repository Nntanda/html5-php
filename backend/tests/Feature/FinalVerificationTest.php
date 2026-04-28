<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\Loan;
use App\Models\Notification;
use App\Models\Backup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class FinalVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $memberUser = User::factory()->create([
            'role' => 'member',
            'status' => 'active',
        ]);

        $this->member = Member::factory()->create([
            'user_id' => $memberUser->id,
        ]);
    }

    /**
     * Test all core features are working
     */
    public function test_authentication_system_working()
    {
        // Test login
        $response = $this->postJson('/api/login', [
            'email' => $this->superAdmin->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['token', 'user'],
        ]);

        // Test authenticated access
        $token = $response->json('data.token');
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/users');
        $response->assertStatus(200);

        // Test logout
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');
        $response->assertStatus(200);
    }

    public function test_member_management_working()
    {
        // Create member
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/members', [
                'first_name' => 'Test',
                'last_name' => 'Member',
                'email' => 'test.member@example.com',
                'phone' => '1234567890',
                'address' => '123 Test St',
                'employment_info' => 'Test Company',
            ]);

        $response->assertStatus(201);
        $memberId = $response->json('data.id');

        // Read member
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/members/{$memberId}");
        $response->assertStatus(200);

        // Update member
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->putJson("/api/members/{$memberId}", [
                'first_name' => 'Updated',
                'last_name' => 'Member',
                'email' => 'test.member@example.com',
                'phone' => '1234567890',
            ]);
        $response->assertStatus(200);

        // List members
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/members');
        $response->assertStatus(200);
    }

    public function test_savings_management_working()
    {
        // Create savings account
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/savings/accounts', [
                'member_id' => $this->member->id,
            ]);

        $response->assertStatus(201);
        $accountId = $response->json('data.id');

        // Make deposit
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/savings/deposit', [
                'account_id' => $accountId,
                'amount' => 10000,
                'source' => 'cash',
                'reference' => 'TEST-001',
            ]);

        $response->assertStatus(201);

        // Check balance
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/savings/accounts/{$accountId}");
        $response->assertStatus(200);
        $this->assertEquals(10000, $response->json('data.balance'));

        // Get transactions
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/savings/accounts/{$accountId}/transactions");
        $response->assertStatus(200);
    }

    public function test_loan_workflow_working()
    {
        // Create savings account with balance
        $account = SavingsAccount::factory()->create([
            'member_id' => $this->member->id,
            'balance' => 50000,
        ]);

        // Apply for loan
        $response = $this->actingAs($this->member->user, 'sanctum')
            ->postJson('/api/loans/apply', [
                'amount' => 100000,
                'term_months' => 12,
                'purpose' => 'Business',
                'guarantor_ids' => [],
            ]);

        $response->assertStatus(201);
        $loanId = $response->json('data.id');

        // Approve loan (as loan officer)
        $loanOfficer = User::factory()->create(['role' => 'loan_officer', 'status' => 'active']);
        $response = $this->actingAs($loanOfficer, 'sanctum')
            ->putJson("/api/loans/{$loanId}/approve");
        $response->assertStatus(200);

        // Disburse loan (as accountant)
        $accountant = User::factory()->create(['role' => 'accountant', 'status' => 'active']);
        $response = $this->actingAs($accountant, 'sanctum')
            ->putJson("/api/loans/{$loanId}/disburse", [
                'disbursement_method' => 'bank_transfer',
                'reference' => 'DISB-TEST',
            ]);
        $response->assertStatus(200);

        // Record repayment
        $response = $this->actingAs($accountant, 'sanctum')
            ->postJson("/api/loans/{$loanId}/repayments", [
                'amount' => 10000,
                'source' => 'cash',
                'reference' => 'REP-TEST',
            ]);
        $response->assertStatus(201);

        // Get loan details
        $response = $this->actingAs($this->member->user, 'sanctum')
            ->getJson("/api/loans/{$loanId}");
        $response->assertStatus(200);
    }

    /**
     * Test all reports generate correctly
     */
    public function test_member_statement_report_generates()
    {
        $account = SavingsAccount::factory()->create([
            'member_id' => $this->member->id,
            'balance' => 10000,
        ]);

        // JSON format
        $response = $this->actingAs($this->member->user, 'sanctum')
            ->getJson("/api/reports/member/{$this->member->id}/statement");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['member', 'savings_account'],
        ]);

        // PDF format
        $response = $this->actingAs($this->member->user, 'sanctum')
            ->getJson("/api/reports/member/{$this->member->id}/statement?format=pdf");
        $response->assertStatus(200);
    }

    public function test_loan_summary_report_generates()
    {
        Loan::factory()->count(5)->create();

        // JSON format
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/reports/loans-summary');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['total_loans', 'total_disbursed'],
        ]);

        // Excel format
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/reports/loans-summary?format=excel');
        $response->assertStatus(200);
    }

    public function test_savings_summary_report_generates()
    {
        SavingsAccount::factory()->count(5)->create();

        // JSON format
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/reports/savings-summary');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['total_accounts', 'total_balance'],
        ]);

        // PDF format
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/reports/savings-summary?format=pdf');
        $response->assertStatus(200);
    }

    public function test_transaction_report_generates()
    {
        $account = SavingsAccount::factory()->create();
        \App\Models\SavingsTransaction::factory()->count(10)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/reports/transactions?start_date=2024-01-01&end_date=2024-12-31');
        $response->assertStatus(200);
    }

    public function test_overdue_loans_report_generates()
    {
        // Create overdue loan
        Loan::factory()->create([
            'status' => 'disbursed',
            'disbursement_date' => now()->subMonths(6),
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/reports/overdue-loans');
        $response->assertStatus(200);
    }

    /**
     * Test notification delivery
     */
    public function test_notification_creation_and_retrieval()
    {
        // Create notification
        $notification = Notification::factory()->create([
            'user_id' => $this->member->user_id,
            'status' => 'pending',
        ]);

        // Get notifications
        $response = $this->actingAs($this->member->user, 'sanctum')
            ->getJson('/api/notifications');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'type', 'subject', 'message', 'status'],
            ],
        ]);

        // Mark as read
        $response = $this->actingAs($this->member->user, 'sanctum')
            ->putJson("/api/notifications/{$notification->id}/read");
        $response->assertStatus(200);

        $notification->refresh();
        $this->assertEquals('read', $notification->status);
    }

    public function test_notification_sending_for_admin()
    {
        // Send manual notification
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/notifications/send', [
                'user_id' => $this->member->user_id,
                'type' => 'general',
                'channel' => 'email',
                'subject' => 'Test Notification',
                'message' => 'This is a test notification',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->member->user_id,
            'subject' => 'Test Notification',
        ]);
    }

    /**
     * Test backup and restore functionality
     */
    public function test_backup_creation()
    {
        Storage::fake('local');

        // Create backup
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/backups/create');

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => ['id', 'filename', 'status'],
        ]);
    }

    public function test_backup_listing()
    {
        Backup::factory()->count(3)->create();

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/backups');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'filename', 'status', 'created_at'],
            ],
        ]);
    }

    /**
     * Test system configuration
     */
    public function test_system_configuration_management()
    {
        // Get configuration
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/config');
        $response->assertStatus(200);

        // Update configuration
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->putJson('/api/config', [
                'interest_rate' => '12.5',
                'max_loan_multiplier' => '3',
            ]);
        $response->assertStatus(200);

        // Verify update
        $this->assertDatabaseHas('system_config', [
            'key' => 'interest_rate',
            'value' => '12.5',
        ]);
    }

    /**
     * Test audit logging
     */
    public function test_audit_logging_working()
    {
        // Perform an action that should be logged
        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/members', [
                'first_name' => 'Audit',
                'last_name' => 'Test',
                'email' => 'audit@example.com',
                'phone' => '1234567890',
            ]);

        // Check audit logs
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/audit-logs');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'user_id', 'action', 'entity_type'],
            ],
        ]);
    }

    /**
     * Test CSV upload functionality
     */
    public function test_csv_upload_functionality()
    {
        Storage::fake('local');

        // Create member with savings account
        $member = Member::factory()->create();
        $account = SavingsAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => 0,
        ]);

        // Create CSV file
        $csvContent = "member_number,amount,reference\n";
        $csvContent .= "{$member->member_number},5000,TEST-001\n";

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'test.csv',
            $csvContent
        );

        // Upload CSV
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/savings/upload-deductions', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['total_records', 'successful', 'failed'],
        ]);

        // Verify balance updated
        $account->refresh();
        $this->assertEquals(5000, $account->balance);
    }

    /**
     * Test user roles and permissions
     */
    public function test_all_user_roles_working()
    {
        $roles = ['super_admin', 'loan_officer', 'accountant', 'member'];

        foreach ($roles as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'status' => 'active',
            ]);

            // Test login
            $response = $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $response->assertStatus(200);
            $this->assertEquals($role, $response->json('data.user.role'));
        }
    }

    /**
     * Test data integrity
     */
    public function test_data_integrity_constraints()
    {
        // Test unique constraints
        $member = Member::factory()->create();
        
        // Attempt to create duplicate member number
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/members', [
                'first_name' => 'Test',
                'last_name' => 'Duplicate',
                'email' => 'unique@example.com',
                'phone' => '1234567890',
                'member_number' => $member->member_number,
            ]);

        // Should fail due to unique constraint
        $this->assertContains($response->status(), [422, 500]);
    }

    /**
     * Test system health
     */
    public function test_database_connection()
    {
        // Test database is accessible
        $this->assertDatabaseCount('users', User::count());
    }

    public function test_api_endpoints_responding()
    {
        $endpoints = [
            '/api/login',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->postJson($endpoint, []);
            // Should respond (even if with validation error)
            $this->assertNotEquals(500, $response->status());
        }
    }
}
