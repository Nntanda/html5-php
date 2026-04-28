<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanGuarantor;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Member $member;
    protected SavingsAccount $savingsAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and member with savings account
        $this->user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => Member::STATUS_ACTIVE,
        ]);
        $this->savingsAccount = SavingsAccount::factory()->create([
            'member_id' => $this->member->id,
            'balance' => 1000000,
            'created_at' => now()->subMonths(7),
        ]);
    }

    /**
     * Test successful loan application
     */
    public function test_apply_loan_successfully()
    {
        // Create guarantor members
        $guarantor1 = Member::factory()->create();
        $guarantor2 = Member::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/loans/apply', [
            'member_id' => $this->member->id,
            'amount' => 2000000,
            'term_months' => 12,
            'purpose' => 'Business expansion',
            'guarantors' => [
                [
                    'member_id' => $guarantor1->id,
                    'guaranteed_amount' => 1000000,
                ],
                [
                    'member_id' => $guarantor2->id,
                    'guaranteed_amount' => 1000000,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'loan' => [
                'id',
                'loan_number',
                'member',
                'amount',
                'interest_rate',
                'term_months',
                'purpose',
                'status',
                'monthly_repayment',
                'outstanding_balance',
                'application_date',
                'guarantors',
            ],
        ]);

        $this->assertDatabaseHas('loans', [
            'member_id' => $this->member->id,
            'amount' => 2000000,
            'status' => Loan::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('loan_guarantors', [
            'guarantor_member_id' => $guarantor1->id,
            'guaranteed_amount' => 1000000,
        ]);
    }

    /**
     * Test loan application with insufficient guarantor amount
     */
    public function test_apply_loan_insufficient_guarantor_amount()
    {
        $guarantor1 = Member::factory()->create();
        $guarantor2 = Member::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/loans/apply', [
            'member_id' => $this->member->id,
            'amount' => 2000000,
            'term_months' => 12,
            'purpose' => 'Business expansion',
            'guarantors' => [
                [
                    'member_id' => $guarantor1->id,
                    'guaranteed_amount' => 500000,
                ],
                [
                    'member_id' => $guarantor2->id,
                    'guaranteed_amount' => 500000,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Total guaranteed amount must be at least equal to loan amount',
        ]);
    }

    /**
     * Test loan application exceeding maximum eligible amount
     */
    public function test_apply_loan_exceeds_max_eligible()
    {
        $guarantor1 = Member::factory()->create();
        $guarantor2 = Member::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/loans/apply', [
            'member_id' => $this->member->id,
            'amount' => 5000000, // Exceeds max of 3,000,000
            'term_months' => 12,
            'purpose' => 'Business expansion',
            'guarantors' => [
                [
                    'member_id' => $guarantor1->id,
                    'guaranteed_amount' => 2500000,
                ],
                [
                    'member_id' => $guarantor2->id,
                    'guaranteed_amount' => 2500000,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Loan application not eligible',
        ]);
    }

    /**
     * Test loan application with insufficient guarantors
     */
    public function test_apply_loan_insufficient_guarantors()
    {
        $guarantor1 = Member::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/loans/apply', [
            'member_id' => $this->member->id,
            'amount' => 1000000,
            'term_months' => 12,
            'purpose' => 'Business expansion',
            'guarantors' => [
                [
                    'member_id' => $guarantor1->id,
                    'guaranteed_amount' => 1000000,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test getting loan details
     */
    public function test_get_loan_details()
    {
        $loan = Loan::factory()->create(['member_id' => $this->member->id]);
        $guarantor = Member::factory()->create();
        LoanGuarantor::factory()->create([
            'loan_id' => $loan->id,
            'guarantor_member_id' => $guarantor->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/loans/{$loan->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'loan' => [
                'id',
                'loan_number',
                'member',
                'amount',
                'status',
                'guarantors',
            ],
        ]);
    }

    /**
     * Test listing loans
     */
    public function test_list_loans()
    {
        Loan::factory()->count(3)->create(['member_id' => $this->member->id]);

        $response = $this->actingAs($this->user)->getJson('/api/loans');

        $response->assertStatus(200);
        $response->assertJsonStructure([
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
     * Test listing loans with status filter
     */
    public function test_list_loans_with_status_filter()
    {
        Loan::factory()->create([
            'member_id' => $this->member->id,
            'status' => Loan::STATUS_PENDING,
        ]);
        Loan::factory()->create([
            'member_id' => $this->member->id,
            'status' => Loan::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/loans?status=pending');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    /**
     * Test updating loan application
     */
    public function test_update_loan_application()
    {
        $loan = Loan::factory()->create([
            'member_id' => $this->member->id,
            'status' => Loan::STATUS_PENDING,
            'amount' => 1000000,
            'term_months' => 12,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/loans/{$loan->id}", [
            'amount' => 1500000,
            'term_months' => 18,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'amount' => 1500000,
            'term_months' => 18,
        ]);
    }

    /**
     * Test cannot update non-pending loan
     */
    public function test_cannot_update_non_pending_loan()
    {
        $loan = Loan::factory()->create([
            'member_id' => $this->member->id,
            'status' => Loan::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/loans/{$loan->id}", [
            'amount' => 1500000,
        ]);

        $response->assertStatus(422);
    }
}
