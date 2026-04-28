<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\User;
use Tests\TestCase;

class ReportTest extends TestCase
{
    private User $user;
    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->member = Member::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_member_statement_endpoint(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/reports/member/{$this->member->id}/statement");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'member_id',
                'member_number',
                'member_name',
                'period',
                'savings',
                'loans',
                'transactions',
            ],
        ]);
    }

    public function test_member_loan_summary_endpoint(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/reports/member/{$this->member->id}/loan-summary");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'member_id',
                'member_number',
                'member_name',
                'period',
                'summary',
                'loans',
            ],
        ]);
    }

    public function test_savings_summary_endpoint(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/reports/savings-summary');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'period',
                'summary',
                'by_source',
                'transaction_count',
            ],
        ]);
    }

    public function test_loans_summary_endpoint(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/reports/loans-summary');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'period',
                'summary',
                'by_status',
                'repayments_in_period',
            ],
        ]);
    }

    public function test_transactions_report_endpoint(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/reports/transactions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'period',
                'summary',
                'transactions',
            ],
        ]);
    }

    public function test_overdue_loans_report_endpoint(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/reports/overdue-loans');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'as_of_date',
                'summary',
                'loans',
            ],
        ]);
    }

    public function test_member_statement_with_date_range(): void
    {
        $startDate = now()->subMonths(3)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->getJson("/api/reports/member/{$this->member->id}/statement?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
        $this->assertEquals($startDate, $response->json('data.period.start_date'));
        $this->assertEquals($endDate, $response->json('data.period.end_date'));
    }

    public function test_transactions_report_with_type_filter(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/reports/transactions?type=savings');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'period',
                'summary',
                'transactions',
            ],
        ]);
    }

    public function test_member_statement_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/reports/member/99999/statement');

        $response->assertStatus(404);
    }
}
