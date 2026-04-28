<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanRepaymentScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected LoanService $loanService;
    protected Member $member;
    protected Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loanService = new LoanService();

        $user = User::factory()->create();
        $this->member = Member::factory()->create(['user_id' => $user->id]);
        SavingsAccount::factory()->create(['member_id' => $this->member->id]);

        $this->loan = Loan::factory()->create([
            'member_id' => $this->member->id,
            'amount' => 2000000,
            'interest_rate' => 15,
            'term_months' => 12,
            'monthly_repayment' => 175832,
            'first_repayment_date' => now()->toDateString(),
        ]);
    }

    /**
     * Test generate repayment schedule
     */
    public function test_generate_repayment_schedule()
    {
        $schedule = $this->loanService->generateRepaymentSchedule($this->loan);

        $this->assertCount(12, $schedule);
        $this->assertEquals(1, $schedule[0]['month']);
        $this->assertArrayHasKey('due_date', $schedule[0]);
        $this->assertArrayHasKey('principal', $schedule[0]);
        $this->assertArrayHasKey('interest', $schedule[0]);
        $this->assertArrayHasKey('payment', $schedule[0]);
        $this->assertArrayHasKey('balance', $schedule[0]);
    }

    /**
     * Test repayment schedule principal and interest breakdown
     */
    public function test_repayment_schedule_breakdown()
    {
        $schedule = $this->loanService->generateRepaymentSchedule($this->loan);

        // First payment should have more interest than principal
        $firstPayment = $schedule[0];
        $this->assertGreater($firstPayment['interest'], $firstPayment['principal']);

        // Last payment should have more principal than interest
        $lastPayment = $schedule[11];
        $this->assertGreater($lastPayment['principal'], $lastPayment['interest']);

        // Total principal should equal loan amount
        $totalPrincipal = array_sum(array_column($schedule, 'principal'));
        $this->assertAlmostEquals($this->loan->amount, $totalPrincipal, 2);
    }

    /**
     * Test repayment schedule balance decreases
     */
    public function test_repayment_schedule_balance_decreases()
    {
        $schedule = $this->loanService->generateRepaymentSchedule($this->loan);

        for ($i = 0; $i < count($schedule) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $schedule[$i + 1]['balance'],
                $schedule[$i]['balance']
            );
        }

        // Final balance should be zero or very close
        $this->assertAlmostEquals(0, $schedule[11]['balance'], 2);
    }

    /**
     * Test repayment schedule with zero interest
     */
    public function test_repayment_schedule_zero_interest()
    {
        $loan = Loan::factory()->create([
            'member_id' => $this->member->id,
            'amount' => 1200000,
            'interest_rate' => 0,
            'term_months' => 12,
            'monthly_repayment' => 100000,
            'first_repayment_date' => now()->toDateString(),
        ]);

        $schedule = $this->loanService->generateRepaymentSchedule($loan);

        // All payments should be equal
        foreach ($schedule as $payment) {
            $this->assertEquals(100000, $payment['payment']);
            $this->assertEquals(0, $payment['interest']);
        }
    }

    /**
     * Test repayment schedule dates increment monthly
     */
    public function test_repayment_schedule_dates_increment()
    {
        $schedule = $this->loanService->generateRepaymentSchedule($this->loan);

        $firstDate = \Carbon\Carbon::parse($schedule[0]['due_date']);
        
        for ($i = 1; $i < count($schedule); $i++) {
            $currentDate = \Carbon\Carbon::parse($schedule[$i]['due_date']);
            $expectedDate = $firstDate->copy()->addMonths($i);
            
            $this->assertEquals(
                $expectedDate->format('Y-m-d'),
                $currentDate->format('Y-m-d')
            );
        }
    }

    /**
     * Test calculate outstanding balance
     */
    public function test_calculate_outstanding_balance()
    {
        $balance = $this->loanService->calculateOutstandingBalance($this->loan);
        
        $this->assertEquals($this->loan->amount, $balance);
    }

    /**
     * Test calculate outstanding balance with repayments
     */
    public function test_calculate_outstanding_balance_with_repayments()
    {
        $this->loan->update(['status' => 'active']);
        
        // Create a repayment
        $this->loan->repayments()->create([
            'amount' => 500000,
            'principal_amount' => 400000,
            'interest_amount' => 100000,
            'penalty_amount' => 0,
            'payment_date' => now()->toDateString(),
            'source' => 'manual',
            'reference' => 'TEST001',
            'recorded_by' => 1,
        ]);

        $balance = $this->loanService->calculateOutstandingBalance($this->loan);
        
        $this->assertLessThan($this->loan->amount, $balance);
    }

    /**
     * Test is loan overdue
     */
    public function test_is_loan_overdue()
    {
        $this->loan->update([
            'status' => 'active',
            'first_repayment_date' => now()->subDays(40)->toDateString(),
        ]);

        $isOverdue = $this->loanService->isLoanOverdue($this->loan);
        
        $this->assertTrue($isOverdue);
    }

    /**
     * Test is loan not overdue
     */
    public function test_is_loan_not_overdue()
    {
        $this->loan->update([
            'status' => 'active',
            'first_repayment_date' => now()->subDays(10)->toDateString(),
        ]);

        $isOverdue = $this->loanService->isLoanOverdue($this->loan);
        
        $this->assertFalse($isOverdue);
    }

    /**
     * Test calculate interest
     */
    public function test_calculate_interest()
    {
        $interest = $this->loanService->calculateInterest(1000000, 15, 365);
        
        $this->assertEquals(150000, $interest);
    }

    /**
     * Test calculate interest for partial year
     */
    public function test_calculate_interest_partial_year()
    {
        $interest = $this->loanService->calculateInterest(1000000, 15, 30);
        
        $expected = (1000000 * 15 / 100) * (30 / 365);
        $this->assertAlmostEquals($expected, $interest, 2);
    }

    /**
     * Helper to assert almost equals for floating point comparison
     */
    private function assertAlmostEquals($expected, $actual, $precision = 0)
    {
        $this->assertEquals($expected, round($actual, $precision), "Expected {$expected} but got {$actual}");
    }
}
