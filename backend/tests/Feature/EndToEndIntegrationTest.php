<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EndToEndIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $loanOfficer;
    protected $accountant;
    protected $member;
    protected $memberUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->loanOfficer = User::factory()->create([
            'role' => 'loan_officer',
            'status' => 'active',
        ]);

        $this->accountant = User::factory()->create([
            'role' => 'accountant',
            'status' => 'active',
        ]);

        $this->memberUser = User::factory()->create([
            'role' => 'member',
            'status' => 'active',
        ]);

        $this->member = Member::factory()->create([
            'user_id' => $this->memberUser->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test complete workflow: Member registration to loan disbursement
     */
    public function test_complete_member_to_loan_disbursement_workflow()
    {
        // Step 1: Super Admin creates a new member
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/members', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'phone' => '1234567890',
                'address' => '123 Main St',
                'employment_info' => 'Software Engineer at Tech Corp',
            ]);

        $response->assertStatus(201);
        $newMember = Member::where('first_name', 'John')->first();
        $this->assertNotNull($newMember);

        // Step 2: Create savings account for the new member
        $response = $this->actingAs($this->accountant, 'sanctum')
            ->postJson('/api/savings/accounts', [
                'member_id' => $newMember->id,
            ]);

        $response->assertStatus(201);
        $savingsAccount = SavingsAccount::where('member_id', $newMember->id)->first();
        $this->assertNotNull($savingsAccount);

        // Step 3: Make deposits to build savings (minimum for loan eligibility)
        $response = $this->actingAs($this->accountant, 'sanctum')
            ->postJson('/api/savings/deposit', [
                'account_id' => $savingsAccount->id,
                'amount' => 50000,
                'source' => 'cash',
                'reference' => 'INIT-001',
            ]);

        $response->assertStatus(201);
        $savingsAccount->refresh();
        $this->assertEquals(50000, $savingsAccount->balance);

        // Step 4: Member applies for a loan
        $response = $this->actingAs($newMember->user, 'sanctum')
            ->postJson('/api/loans/apply', [
                'amount' => 100000,
                'term_months' => 12,
                'purpose' => 'Business expansion',
                'guarantor_ids' => [$this->member->id],
            ]);

        $response->assertStatus(201);
        $loan = Loan::where('member_id', $newMember->id)->first();
        $this->assertNotNull($loan);
        $this->assertEquals('pending', $loan->status);

        // Step 5: Guarantor approves the loan
        $guarantor = $loan->guarantors()->first();
        $response = $this->actingAs($this->member->user, 'sanctum')
            ->putJson("/api/loans/{$loan->id}/guarantors/{$guarantor->id}", [
                'status' => 'approved',
            ]);

        $response->assertStatus(200);

        // Step 6: Loan Officer approves the loan
        $response = $this->actingAs($this->loanOfficer, 'sanctum')
            ->putJson("/api/loans/{$loan->id}/approve");

        $response->assertStatus(200);
        $loan->refresh();
        $this->assertEquals('approved', $loan->status);

        // Step 7: Accountant disburses the loan
        $response = $this->actingAs($this->accountant, 'sanctum')
            ->putJson("/api/loans/{$loan->id}/disburse", [
                'disbursement_method' => 'bank_transfer',
                'reference' => 'DISB-001',
            ]);

        $response->assertStatus(200);
        $loan->refresh();
        $this->assertEquals('disbursed', $loan->status);
        $this->assertNotNull($loan->disbursement_date);

        // Step 8: Record a repayment
        $response = $this->actingAs($this->accountant, 'sanctum')
            ->postJson("/api/loans/{$loan->id}/repayments", [
                'amount' => 10000,
                'source' => 'cash',
                'reference' => 'REP-001',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('loan_repayments', [
            'loan_id' => $loan->id,
            'amount' => 10000,
        ]);
    }

    /**
     * Test role-based access controls
     */
    public function test_role_based_access_controls()
    {
        // Member should not access user management
        $response = $this->actingAs($this->memberUser, 'sanctum')
            ->getJson('/api/users');
        $response->assertStatus(403);

        // Loan Officer should not access user management
        $response = $this->actingAs($this->loanOfficer, 'sanctum')
            ->getJson('/api/users');
        $response->assertStatus(403);

        // Super Admin should access user management
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/users');
        $response->assertStatus(200);

        // Accountant should not approve loans
        $loan = Loan::factory()->create(['status' => 'pending']);
        $response = $this->actingAs($this->accountant, 'sanctum')
            ->putJson("/api/loans/{$loan->id}/approve");
        $response->assertStatus(403);

        // Loan Officer should approve loans
        $response = $this->actingAs($this->loanOfficer, 'sanctum')
            ->putJson("/api/loans/{$loan->id}/approve");
        $response->assertStatus(200);

        // Loan Officer should not disburse loans
        $loan->refresh();
        $response = $this->actingAs($this->loanOfficer, 'sanctum')
            ->putJson("/api/loans/{$loan->id}/disburse", [
                'disbursement_method' => 'bank_transfer',
                'reference' => 'TEST',
            ]);
        $response->assertStatus(403);

        // Accountant should disburse loans
        $response = $this->actingAs($this->accountant, 'sanctum')
            ->putJson("/api/loans/{$loan->id}/disburse", [
                'disbursement_method' => 'bank_transfer',
                'reference' => 'TEST',
            ]);
        $response->assertStatus(200);
    }

    /**
     * Test CSV upload and batch processing
     */
    public function test_csv_upload_and_batch_processing()
    {
        Storage::fake('local');

        // Create members with savings accounts
        $member1 = Member::factory()->create();
        $member2 = Member::factory()->create();
        $account1 = SavingsAccount::factory()->create(['member_id' => $member1->id, 'balance' => 0]);
        $account2 = SavingsAccount::factory()->create(['member_id' => $member2->id, 'balance' => 0]);

        // Create CSV content for salary deductions
        $csvContent = "member_number,amount,reference\n";
        $csvContent .= "{$member1->member_number},5000,SAL-001\n";
        $csvContent .= "{$member2->member_number},7500,SAL-002\n";

        $file = UploadedFile::fake()->createWithContent('deductions.csv', $csvContent);

        // Upload CSV
        $response = $this->actingAs($this->accountant, 'sanctum')
            ->postJson('/api/savings/upload-deductions', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total_records',
                'successful',
                'failed',
            ],
        ]);

        // Verify balances updated
        $account1->refresh();
        $account2->refresh();
        $this->assertEquals(5000, $account1->balance);
        $this->assertEquals(7500, $account2->balance);
    }

    /**
     * Test report generation and exports
     */
    public function test_report_generation_and_exports()
    {
        // Create test data
        $savingsAccount = SavingsAccount::factory()->create([
            'member_id' => $this->member->id,
            'balance' => 10000,
        ]);

        // Test member statement report
        $response = $this->actingAs($this->member->user, 'sanctum')
            ->getJson("/api/reports/member/{$this->member->id}/statement");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'member',
                'savings_account',
                'transactions',
            ],
        ]);

        // Test savings summary report (admin)
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/reports/savings-summary');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_accounts',
                'total_balance',
            ],
        ]);

        // Test loans summary report
        $response = $this->actingAs($this->loanOfficer, 'sanctum')
            ->getJson('/api/reports/loans-summary');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_loans',
                'total_disbursed',
            ],
        ]);

        // Test PDF export
        $response = $this->actingAs($this->member->user, 'sanctum')
            ->getJson("/api/reports/member/{$this->member->id}/statement?format=pdf");
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));

        // Test Excel export
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/reports/savings-summary?format=excel');
        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('Content-Type'), 'spreadsheet') ||
            str_contains($response->headers->get('Content-Type'), 'excel')
        );
    }

    /**
     * Test authentication flow
     */
    public function test_authentication_flow()
    {
        // Test login with valid credentials
        $response = $this->postJson('/api/login', [
            'email' => $this->memberUser->email,
            'password' => 'password', // Default factory password
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'token',
                'user',
            ],
        ]);

        $token = $response->json('data.token');

        // Test authenticated request
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/members/' . $this->member->id);
        $response->assertStatus(200);

        // Test logout
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');
        $response->assertStatus(200);

        // Test request after logout (should fail)
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/members/' . $this->member->id);
        $response->assertStatus(401);
    }

    /**
     * Test data validation and error handling
     */
    public function test_data_validation_and_error_handling()
    {
        // Test invalid member creation
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/members', [
                'first_name' => '', // Empty required field
                'email' => 'invalid-email', // Invalid email
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name', 'email']);

        // Test invalid loan application
        $response = $this->actingAs($this->memberUser, 'sanctum')
            ->postJson('/api/loans/apply', [
                'amount' => -1000, // Negative amount
                'term_months' => 0, // Invalid term
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount', 'term_months']);

        // Test invalid deposit
        $savingsAccount = SavingsAccount::factory()->create();
        $response = $this->actingAs($this->accountant, 'sanctum')
            ->postJson('/api/savings/deposit', [
                'account_id' => $savingsAccount->id,
                'amount' => -500, // Negative amount
            ]);

        $response->assertStatus(422);
    }
}
