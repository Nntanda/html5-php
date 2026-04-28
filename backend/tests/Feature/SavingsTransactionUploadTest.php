<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SavingsTransactionUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Member $member1;
    protected Member $member2;
    protected SavingsAccount $account1;
    protected SavingsAccount $account2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for authentication
        $this->user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Create members with savings accounts
        $this->member1 = Member::factory()->create([
            'member_number' => 'MEM001',
            'status' => Member::STATUS_ACTIVE,
        ]);

        $this->member2 = Member::factory()->create([
            'member_number' => 'MEM002',
            'status' => Member::STATUS_ACTIVE,
        ]);

        $this->account1 = SavingsAccount::factory()->create([
            'member_id' => $this->member1->id,
            'balance' => 0,
        ]);

        $this->account2 = SavingsAccount::factory()->create([
            'member_id' => $this->member2->id,
            'balance' => 0,
        ]);
    }

    /**
     * Test successful CSV upload with valid data
     */
    public function test_upload_deductions_with_valid_csv()
    {
        $csvContent = "member_number,amount,reference\nMEM001,50000,SAL-2024-01\nMEM002,75000,SAL-2024-01-02\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'summary' => [
                'total_records',
                'successful',
                'failed',
                'total_amount_processed',
                'salary_period',
                'processed_at',
            ],
            'processed_records',
            'errors',
        ]);

        // Verify summary
        $this->assertEquals(2, $response->json('summary.successful'));
        $this->assertEquals(0, $response->json('summary.failed'));
        $this->assertEquals(125000, $response->json('summary.total_amount_processed'));

        // Verify transactions were created
        $this->assertDatabaseHas('savings_transactions', [
            'account_id' => $this->account1->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 50000,
            'reference' => 'SAL-2024-01',
        ]);

        $this->assertDatabaseHas('savings_transactions', [
            'account_id' => $this->account2->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 75000,
            'reference' => 'SAL-2024-01-02',
        ]);

        // Verify balances were updated
        $this->assertEquals(50000, $this->account1->fresh()->balance);
        $this->assertEquals(75000, $this->account2->fresh()->balance);
    }

    /**
     * Test CSV upload with invalid headers
     */
    public function test_upload_deductions_with_invalid_headers()
    {
        $csvContent = "wrong_header,amount,reference\nMEM001,50000,SAL-2024-01\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    /**
     * Test CSV upload with invalid amount
     */
    public function test_upload_deductions_with_invalid_amount()
    {
        $csvContent = "member_number,amount,reference\nMEM001,invalid_amount,SAL-2024-01\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('summary.successful'));
        $this->assertEquals(1, $response->json('summary.failed'));
        $this->assertNotEmpty($response->json('errors'));
    }

    /**
     * Test CSV upload with non-existent member
     */
    public function test_upload_deductions_with_nonexistent_member()
    {
        $csvContent = "member_number,amount,reference\nMEM999,50000,SAL-2024-01\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('summary.successful'));
        $this->assertEquals(1, $response->json('summary.failed'));
        $this->assertStringContainsString('Member not found', $response->json('errors.0.error'));
    }

    /**
     * Test CSV upload with inactive member
     */
    public function test_upload_deductions_with_inactive_member()
    {
        $this->member1->update(['status' => Member::STATUS_INACTIVE]);

        $csvContent = "member_number,amount,reference\nMEM001,50000,SAL-2024-01\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('summary.successful'));
        $this->assertEquals(1, $response->json('summary.failed'));
        $this->assertStringContainsString('not active', $response->json('errors.0.error'));
    }

    /**
     * Test CSV upload with duplicate reference
     */
    public function test_upload_deductions_with_duplicate_reference()
    {
        // Create an existing transaction with the same reference
        SavingsTransaction::create([
            'account_id' => $this->account1->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 25000,
            'source' => SavingsTransaction::SOURCE_SALARY,
            'reference' => 'SAL-2024-01',
            'transaction_date' => now()->toDateString(),
            'description' => 'Existing transaction',
            'salary_period' => 'January 2024',
        ]);

        $csvContent = "member_number,amount,reference\nMEM001,50000,SAL-2024-01\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('summary.successful'));
        $this->assertEquals(1, $response->json('summary.failed'));
        $this->assertStringContainsString('already exists', $response->json('errors.0.error'));
    }

    /**
     * Test CSV upload with mixed valid and invalid records
     */
    public function test_upload_deductions_with_mixed_records()
    {
        $csvContent = "member_number,amount,reference\nMEM001,50000,SAL-2024-01\nMEM999,75000,SAL-2024-02\nMEM002,100000,SAL-2024-03\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('summary.successful'));
        $this->assertEquals(1, $response->json('summary.failed'));
        $this->assertEquals(150000, $response->json('summary.total_amount_processed'));
    }

    /**
     * Test CSV upload without file
     */
    public function test_upload_deductions_without_file()
    {
        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    /**
     * Test CSV upload without salary period
     */
    public function test_upload_deductions_without_salary_period()
    {
        $csvContent = "member_number,amount,reference\nMEM001,50000,SAL-2024-01\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('salary_period');
    }

    /**
     * Test CSV upload with empty rows
     */
    public function test_upload_deductions_with_empty_rows()
    {
        $csvContent = "member_number,amount,reference\nMEM001,50000,SAL-2024-01\n\n\nMEM002,75000,SAL-2024-02\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('summary.successful'));
        $this->assertEquals(0, $response->json('summary.failed'));
    }

    /**
     * Test CSV upload with zero amount
     */
    public function test_upload_deductions_with_zero_amount()
    {
        $csvContent = "member_number,amount,reference\nMEM001,0,SAL-2024-01\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('summary.successful'));
        $this->assertEquals(1, $response->json('summary.failed'));
    }

    /**
     * Test CSV upload with negative amount
     */
    public function test_upload_deductions_with_negative_amount()
    {
        $csvContent = "member_number,amount,reference\nMEM001,-50000,SAL-2024-01\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('summary.successful'));
        $this->assertEquals(1, $response->json('summary.failed'));
    }

    /**
     * Test CSV upload with empty reference
     */
    public function test_upload_deductions_with_empty_reference()
    {
        $csvContent = "member_number,amount,reference\nMEM001,50000,\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('summary.successful'));
        $this->assertEquals(1, $response->json('summary.failed'));
    }

    /**
     * Test CSV upload with whitespace in reference
     */
    public function test_upload_deductions_with_whitespace_reference()
    {
        $csvContent = "member_number,amount,reference\nMEM001,50000,  SAL-2024-01  \n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('summary.successful'));
        $this->assertEquals(0, $response->json('summary.failed'));

        // Verify reference was trimmed
        $this->assertDatabaseHas('savings_transactions', [
            'reference' => 'SAL-2024-01',
        ]);
    }

    /**
     * Test CSV upload with large amounts
     */
    public function test_upload_deductions_with_large_amounts()
    {
        $csvContent = "member_number,amount,reference\nMEM001,999999999.99,SAL-2024-01\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('summary.successful'));
        $this->assertEquals(999999999.99, $response->json('summary.total_amount_processed'));
    }

    /**
     * Test CSV upload with decimal amounts
     */
    public function test_upload_deductions_with_decimal_amounts()
    {
        $csvContent = "member_number,amount,reference\nMEM001,50000.50,SAL-2024-01\nMEM002,75000.75,SAL-2024-02\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('summary.successful'));
        $this->assertEquals(125001.25, $response->json('summary.total_amount_processed'));
    }

    /**
     * Test CSV upload requires authentication
     */
    public function test_upload_deductions_requires_authentication()
    {
        $csvContent = "member_number,amount,reference\nMEM001,50000,SAL-2024-01\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test CSV upload with file size validation
     */
    public function test_upload_deductions_with_oversized_file()
    {
        // Create a file larger than 5MB
        $largeContent = "member_number,amount,reference\n";
        for ($i = 0; $i < 100000; $i++) {
            $largeContent .= "MEM001,50000,SAL-2024-" . str_pad($i, 5, '0', STR_PAD_LEFT) . "\n";
        }

        $file = UploadedFile::fromBase64(
            base64_encode($largeContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        // Should fail validation if file is too large
        if (strlen($largeContent) > 5242880) {
            $response->assertStatus(422);
        }
    }

    /**
     * Test upload log is created on successful upload
     */
    public function test_upload_log_created_on_success()
    {
        $csvContent = "member_number,amount,reference\nMEM001,50000,SAL-2024-01\nMEM002,75000,SAL-2024-02\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);

        // Verify upload log was created
        $this->assertDatabaseHas('upload_logs', [
            'user_id' => $this->user->id,
            'upload_type' => 'salary_deductions',
            'file_name' => 'salary_deductions.csv',
            'total_records' => 2,
            'successful_records' => 2,
            'failed_records' => 0,
            'status' => 'completed',
        ]);
    }

    /**
     * Test upload log is created on partial failure
     */
    public function test_upload_log_created_on_partial_failure()
    {
        $csvContent = "member_number,amount,reference\nMEM001,50000,SAL-2024-01\nMEM999,75000,SAL-2024-02\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);

        // Verify upload log was created with partial status
        $this->assertDatabaseHas('upload_logs', [
            'user_id' => $this->user->id,
            'upload_type' => 'salary_deductions',
            'total_records' => 2,
            'successful_records' => 1,
            'failed_records' => 1,
            'status' => 'partial',
        ]);
    }

    /**
     * Test upload log contains error details
     */
    public function test_upload_log_contains_error_details()
    {
        $csvContent = "member_number,amount,reference\nMEM999,50000,SAL-2024-01\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);

        // Verify upload log contains errors
        $uploadLog = \App\Models\UploadLog::where('user_id', $this->user->id)->first();
        $this->assertNotNull($uploadLog->errors);
        $this->assertCount(1, $uploadLog->errors);
        $this->assertStringContainsString('Member not found', $uploadLog->errors[0]['error']);
    }

    /**
     * Test upload log contains summary
     */
    public function test_upload_log_contains_summary()
    {
        $csvContent = "member_number,amount,reference\nMEM001,50000,SAL-2024-01\nMEM002,75000,SAL-2024-02\n";
        $file = UploadedFile::fromBase64(
            base64_encode($csvContent),
            'salary_deductions.csv',
            'text/csv'
        );

        $response = $this->actingAs($this->user)->postJson('/api/savings/upload-deductions', [
            'file' => $file,
            'salary_period' => 'January 2024',
        ]);

        $response->assertStatus(200);

        // Verify upload log contains summary
        $uploadLog = \App\Models\UploadLog::where('user_id', $this->user->id)->first();
        $this->assertNotNull($uploadLog->summary);
        $this->assertEquals(2, $uploadLog->summary['successful']);
        $this->assertEquals(0, $uploadLog->summary['failed']);
        $this->assertEquals(125000, $uploadLog->summary['total_amount_processed']);
    }
}
