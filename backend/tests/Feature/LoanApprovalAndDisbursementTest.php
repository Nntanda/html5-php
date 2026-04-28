<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanGuarantor;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanApprovalAndDisbursementTest extends TestCase
{
    use RefreshDatabase;

    protected User $loanOfficer;
    protected User $accountant;
    protected User $memberUser;
    protected Member $member;
    protected Member $guarantor1;
    protected Member $guarantor2;
    protected Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->loanOfficer = User::factory()->create(['role' => User::ROLE_LOAN_OFFICER]);
        $this->accountant = User::factory()->create(['role' => User::ROLE_ACCOUNTANT]);
        $this->memberUser = User::factory()->create(['role' => User::ROLE_MEMBER]);

        // Create member with savings account
        $this->member = Member::factory()->create([
            'user_id' => $this->memberUser->id,
            'status' => Member::STATUS_ACTIVE,
        ]);
        SavingsAccount::factory()->create([
            'member_id' => $this->member->id,
            'balance' => 1000000,
            'created_at' => now()->subMonths(7),
        ]);

        // Create guarantors
        $this->guarantor1 = Member::factory()->create();
        $this->guarantor2 = Member::factory()->create();

        // Create loan with guarantors approved
        $this->loan = Loan::factory()->create([
            'member_id' => $this->member->id,
            'status' => Loan::STATUS_GUARANTORS_APPROVED,
            'amount' => 2000000,
            'interest_rate' => 15,
            'term_months' => 12,
            'monthly_repayment' => 175832,
        ]);

        // Create approved guarantors
        LoanGuarantor::factory()->create([
            'loan_id' => $this->loan->id,
            'guarantor_member_id' => $this->guarantor1->id,
            'status' => LoanGuarantor::STATUS_ACCEPTED,
            'approval_date' => now()->toDateString(),
        ]);
        LoanGuarantor::factory()->create([
            'loan_id' => $this->loan->id,
            'guarantor_member_id' => $this->guarantor2->id,
            'status' => LoanGuarantor::STATUS_ACCEPTED,
            'approval_date' => now()->toDateString(),
        ]);
    }

    /**
     * Test loan approval by loan officer
     */
    public function test_approve_loan_successfully()
    {
        $response = $this->actingAs($this->loanOfficer)->putJson("/api/loans/{$this->loan->id}/approve", [
            'approval_comment' => 'Loan approved - all requirements met',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => 'Loan approved successfully',
            'status' => Loan::STATUS_APPROVED_PENDING_DISBURSEMENT,
        ]);

        $this->assertDatabaseHas('loans', [
            'id' => $this->loan->id,
            'status' => Loan::STATUS_APPROVED_PENDING_DISBURSEMENT,
            'approved_by' => $this->loanOfficer->id,
        ]);
    }

    /**
     * Test cannot approve loan without all guarantors approved
     */
    public function test_cannot_approve_loan_without_all_guarantors()
    {
        $loan = Loan::factory()->create([
            'member_id' => $this->member->id,
            'status' => Loan::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->loanOfficer)->putJson("/api/loans/{$loan->id}/approve", [
            'approval_comment' => 'Test',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Loan can only be approved when all guarantors have approved',
        ]);
    }

    /**
     * Test loan rejection by loan officer
     */
    public function test_reject_loan_successfully()
    {
        $response = $this->actingAs($this->loanOfficer)->putJson("/api/loans/{$this->loan->id}/reject", [
            'rejection_reason' => 'Insufficient guarantor coverage',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => 'Loan rejected successfully',
            'status' => Loan::STATUS_REJECTED,
        ]);

        $this->assertDatabaseHas('loans', [
            'id' => $this->loan->id,
            'status' => Loan::STATUS_REJECTED,
        ]);
    }

    /**
     * Test cannot reject loan not in guarantors_approved status
     */
    public function test_cannot_reject_loan_not_in_guarantors_approved()
    {
        $loan = Loan::factory()->create([
            'member_id' => $this->member->id,
            'status' => Loan::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->loanOfficer)->putJson("/api/loans/{$loan->id}/reject", [
            'rejection_reason' => 'Test',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test loan disbursement by accountant
     */
    public function test_disburse_loan_successfully()
    {
        // First approve the loan
        $this->loan->update(['status' => Loan::STATUS_APPROVED_PENDING_DISBURSEMENT]);

        $response = $this->actingAs($this->accountant)->putJson("/api/loans/{$this->loan->id}/disburse", [
            'disbursement_method' => Loan::DISBURSEMENT_BANK_TRANSFER,
            'first_repayment_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => 'Loan disbursed successfully',
            'status' => Loan::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('loans', [
            'id' => $this->loan->id,
            'status' => Loan::STATUS_ACTIVE,
            'disbursed_by' => $this->accountant->id,
        ]);

        // Verify savings account was credited
        $savingsAccount = $this->member->savingsAccount;
        $this->assertEquals(3000000, $savingsAccount->balance);
    }

    /**
     * Test cannot disburse loan not in approved_pending_disbursement status
     */
    public function test_cannot_disburse_loan_not_approved()
    {
        $response = $this->actingAs($this->accountant)->putJson("/api/loans/{$this->loan->id}/disburse", [
            'disbursement_method' => Loan::DISBURSEMENT_BANK_TRANSFER,
            'first_repayment_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Loan can only be disbursed when in approved_pending_disbursement status',
        ]);
    }

    /**
     * Test disbursement creates savings transaction
     */
    public function test_disbursement_creates_savings_transaction()
    {
        $this->loan->update(['status' => Loan::STATUS_APPROVED_PENDING_DISBURSEMENT]);

        $this->actingAs($this->accountant)->putJson("/api/loans/{$this->loan->id}/disburse", [
            'disbursement_method' => Loan::DISBURSEMENT_BANK_TRANSFER,
            'first_repayment_date' => now()->addMonth()->toDateString(),
        ]);

        $savingsAccount = $this->member->savingsAccount;
        $this->assertDatabaseHas('savings_transactions', [
            'account_id' => $savingsAccount->id,
            'type' => 'direct_deposit',
            'amount' => $this->loan->amount,
        ]);
    }

    /**
     * Test disbursement with different methods
     */
    public function test_disburse_loan_with_different_methods()
    {
        $methods = [
            Loan::DISBURSEMENT_BANK_TRANSFER,
            Loan::DISBURSEMENT_MOBILE_MONEY,
            Loan::DISBURSEMENT_CASH,
            Loan::DISBURSEMENT_CHEQUE,
        ];

        foreach ($methods as $method) {
            $loan = Loan::factory()->create([
                'member_id' => $this->member->id,
                'status' => Loan::STATUS_APPROVED_PENDING_DISBURSEMENT,
            ]);

            $response = $this->actingAs($this->accountant)->putJson("/api/loans/{$loan->id}/disburse", [
                'disbursement_method' => $method,
                'first_repayment_date' => now()->addMonth()->toDateString(),
            ]);

            $response->assertStatus(200);
            $this->assertDatabaseHas('loans', [
                'id' => $loan->id,
                'disbursement_method' => $method,
            ]);
        }
    }

    /**
     * Test first repayment date must be in future
     */
    public function test_first_repayment_date_must_be_future()
    {
        $this->loan->update(['status' => Loan::STATUS_APPROVED_PENDING_DISBURSEMENT]);

        $response = $this->actingAs($this->accountant)->putJson("/api/loans/{$this->loan->id}/disburse", [
            'disbursement_method' => Loan::DISBURSEMENT_BANK_TRANSFER,
            'first_repayment_date' => now()->subDay()->toDateString(),
        ]);

        $response->assertStatus(422);
    }
}
