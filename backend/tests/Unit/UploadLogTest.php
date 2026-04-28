<?php

namespace Tests\Unit;

use App\Models\UploadLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * Test upload log creation with all attributes
     */
    public function test_upload_log_created_with_all_attributes()
    {
        $uploadLog = UploadLog::create([
            'user_id' => $this->user->id,
            'upload_type' => UploadLog::TYPE_SALARY_DEDUCTIONS,
            'file_name' => 'salary_deductions.csv',
            'total_records' => 100,
            'successful_records' => 95,
            'failed_records' => 5,
            'total_amount_processed' => 500000,
            'salary_period' => 'January 2024',
            'status' => UploadLog::STATUS_COMPLETED,
        ]);

        $this->assertDatabaseHas('upload_logs', [
            'id' => $uploadLog->id,
            'user_id' => $this->user->id,
            'upload_type' => UploadLog::TYPE_SALARY_DEDUCTIONS,
            'file_name' => 'salary_deductions.csv',
            'total_records' => 100,
            'successful_records' => 95,
            'failed_records' => 5,
            'total_amount_processed' => 500000,
            'salary_period' => 'January 2024',
            'status' => UploadLog::STATUS_COMPLETED,
        ]);
    }

    /**
     * Test upload log with errors array
     */
    public function test_upload_log_with_errors_array()
    {
        $errors = [
            [
                'line' => 2,
                'member_number' => 'MEM999',
                'error' => 'Member not found',
            ],
        ];

        $uploadLog = UploadLog::create([
            'user_id' => $this->user->id,
            'upload_type' => UploadLog::TYPE_SALARY_DEDUCTIONS,
            'file_name' => 'salary_deductions.csv',
            'total_records' => 1,
            'successful_records' => 0,
            'failed_records' => 1,
            'total_amount_processed' => 0,
            'status' => UploadLog::STATUS_FAILED,
            'errors' => $errors,
        ]);

        $this->assertIsArray($uploadLog->fresh()->errors);
        $this->assertCount(1, $uploadLog->fresh()->errors);
        $this->assertEquals('Member not found', $uploadLog->fresh()->errors[0]['error']);
    }

    /**
     * Test upload log with summary array
     */
    public function test_upload_log_with_summary_array()
    {
        $summary = [
            'total_records' => 100,
            'successful' => 95,
            'failed' => 5,
            'total_amount_processed' => 500000,
            'salary_period' => 'January 2024',
            'processed_at' => now()->toIso8601String(),
        ];

        $uploadLog = UploadLog::create([
            'user_id' => $this->user->id,
            'upload_type' => UploadLog::TYPE_SALARY_DEDUCTIONS,
            'file_name' => 'salary_deductions.csv',
            'total_records' => 100,
            'successful_records' => 95,
            'failed_records' => 5,
            'total_amount_processed' => 500000,
            'status' => UploadLog::STATUS_COMPLETED,
            'summary' => $summary,
        ]);

        $this->assertIsArray($uploadLog->fresh()->summary);
        $this->assertEquals(95, $uploadLog->fresh()->summary['successful']);
        $this->assertEquals(500000, $uploadLog->fresh()->summary['total_amount_processed']);
    }

    /**
     * Test upload log status constants
     */
    public function test_upload_log_status_constants()
    {
        $this->assertEquals('completed', UploadLog::STATUS_COMPLETED);
        $this->assertEquals('failed', UploadLog::STATUS_FAILED);
        $this->assertEquals('partial', UploadLog::STATUS_PARTIAL);
    }

    /**
     * Test upload log type constants
     */
    public function test_upload_log_type_constants()
    {
        $this->assertEquals('salary_deductions', UploadLog::TYPE_SALARY_DEDUCTIONS);
        $this->assertEquals('loan_repayments', UploadLog::TYPE_LOAN_REPAYMENTS);
    }

    /**
     * Test upload log relationship to user
     */
    public function test_upload_log_relationship_to_user()
    {
        $uploadLog = UploadLog::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals($this->user->id, $uploadLog->user->id);
    }

    /**
     * Test upload log with null user
     */
    public function test_upload_log_with_null_user()
    {
        $uploadLog = UploadLog::create([
            'user_id' => null,
            'upload_type' => UploadLog::TYPE_SALARY_DEDUCTIONS,
            'file_name' => 'salary_deductions.csv',
            'total_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
            'total_amount_processed' => 0,
            'status' => UploadLog::STATUS_FAILED,
        ]);

        $this->assertNull($uploadLog->fresh()->user_id);
    }

    /**
     * Test upload log amount casting
     */
    public function test_upload_log_amount_casting()
    {
        $uploadLog = UploadLog::create([
            'user_id' => $this->user->id,
            'upload_type' => UploadLog::TYPE_SALARY_DEDUCTIONS,
            'file_name' => 'salary_deductions.csv',
            'total_records' => 1,
            'successful_records' => 1,
            'failed_records' => 0,
            'total_amount_processed' => 50000.50,
            'status' => UploadLog::STATUS_COMPLETED,
        ]);

        $this->assertIsString($uploadLog->fresh()->total_amount_processed);
        $this->assertEquals('50000.50', $uploadLog->fresh()->total_amount_processed);
    }

    /**
     * Test upload log factory
     */
    public function test_upload_log_factory()
    {
        $uploadLog = UploadLog::factory()->create();

        $this->assertNotNull($uploadLog->id);
        $this->assertNotNull($uploadLog->user_id);
        $this->assertEquals(UploadLog::TYPE_SALARY_DEDUCTIONS, $uploadLog->upload_type);
        $this->assertStringContainsString('salary_deductions', $uploadLog->file_name);
    }

    /**
     * Test upload log factory completed state
     */
    public function test_upload_log_factory_completed_state()
    {
        $uploadLog = UploadLog::factory()->completed()->create();

        $this->assertEquals(UploadLog::STATUS_COMPLETED, $uploadLog->status);
        $this->assertEquals(0, $uploadLog->failed_records);
    }

    /**
     * Test upload log factory failed state
     */
    public function test_upload_log_factory_failed_state()
    {
        $uploadLog = UploadLog::factory()->failed()->create();

        $this->assertEquals(UploadLog::STATUS_FAILED, $uploadLog->status);
        $this->assertEquals(0, $uploadLog->successful_records);
        $this->assertNotNull($uploadLog->error_message);
    }

    /**
     * Test upload log factory partial state
     */
    public function test_upload_log_factory_partial_state()
    {
        $uploadLog = UploadLog::factory()->partial()->create();

        $this->assertEquals(UploadLog::STATUS_PARTIAL, $uploadLog->status);
    }
}
