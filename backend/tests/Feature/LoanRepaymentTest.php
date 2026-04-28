<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanRepaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $accountant;
    protected User $memberUser;
    protected Member $member;
    protected Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountant = User::factory()->create(['role' => User::ROLE_ACCOUNTANT]);
        $this->memberUser = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->member = Member::factory()->create([
            'user_id' => $this->memberUser->id,
            'status' => Member::STATUS_ACTIVE,
        ]);

        SavingsAccount::factory()->create([
            'member_id' => $this->member->id,
            'balance' => 5000000,
        ]);

        $this->loan = Loan::factory()->create([
            'member_id' => $this->member->id,
            'status' => Loan::STATUS_ACTIVE,
            'amount' => 2000000,
            'interest_rate' => 15,
            'term_months' => 12,
            'monthly_repayment' => 175832,
            'outstanding_balance' => 2000000,
            'first_repayment_date' => now()->toDateString(),
        ]);
    }

    /**
     * Test record manual repayment
     */
    public function test_record_manual_repayment()
    {
        $response = $this->actingAs($this->accountant)->postJson("/api/loans/{$this->loan->id}/repayments", [
            'amount' => 175832,
            'payment_date' => now()->toDateString(),
            'source' => LoanRepayment::SOURCE_MANUAL,
            'reference' => 'MANUAL_001',
            'notes' => 'First monthly payment',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'message' => 'Repayment recorded successfully',
        ]);

        $this->assertDatabaseHas('loan_repayments', [
            'loan_id' => $this->loan->id,
            'amount' => 175832,
            'source' => LoanRepayment::SOURCE_MANUAL,
        ]);
    }

    /**
     * Test cannot record repayment for non-active loan
     */
    public function test_cannot_record_repayment_for_non_active_loan()
    {
        $loan = Loan::factory()->create([
            'member_id' => $this->member->id,
            'status' => Loan::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->accountant)->postJson("/api/loans/{$loan->id}/repayments", [
            'amount' => 100000,
            'payment_date' => now()->toDateString(),
            'source' => LoanRepayment::SOURCE_MANUAL,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Loan must be active to record repayments',
        ]);
    }

    /**
     * Test repayment updates outstanding balance
     */
    public function test_repayment_updates_outstanding_balance()
    {
        $this->actingAs($this->accountant)->postJson("/api/loans/{$this->loan->id}/repayments", [
            'amount' => 500000,
            'payment_date' => now()->toDateString(),
            'source' => LoanRepayment::SOURCE_MANUAL,
        ]);

        $this->loan->refresh();
        $this->assertLessThan(2000000, $this->loan->outstanding_balance);
    }

    /**
     * Test loan closes when fully repaid
     */
    public function test_loan_closes_when_fully_repaid()
    {
        $this->actingAs($this->accountant)->postJson("/api/loans/{$this->loan->id}/repayments", [
            'amount' => 2000000,
            'payment_date' => now()->toDateString(),
            'source' => LoanRepayment::SOURCE_MANUAL,
        ]);

        $this->loan->refresh();
        $this->assertEquals(Loan::STATUS_CLOSED, $this->loan->status);
    }

    /**
     * Test get loan repayments
     */
    public function test_get_loan_repayments()
    {
        LoanRepayment::factory()->count(3)->create(['loan_id' => $this->loan->id]);

        $response = $this->actingAs($this->accountant)->getJson("/api/loans/{$this->loan->id}/repayments");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'loan_id',
            'loan_number',
            'data',
            'meta' => [
                'current_page',
                'total',
                'per_page',
                'last_page',
            ],
        ]);
    }

    /**
     * Test upload salary deduction repayments
     */
    public function test_upload_salary_deduction_repayments()
    {
        $csvContent = "loan_number,member_number,amount,payment_date\n";
        $csvContent .= "{$this->loan->loan_number},{$this->member->member_number},175832," . now()->toDateString() . "\n";

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'repayments.csv',
            $csvContent
        );

        $response = $this->actingAs($this->accountant)->postJson('/api/loans/repayments/upload-deductions', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => 'Salary deduction repayments processed',
        ]);

        $this->assertDatabaseHas('loan_repayments', [
            'loan_id' => $this->loan->id,
            'source' => LoanRepayment::SOURCE_SALARY_DEDUCTION,
        ]);
    }

    /**
     * Test salary deduction CSV with invalid loan number
     */
    public function test_salary_deduction_csv_with_invalid_loan()
    {
        $csvContent = "loan_number,member_number,amount,payment_date\n";
        $csvContent .= "INVALID_LOAN,{$this->member->member_number},175832," . now()->toDateString() . "\n";

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'repayments.csv',
            $csvContent
        );

        $response = $this->actingAs($this->accountant)->postJson('/api/loans/repayments/upload-deductions', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'failed_records' => 1,
        ]);
    }

    /**
     * Test salary deduction CSV with member mismatch
     */
    public function test_salary_deduction_csv_with_member_mismatch()
    {
        $otherMember = Member::factory()->create();

        $csvContent = "loan_number,member_number,amount,payment_date\n";
        $csvContent .= "{$this->loan->loan_number},{$otherMember->member_number},175832," . now()->toDateString() . "\n";

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'repayments.csv',
            $csvContent
        );

        $response = $this->actingAs($this->accountant)->postJson('/api/loans/repayments/upload-deductions', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'failed_records' => 1,
        ]);
    }

    /**
     * Test get loan tracking information
     */
    public function test_get_loan_tracking_info()
    {
        LoanRepayment::factory()->create([
            'loan_id' => $this->loan->id,
            'amount' => 175832,
            'payment_date' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->actingAs($this->accountant)->getJson("/api/loans/{$this->loan->id}/tracking");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'loan' => [
                'id',
                'loan_number',
                'amount',
                'status',
                'interest_rate',
                'term_months',
                'monthly_repayment',
            ],
            'tracking' => [
                'outstanding_balance',
                'total_repaid',
                'remaining_payments',
                'is_overdue',
                'next_payment_due',
                'disbursement_date',
                'first_repayment_date',
            ],
            'payment_history',
            'repayment_schedule',
        ]);
    }

    /**
     * Test repayment with different sources
     */
    public function test_record_repayment_with_different_sources()
    {
        $sources = [
            LoanRepayment::SOURCE_MANUAL,
            LoanRepayment::SOURCE_CASH,
            LoanRepayment::SOURCE_BANK_TRANSFER,
            LoanRepayment::SOURCE_MOBILE_MONEY,
        ];

        foreach ($sources as $source) {
            $loan = Loan::factory()->create([
                'member_id' => $this->member->id,
                'status' => Loan::STATUS_ACTIVE,
                'outstanding_balance' => 1000000,
            ]);

            $response = $this->actingAs($this->accountant)->postJson("/api/loans/{$loan->id}/repayments", [
                'amount' => 100000,
                'payment_date' => now()->toDateString(),
                'source' => $source,
            ]);

            $response->assertStatus(201);
            $this->assertDatabaseHas('loan_repayments', [
                'loan_id' => $loan->id,
                'source' => $source,
            ]);
        }
    }

    /**
     * Test repayment amount cannot exceed outstanding balance
     */
    public function test_repayment_capped_at_outstanding_balance()
    {
        $this->loan->update(['outstanding_balance' => 500000]);

        $response = $this->actingAs($this->accountant)->postJson("/api/loans/{$this->loan->id}/repayments", [
            'amount' => 1000000,
            'payment_date' => now()->toDateString(),
            'source' => LoanRepayment::SOURCE_MANUAL,
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'loan_outstanding_balance' => 0,
        ]);
    }

    /**
     * Test multiple repayments on same loan
     */
    public function test_multiple_repayments_on_same_loan()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->accountant)->postJson("/api/loans/{$this->loan->id}/repayments", [
                'amount' => 500000,
                'payment_date' => now()->addDays($i)->toDateString(),
                'source' => LoanRepayment::SOURCE_MANUAL,
            ]);
        }

        $repayments = LoanRepayment::where('loan_id', $this->loan->id)->count();
        $this->assertEquals(3, $repayments);
    }
}
