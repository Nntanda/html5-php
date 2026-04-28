<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LoanService $loanService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loanService = new LoanService();
    }

    /**
     * Test calculating maximum loan amount based on savings
     */
    public function test_calculate_max_loan_amount()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $savingsAccount = SavingsAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => 1000000,
        ]);

        $maxLoanAmount = $this->loanService->calculateMaxLoanAmount($member);

        // With default multiplier of 3, max should be 3,000,000
        $this->assertEquals(3000000, $maxLoanAmount);
    }

    /**
     * Test calculating max loan amount for member without savings account
     */
    public function test_calculate_max_loan_amount_no_savings()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);

        $maxLoanAmount = $this->loanService->calculateMaxLoanAmount($member);

        $this->assertEquals(0, $maxLoanAmount);
    }

    /**
     * Test loan eligibility validation - eligible member
     */
    public function test_validate_loan_eligibility_eligible()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'status' => Member::STATUS_ACTIVE,
        ]);
        $savingsAccount = SavingsAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => 1000000,
            'created_at' => now()->subMonths(7),
        ]);

        $result = $this->loanService->validateLoanEligibility($member, 2000000);

        $this->assertTrue($result['eligible']);
        $this->assertEmpty($result['errors']);
        $this->assertEquals(3000000, $result['max_loan_amount']);
    }

    /**
     * Test loan eligibility validation - inactive member
     */
    public function test_validate_loan_eligibility_inactive_member()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'status' => Member::STATUS_INACTIVE,
        ]);

        $result = $this->loanService->validateLoanEligibility($member, 1000000);

        $this->assertFalse($result['eligible']);
        $this->assertContains('Member is not active', $result['errors']);
    }

    /**
     * Test loan eligibility validation - insufficient savings period
     */
    public function test_validate_loan_eligibility_insufficient_savings_period()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'status' => Member::STATUS_ACTIVE,
        ]);
        $savingsAccount = SavingsAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => 1000000,
            'created_at' => now()->subMonths(3),
        ]);

        $result = $this->loanService->validateLoanEligibility($member, 500000);

        $this->assertFalse($result['eligible']);
        $this->assertTrue(
            collect($result['errors'])->contains(
                fn($error) => str_contains($error, 'must have savings for at least')
            )
        );
    }

    /**
     * Test loan eligibility validation - exceeds maximum amount
     */
    public function test_validate_loan_eligibility_exceeds_max()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'status' => Member::STATUS_ACTIVE,
        ]);
        $savingsAccount = SavingsAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => 1000000,
            'created_at' => now()->subMonths(7),
        ]);

        $result = $this->loanService->validateLoanEligibility($member, 5000000);

        $this->assertFalse($result['eligible']);
        $this->assertTrue(
            collect($result['errors'])->contains(
                fn($error) => str_contains($error, 'exceeds maximum eligible amount')
            )
        );
    }

    /**
     * Test loan eligibility validation - has active loans
     */
    public function test_validate_loan_eligibility_has_active_loans()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'status' => Member::STATUS_ACTIVE,
        ]);
        $savingsAccount = SavingsAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => 1000000,
            'created_at' => now()->subMonths(7),
        ]);

        // Create an active loan
        Loan::factory()->create([
            'member_id' => $member->id,
            'status' => Loan::STATUS_ACTIVE,
        ]);

        $result = $this->loanService->validateLoanEligibility($member, 500000);

        $this->assertFalse($result['eligible']);
        $this->assertContains('Member has existing active loans', $result['errors']);
    }

    /**
     * Test loan eligibility validation - has overdue loans
     */
    public function test_validate_loan_eligibility_has_overdue_loans()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'status' => Member::STATUS_ACTIVE,
        ]);
        $savingsAccount = SavingsAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => 1000000,
            'created_at' => now()->subMonths(7),
        ]);

        // Create an overdue loan
        Loan::factory()->create([
            'member_id' => $member->id,
            'status' => Loan::STATUS_OVERDUE,
        ]);

        $result = $this->loanService->validateLoanEligibility($member, 500000);

        $this->assertFalse($result['eligible']);
        $this->assertContains('Member has overdue loans', $result['errors']);
    }

    /**
     * Test calculating monthly repayment
     */
    public function test_calculate_monthly_repayment()
    {
        $principal = 1000000;
        $annualRate = 15;
        $termMonths = 12;

        $monthlyPayment = $this->loanService->calculateMonthlyRepayment(
            $principal,
            $annualRate,
            $termMonths
        );

        // Monthly payment should be approximately 87,916
        $this->assertGreaterThan(85000, $monthlyPayment);
        $this->assertLessThan(90000, $monthlyPayment);
    }

    /**
     * Test calculating monthly repayment with zero interest
     */
    public function test_calculate_monthly_repayment_zero_interest()
    {
        $principal = 1200000;
        $annualRate = 0;
        $termMonths = 12;

        $monthlyPayment = $this->loanService->calculateMonthlyRepayment(
            $principal,
            $annualRate,
            $termMonths
        );

        // With zero interest, should be exactly principal / months
        $this->assertEquals(100000, $monthlyPayment);
    }

    /**
     * Test generating repayment schedule
     */
    public function test_generate_repayment_schedule()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create([
            'member_id' => $member->id,
            'amount' => 1000000,
            'interest_rate' => 15,
            'term_months' => 12,
            'monthly_repayment' => 87916,
            'first_repayment_date' => now()->addMonth(),
        ]);

        $schedule = $this->loanService->generateRepaymentSchedule($loan);

        $this->assertCount(12, $schedule);
        $this->assertEquals(1, $schedule[0]['month']);
        $this->assertGreaterThan(0, $schedule[0]['principal']);
        $this->assertGreaterThan(0, $schedule[0]['interest']);
        $this->assertEquals(87916, $schedule[0]['payment']);
    }

    /**
     * Test calculating interest
     */
    public function test_calculate_interest()
    {
        $principal = 1000000;
        $annualRate = 15;
        $days = 365;

        $interest = $this->loanService->calculateInterest($principal, $annualRate, $days);

        // Should be approximately 150,000 (15% of 1,000,000)
        $this->assertEquals(150000, $interest);
    }

    /**
     * Test calculating interest for partial year
     */
    public function test_calculate_interest_partial_year()
    {
        $principal = 1000000;
        $annualRate = 15;
        $days = 180; // Half year

        $interest = $this->loanService->calculateInterest($principal, $annualRate, $days);

        // Should be approximately 75,000 (half of 150,000)
        $this->assertEquals(75000, $interest);
    }

    /**
     * Test checking if loan is overdue
     */
    public function test_is_loan_overdue()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create([
            'member_id' => $member->id,
            'status' => Loan::STATUS_ACTIVE,
            'first_repayment_date' => now()->subDays(40),
        ]);

        $isOverdue = $this->loanService->isLoanOverdue($loan);

        $this->assertTrue($isOverdue);
    }

    /**
     * Test checking if loan is not overdue
     */
    public function test_is_loan_not_overdue()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create([
            'member_id' => $member->id,
            'status' => Loan::STATUS_ACTIVE,
            'first_repayment_date' => now()->subDays(10),
        ]);

        $isOverdue = $this->loanService->isLoanOverdue($loan);

        $this->assertFalse($isOverdue);
    }

    /**
     * Test calculating outstanding balance
     */
    public function test_calculate_outstanding_balance()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create([
            'member_id' => $member->id,
            'amount' => 1000000,
            'interest_rate' => 15,
            'status' => Loan::STATUS_ACTIVE,
            'first_repayment_date' => now()->subDays(10),
        ]);

        $balance = $this->loanService->calculateOutstandingBalance($loan);

        // Should be approximately 1,000,000 plus accrued interest
        $this->assertGreaterThan(1000000, $balance);
    }
}
