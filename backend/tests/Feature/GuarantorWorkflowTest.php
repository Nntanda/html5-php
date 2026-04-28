<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanGuarantor;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuarantorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Member $member;
    protected Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->member = Member::factory()->create(['user_id' => $this->user->id]);
        $this->loan = Loan::factory()->create(['member_id' => $this->member->id]);
    }

    /**
     * Test adding guarantor to loan
     */
    public function test_add_guarantor_to_loan()
    {
        $guarantor = Member::factory()->create();

        $response = $this->actingAs($this->user)->postJson(
            "/api/loans/{$this->loan->id}/guarantors",
            [
                'guarantor_member_id' => $guarantor->id,
                'guaranteed_amount' => 1000000,
            ]
        );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'guarantor' => [
                'id',
                'loan_id',
                'member',
                'guaranteed_amount',
                'status',
            ],
        ]);

        $this->assertDatabaseHas('loan_guarantors', [
            'loan_id' => $this->loan->id,
            'guarantor_member_id' => $guarantor->id,
            'guaranteed_amount' => 1000000,
            'status' => LoanGuarantor::STATUS_PENDING,
        ]);
    }

    /**
     * Test cannot add guarantor to non-pending loan
     */
    public function test_cannot_add_guarantor_to_non_pending_loan()
    {
        $loan = Loan::factory()->create([
            'member_id' => $this->member->id,
            'status' => Loan::STATUS_ACTIVE,
        ]);
        $guarantor = Member::factory()->create();

        $response = $this->actingAs($this->user)->postJson(
            "/api/loans/{$loan->id}/guarantors",
            [
                'guarantor_member_id' => $guarantor->id,
                'guaranteed_amount' => 1000000,
            ]
        );

        $response->assertStatus(422);
    }

    /**
     * Test cannot add loan applicant as guarantor
     */
    public function test_cannot_add_applicant_as_guarantor()
    {
        $response = $this->actingAs($this->user)->postJson(
            "/api/loans/{$this->loan->id}/guarantors",
            [
                'guarantor_member_id' => $this->member->id,
                'guaranteed_amount' => 1000000,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Loan applicant cannot be a guarantor',
        ]);
    }

    /**
     * Test cannot add duplicate guarantor
     */
    public function test_cannot_add_duplicate_guarantor()
    {
        $guarantor = Member::factory()->create();

        // Add guarantor first time
        $this->actingAs($this->user)->postJson(
            "/api/loans/{$this->loan->id}/guarantors",
            [
                'guarantor_member_id' => $guarantor->id,
                'guaranteed_amount' => 1000000,
            ]
        );

        // Try to add same guarantor again
        $response = $this->actingAs($this->user)->postJson(
            "/api/loans/{$this->loan->id}/guarantors",
            [
                'guarantor_member_id' => $guarantor->id,
                'guaranteed_amount' => 500000,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'This member is already a guarantor for this loan',
        ]);
    }

    /**
     * Test guarantor approval
     */
    public function test_guarantor_approval()
    {
        $guarantor = Member::factory()->create();
        $loanGuarantor = LoanGuarantor::factory()->create([
            'loan_id' => $this->loan->id,
            'guarantor_member_id' => $guarantor->id,
            'status' => LoanGuarantor::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->user)->putJson(
            "/api/loans/{$this->loan->id}/guarantors/{$loanGuarantor->id}",
            [
                'status' => 'accepted',
            ]
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('loan_guarantors', [
            'id' => $loanGuarantor->id,
            'status' => LoanGuarantor::STATUS_ACCEPTED,
        ]);
    }

    /**
     * Test guarantor rejection
     */
    public function test_guarantor_rejection()
    {
        $guarantor = Member::factory()->create();
        $loanGuarantor = LoanGuarantor::factory()->create([
            'loan_id' => $this->loan->id,
            'guarantor_member_id' => $guarantor->id,
            'status' => LoanGuarantor::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->user)->putJson(
            "/api/loans/{$this->loan->id}/guarantors/{$loanGuarantor->id}",
            [
                'status' => 'rejected',
                'rejection_reason' => 'Cannot guarantee at this time',
            ]
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('loan_guarantors', [
            'id' => $loanGuarantor->id,
            'status' => LoanGuarantor::STATUS_REJECTED,
            'rejection_reason' => 'Cannot guarantee at this time',
        ]);
    }

    /**
     * Test loan status updates when all guarantors approve
     */
    public function test_loan_status_updates_when_all_guarantors_approve()
    {
        $guarantor1 = Member::factory()->create();
        $guarantor2 = Member::factory()->create();

        $loanGuarantor1 = LoanGuarantor::factory()->create([
            'loan_id' => $this->loan->id,
            'guarantor_member_id' => $guarantor1->id,
            'status' => LoanGuarantor::STATUS_PENDING,
        ]);

        $loanGuarantor2 = LoanGuarantor::factory()->create([
            'loan_id' => $this->loan->id,
            'guarantor_member_id' => $guarantor2->id,
            'status' => LoanGuarantor::STATUS_PENDING,
        ]);

        // Approve first guarantor
        $this->actingAs($this->user)->putJson(
            "/api/loans/{$this->loan->id}/guarantors/{$loanGuarantor1->id}",
            ['status' => 'accepted']
        );

        // Loan should still be pending
        $this->assertDatabaseHas('loans', [
            'id' => $this->loan->id,
            'status' => Loan::STATUS_PENDING,
        ]);

        // Approve second guarantor
        $this->actingAs($this->user)->putJson(
            "/api/loans/{$this->loan->id}/guarantors/{$loanGuarantor2->id}",
            ['status' => 'accepted']
        );

        // Loan should now be guarantors_approved
        $this->assertDatabaseHas('loans', [
            'id' => $this->loan->id,
            'status' => Loan::STATUS_GUARANTORS_APPROVED,
        ]);
    }

    /**
     * Test listing loan guarantors
     */
    public function test_list_loan_guarantors()
    {
        $guarantor1 = Member::factory()->create();
        $guarantor2 = Member::factory()->create();

        LoanGuarantor::factory()->create([
            'loan_id' => $this->loan->id,
            'guarantor_member_id' => $guarantor1->id,
        ]);

        LoanGuarantor::factory()->create([
            'loan_id' => $this->loan->id,
            'guarantor_member_id' => $guarantor2->id,
        ]);

        $response = $this->actingAs($this->user)->getJson(
            "/api/loans/{$this->loan->id}/guarantors"
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'loan_id',
            'loan_number',
            'guarantors' => [
                '*' => [
                    'id',
                    'loan_id',
                    'member',
                    'guaranteed_amount',
                    'status',
                ],
            ],
        ]);
        $this->assertCount(2, $response->json('guarantors'));
    }

    /**
     * Test getting pending guarantor requests for member
     */
    public function test_get_pending_guarantor_requests()
    {
        $guarantor = Member::factory()->create();
        $guarantorUser = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $guarantor->update(['user_id' => $guarantorUser->id]);

        $loan1 = Loan::factory()->create();
        $loan2 = Loan::factory()->create();

        LoanGuarantor::factory()->create([
            'loan_id' => $loan1->id,
            'guarantor_member_id' => $guarantor->id,
            'status' => LoanGuarantor::STATUS_PENDING,
        ]);

        LoanGuarantor::factory()->create([
            'loan_id' => $loan2->id,
            'guarantor_member_id' => $guarantor->id,
            'status' => LoanGuarantor::STATUS_ACCEPTED,
        ]);

        $response = $this->actingAs($guarantorUser)->getJson(
            "/api/members/{$guarantor->id}/guarantor-requests"
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'member_id',
            'member_number',
            'full_name',
            'pending_requests' => [
                '*' => [
                    'id',
                    'loan',
                    'guaranteed_amount',
                    'status',
                ],
            ],
        ]);
        // Should only have 1 pending request
        $this->assertCount(1, $response->json('pending_requests'));
    }

    /**
     * Test cannot update non-pending guarantor request
     */
    public function test_cannot_update_non_pending_guarantor_request()
    {
        $guarantor = Member::factory()->create();
        $loanGuarantor = LoanGuarantor::factory()->create([
            'loan_id' => $this->loan->id,
            'guarantor_member_id' => $guarantor->id,
            'status' => LoanGuarantor::STATUS_ACCEPTED,
        ]);

        $response = $this->actingAs($this->user)->putJson(
            "/api/loans/{$this->loan->id}/guarantors/{$loanGuarantor->id}",
            ['status' => 'rejected']
        );

        $response->assertStatus(422);
    }
}
