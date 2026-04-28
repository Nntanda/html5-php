<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    private ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = new ReportService();
    }

    public function test_generate_member_statement(): void
    {
        // Create test data
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $member = Member::factory()->create(['user_id' => $user->id]);
        $savingsAccount = SavingsAccount::factory()->create(['member_id' => $member->id]);

        // Add some transactions
        SavingsTransaction::factory()->create([
            'account_id' => $savingsAccount->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 100000,
            'transaction_date' => now()->subMonths(6),
        ]);

        $statement = $this->reportService->generateMemberStatement($member);

        $this->assertIsArray($statement);
        $this->assertEquals($member->id, $statement['member_id']);
        $this->assertEquals($member->member_number, $statement['member_number']);
        $this->assertArrayHasKey('savings', $statement);
        $this->assertArrayHasKey('loans', $statement);
    }

    public function test_generate_member_loan_summary(): void
    {
        // Create test data
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $member = Member::factory()->create(['user_id' => $user->id]);
        
        $loan = Loan::factory()->create([
            'member_id' => $member->id,
            'status' => Loan::STATUS_ACTIVE,
            'amount' => 500000,
            'interest_rate' => 12,
            'term_months' => 12,
        ]);

        $summary = $this->reportService->generateMemberLoanSummary($member);

        $this->assertIsArray($summary);
        $this->assertEquals($member->id, $summary['member_id']);
        $this->assertArrayHasKey('summary', $summary);
        $this->assertArrayHasKey('loans', $summary);
        $this->assertEquals(1, $summary['summary']['total_loans']);
    }

    public function test_generate_savings_summary(): void
    {
        // Create test data
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $member = Member::factory()->create(['user_id' => $user->id]);
        $savingsAccount = SavingsAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => 500000,
        ]);

        SavingsTransaction::factory()->create([
            'account_id' => $savingsAccount->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 100000,
            'transaction_date' => now(),
        ]);

        $summary = $this->reportService->generateSavingsSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('summary', $summary);
        $this->assertGreaterThan(0, $summary['summary']['total_members']);
        $this->assertGreaterThan(0, $summary['summary']['total_savings']);
    }

    public function test_generate_loans_summary(): void
    {
        // Create test data
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $member = Member::factory()->create(['user_id' => $user->id]);
        
        Loan::factory()->create([
            'member_id' => $member->id,
            'status' => Loan::STATUS_ACTIVE,
            'amount' => 500000,
        ]);

        $summary = $this->reportService->generateLoansSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('summary', $summary);
        $this->assertArrayHasKey('by_status', $summary);
        $this->assertGreaterThan(0, $summary['summary']['total_loans']);
    }

    public function test_generate_transaction_report(): void
    {
        // Create test data
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $member = Member::factory()->create(['user_id' => $user->id]);
        $savingsAccount = SavingsAccount::factory()->create(['member_id' => $member->id]);

        SavingsTransaction::factory()->create([
            'account_id' => $savingsAccount->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 100000,
            'transaction_date' => now(),
        ]);

        $report = $this->reportService->generateTransactionReport();

        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('transactions', $report);
    }

    public function test_generate_overdue_loans_report(): void
    {
        // Create test data
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $member = Member::factory()->create(['user_id' => $user->id]);
        
        $loan = Loan::factory()->create([
            'member_id' => $member->id,
            'status' => Loan::STATUS_ACTIVE,
            'amount' => 500000,
            'first_repayment_date' => now()->subMonths(3),
        ]);

        $report = $this->reportService->generateOverdueLoansReport();

        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('loans', $report);
    }

    public function test_member_statement_with_date_range(): void
    {
        // Create test data
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $member = Member::factory()->create(['user_id' => $user->id]);
        $savingsAccount = SavingsAccount::factory()->create(['member_id' => $member->id]);

        SavingsTransaction::factory()->create([
            'account_id' => $savingsAccount->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 100000,
            'transaction_date' => now()->subMonths(6),
        ]);

        $startDate = now()->subMonths(3);
        $endDate = now();

        $statement = $this->reportService->generateMemberStatement($member, $startDate, $endDate);

        $this->assertIsArray($statement);
        $this->assertEquals($startDate->format('Y-m-d'), $statement['period']['start_date']);
        $this->assertEquals($endDate->format('Y-m-d'), $statement['period']['end_date']);
    }
}
