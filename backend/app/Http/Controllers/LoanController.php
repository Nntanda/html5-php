<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Member;
use App\Models\LoanGuarantor;
use App\Models\LoanRepayment;
use App\Models\SavingsTransaction;
use App\Services\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    protected LoanService $loanService;

    public function __construct(LoanService $loanService)
    {
        $this->loanService = $loanService;
    }

    /**
     * Submit loan application
     */
    public function apply(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'amount' => 'required|numeric|min:1000',
            'term_months' => 'required|integer|min:1|max:36',
            'purpose' => 'required|string|max:500',
            'guarantors' => 'required|array|min:2',
            'guarantors.*.member_id' => 'required|exists:members,id|distinct',
            'guarantors.*.guaranteed_amount' => 'required|numeric|min:1000',
        ]);

        $member = Member::findOrFail($validated['member_id']);

        // Validate eligibility
        $eligibility = $this->loanService->validateLoanEligibility(
            $member,
            (float) $validated['amount']
        );

        if (!$eligibility['eligible']) {
            return response()->json([
                'message' => 'Loan application not eligible',
                'errors' => $eligibility['errors'],
            ], 422);
        }

        // Validate guarantors
        $totalGuaranteedAmount = 0;
        foreach ($validated['guarantors'] as $guarantor) {
            $totalGuaranteedAmount += $guarantor['guaranteed_amount'];
        }

        if ($totalGuaranteedAmount < $validated['amount']) {
            return response()->json([
                'message' => 'Total guaranteed amount must be at least equal to loan amount',
                'total_guaranteed' => $totalGuaranteedAmount,
                'loan_amount' => $validated['amount'],
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get system configuration
            $interestRate = (float) config('sacco.interest_rate', 15);

            // Calculate monthly repayment
            $monthlyRepayment = $this->loanService->calculateMonthlyRepayment(
                (float) $validated['amount'],
                $interestRate,
                (int) $validated['term_months']
            );

            // Create loan
            $loan = Loan::create([
                'member_id' => $validated['member_id'],
                'loan_number' => Loan::generateLoanNumber(),
                'amount' => $validated['amount'],
                'interest_rate' => $interestRate,
                'term_months' => $validated['term_months'],
                'purpose' => $validated['purpose'],
                'status' => Loan::STATUS_PENDING,
                'application_date' => now()->toDateString(),
                'monthly_repayment' => $monthlyRepayment,
                'outstanding_balance' => $validated['amount'],
            ]);

            // Add guarantors
            foreach ($validated['guarantors'] as $guarantor) {
                LoanGuarantor::create([
                    'loan_id' => $loan->id,
                    'guarantor_member_id' => $guarantor['member_id'],
                    'guaranteed_amount' => $guarantor['guaranteed_amount'],
                    'status' => LoanGuarantor::STATUS_PENDING,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Loan application submitted successfully',
                'loan' => $this->formatLoanResponse($loan),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to submit loan application',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loan details
     */
    public function show(string $id)
    {
        $loan = Loan::with(['member', 'guarantors.guarantor', 'repayments', 'approvedBy', 'disbursedBy'])
            ->findOrFail($id);

        return response()->json([
            'loan' => $this->formatLoanResponse($loan),
        ]);
    }

    /**
     * List loans with filters
     */
    public function index(Request $request)
    {
        $query = Loan::with(['member', 'guarantors', 'repayments']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by member
        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('application_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('application_date', '<=', $request->to_date);
        }

        // Search by loan number or member name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->get('per_page', 15);
        $loans = $query->orderBy('application_date', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $loans->map(fn($loan) => $this->formatLoanResponse($loan)),
            'meta' => [
                'current_page' => $loans->currentPage(),
                'total' => $loans->total(),
                'per_page' => $loans->perPage(),
                'last_page' => $loans->lastPage(),
            ],
        ]);
    }

    /**
     * Update loan application
     */
    public function update(Request $request, string $id)
    {
        $loan = Loan::findOrFail($id);

        // Only allow updates for pending loans
        if ($loan->status !== Loan::STATUS_PENDING) {
            return response()->json([
                'message' => 'Can only update pending loan applications',
            ], 422);
        }

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:1000',
            'term_months' => 'sometimes|integer|min:1|max:36',
            'purpose' => 'sometimes|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $loan->update($validated);

            // Recalculate monthly repayment if amount or term changed
            if (isset($validated['amount']) || isset($validated['term_months'])) {
                $amount = $validated['amount'] ?? $loan->amount;
                $termMonths = $validated['term_months'] ?? $loan->term_months;
                
                $monthlyRepayment = $this->loanService->calculateMonthlyRepayment(
                    (float) $amount,
                    (float) $loan->interest_rate,
                    (int) $termMonths
                );

                $loan->update([
                    'monthly_repayment' => $monthlyRepayment,
                    'outstanding_balance' => $amount,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Loan application updated successfully',
                'loan' => $this->formatLoanResponse($loan),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update loan application',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve loan (Loan Officer)
     */
    public function approve(Request $request, string $id)
    {
        $loan = Loan::findOrFail($id);

        // Only allow approving loans with all guarantors approved
        if ($loan->status !== Loan::STATUS_GUARANTORS_APPROVED) {
            return response()->json([
                'message' => 'Loan can only be approved when all guarantors have approved',
                'current_status' => $loan->status,
            ], 422);
        }

        // Validate all guarantors have approved
        if (!$loan->allGuarantorsApproved()) {
            return response()->json([
                'message' => 'Not all guarantors have approved this loan',
            ], 422);
        }

        $validated = $request->validate([
            'approval_comment' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $loan->update([
                'status' => Loan::STATUS_APPROVED_PENDING_DISBURSEMENT,
                'approval_date' => now()->toDateString(),
                'approved_by' => Auth::id(),
                'approval_comment' => $validated['approval_comment'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Loan approved successfully',
                'loan' => $this->formatLoanResponse($loan),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to approve loan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject loan (Loan Officer)
     */
    public function reject(Request $request, string $id)
    {
        $loan = Loan::findOrFail($id);

        // Only allow rejecting loans with guarantors approved status
        if ($loan->status !== Loan::STATUS_GUARANTORS_APPROVED) {
            return response()->json([
                'message' => 'Loan can only be rejected when in guarantors_approved status',
                'current_status' => $loan->status,
            ], 422);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $loan->update([
                'status' => Loan::STATUS_REJECTED,
                'rejection_reason' => $validated['rejection_reason'],
                'approved_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Loan rejected successfully',
                'loan' => $this->formatLoanResponse($loan),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to reject loan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disburse loan (Accountant)
     */
    public function disburse(Request $request, string $id)
    {
        $loan = Loan::findOrFail($id);

        // Only allow disbursing approved loans
        if ($loan->status !== Loan::STATUS_APPROVED_PENDING_DISBURSEMENT) {
            return response()->json([
                'message' => 'Loan can only be disbursed when in approved_pending_disbursement status',
                'current_status' => $loan->status,
            ], 422);
        }

        $validated = $request->validate([
            'disbursement_method' => 'required|in:bank_transfer,mobile_money,cash,cheque',
            'first_repayment_date' => 'required|date|after:today',
        ]);

        try {
            DB::beginTransaction();

            // Update loan status and disbursement details
            $loan->update([
                'status' => Loan::STATUS_ACTIVE,
                'disbursement_date' => now()->toDateString(),
                'disbursement_method' => $validated['disbursement_method'],
                'first_repayment_date' => $validated['first_repayment_date'],
                'disbursed_by' => Auth::id(),
            ]);

            // Credit member's savings account
            $savingsAccount = $loan->member->savingsAccount;
            if ($savingsAccount) {
                $savingsAccount->increment('balance', $loan->amount);

                // Record disbursement transaction
                $savingsAccount->transactions()->create([
                    'type' => SavingsTransaction::TYPE_DIRECT_DEPOSIT,
                    'amount' => $loan->amount,
                    'source' => SavingsTransaction::SOURCE_BANK_TRANSFER,
                    'reference' => 'LOAN_DISBURSE_' . $loan->loan_number,
                    'transaction_date' => now()->toDateString(),
                    'description' => "Loan disbursement for {$loan->loan_number}",
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Loan disbursed successfully',
                'loan' => $this->formatLoanResponse($loan),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to disburse loan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loan eligibility for a member
     */
    public function eligibility(string $memberId)
    {
        $member = Member::with('savingsAccount')->findOrFail($memberId);

        $eligibility = $this->loanService->validateLoanEligibility($member, 0);
        $maxLoanAmount = $this->loanService->calculateMaxLoanAmount($member);

        // Calculate available loan limit (considering existing loans)
        $activeLoans = $member->loans()
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_APPROVED_PENDING_DISBURSEMENT])
            ->sum('outstanding_balance');

        $availableLoanLimit = max(0, $maxLoanAmount - $activeLoans);

        return response()->json([
            'eligible' => $eligibility['eligible'],
            'max_loan_amount' => $maxLoanAmount,
            'available_loan_limit' => $availableLoanLimit,
            'savings_balance' => $member->savingsAccount ? $member->savingsAccount->balance : 0,
            'active_loans_balance' => $activeLoans,
            'can_apply' => $eligibility['eligible'] && $availableLoanLimit > 0,
            'errors' => $eligibility['errors'],
        ]);
    }

    /**
     * Get potential guarantors for a member
     */
    public function potentialGuarantors(string $memberId)
    {
        $member = Member::findOrFail($memberId);

        // Get active members excluding the applicant
        $potentialGuarantors = Member::where('status', Member::STATUS_ACTIVE)
            ->where('id', '!=', $memberId)
            ->whereHas('savingsAccount', function ($query) {
                $query->where('balance', '>', 0);
            })
            ->with('savingsAccount')
            ->get()
            ->map(function ($guarantor) {
                // Calculate guarantor's capacity
                $existingGuarantees = $guarantor->guarantorRecords()
                    ->where('status', LoanGuarantor::STATUS_ACCEPTED)
                    ->whereHas('loan', function ($q) {
                        $q->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_APPROVED_PENDING_DISBURSEMENT]);
                    })
                    ->sum('guaranteed_amount');

                $savingsBalance = $guarantor->savingsAccount ? $guarantor->savingsAccount->balance : 0;
                $maxGuaranteeCapacity = $savingsBalance * 2; // Can guarantee up to 2x their savings
                $availableCapacity = max(0, $maxGuaranteeCapacity - $existingGuarantees);

                return [
                    'id' => $guarantor->id,
                    'member_number' => $guarantor->member_number,
                    'full_name' => $guarantor->full_name,
                    'savings_balance' => $savingsBalance,
                    'existing_guarantees' => $existingGuarantees,
                    'available_capacity' => $availableCapacity,
                    'can_guarantee' => $availableCapacity > 0,
                ];
            })
            ->filter(fn($g) => $g['can_guarantee'])
            ->values();

        return response()->json([
            'data' => $potentialGuarantors,
        ]);
    }

    /**
     * Get repayment schedule for a loan
     */
    public function repaymentSchedule(string $id)
    {
        $loan = Loan::findOrFail($id);

        $schedule = $this->loanService->generateRepaymentSchedule($loan);

        return response()->json([
            'loan_id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'data' => $schedule,
        ]);
    }

    /**
     * Calculate loan estimates (calculator)
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'term_months' => 'required|integer|min:1|max:60',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $amount = (float) $validated['amount'];
        $termMonths = (int) $validated['term_months'];
        $interestRate = isset($validated['interest_rate']) 
            ? (float) $validated['interest_rate'] 
            : (float) config('sacco.interest_rate', 15);

        // Calculate monthly repayment
        $monthlyRepayment = $this->loanService->calculateMonthlyRepayment(
            $amount,
            $interestRate,
            $termMonths
        );

        // Calculate total repayment and interest
        $totalRepayment = $monthlyRepayment * $termMonths;
        $totalInterest = $totalRepayment - $amount;

        return response()->json([
            'loan_amount' => $amount,
            'interest_rate' => $interestRate,
            'term_months' => $termMonths,
            'monthly_repayment' => round($monthlyRepayment, 2),
            'total_repayment' => round($totalRepayment, 2),
            'total_interest' => round($totalInterest, 2),
        ]);
    }

    /**
     * Bulk approve loans
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'loan_ids' => 'required|array',
            'loan_ids.*' => 'exists:loans,id',
            'approval_comment' => 'nullable|string|max:500',
        ]);

        $results = [
            'approved' => [],
            'failed' => [],
        ];

        foreach ($validated['loan_ids'] as $loanId) {
            try {
                $loan = Loan::findOrFail($loanId);

                if ($loan->status === Loan::STATUS_GUARANTORS_APPROVED && $loan->allGuarantorsApproved()) {
                    $loan->update([
                        'status' => Loan::STATUS_APPROVED_PENDING_DISBURSEMENT,
                        'approval_date' => now()->toDateString(),
                        'approved_by' => Auth::id(),
                        'approval_comment' => $validated['approval_comment'] ?? null,
                    ]);

                    $results['approved'][] = $loan->loan_number;
                } else {
                    $results['failed'][] = [
                        'loan_number' => $loan->loan_number,
                        'reason' => 'Not all guarantors approved or invalid status',
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'loan_id' => $loanId,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => 'Bulk approval completed',
            'results' => $results,
        ]);
    }

    /**
     * Bulk reject loans
     */
    public function bulkReject(Request $request)
    {
        $validated = $request->validate([
            'loan_ids' => 'required|array',
            'loan_ids.*' => 'exists:loans,id',
            'rejection_reason' => 'required|string|max:500',
        ]);

        $results = [
            'rejected' => [],
            'failed' => [],
        ];

        foreach ($validated['loan_ids'] as $loanId) {
            try {
                $loan = Loan::findOrFail($loanId);

                if ($loan->status === Loan::STATUS_GUARANTORS_APPROVED) {
                    $loan->update([
                        'status' => Loan::STATUS_REJECTED,
                        'rejection_reason' => $validated['rejection_reason'],
                        'approved_by' => Auth::id(),
                    ]);

                    $results['rejected'][] = $loan->loan_number;
                } else {
                    $results['failed'][] = [
                        'loan_number' => $loan->loan_number,
                        'reason' => 'Invalid status for rejection',
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'loan_id' => $loanId,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => 'Bulk rejection completed',
            'results' => $results,
        ]);
    }

    /**
     * Calculate early settlement amount
     */
    public function earlySettlement(string $id)
    {
        $loan = Loan::findOrFail($id);

        $settlement = $this->loanService->calculateEarlySettlement($loan);

        return response()->json([
            'loan_id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'settlement' => $settlement,
        ]);
    }

    /**
     * Process early settlement
     */
    public function processEarlySettlement(Request $request, string $id)
    {
        $loan = Loan::findOrFail($id);

        if ($loan->status !== Loan::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'Only active loans can be settled early',
            ], 422);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money',
            'reference' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $settlement = $this->loanService->calculateEarlySettlement($loan);
            
            if (!$settlement['eligible']) {
                return response()->json([
                    'message' => $settlement['reason'],
                ], 422);
            }

            // Create final repayment record
            LoanRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $settlement['settlement_amount'],
                'principal_amount' => $settlement['outstanding_principal'],
                'interest_amount' => $settlement['discounted_interest'],
                'penalty_amount' => 0,
                'payment_date' => now()->toDateString(),
                'source' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? 'EARLY_SETTLEMENT_' . $loan->loan_number,
                'recorded_by' => Auth::id(),
                'notes' => 'Early settlement with ' . ($settlement['discount_rate']) . '% discount',
            ]);

            // Close the loan
            $loan->update([
                'status' => Loan::STATUS_CLOSED,
                'outstanding_balance' => 0,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Loan settled successfully',
                'settlement' => $settlement,
                'loan' => $this->formatLoanResponse($loan->fresh()),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process early settlement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate penalty for a loan
     */
    public function penalty(string $id)
    {
        $loan = Loan::findOrFail($id);

        $penalty = $this->loanService->calculatePenalty($loan);
        $isOverdue = $this->loanService->isLoanOverdue($loan);

        return response()->json([
            'loan_id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'penalty_amount' => $penalty,
            'is_overdue' => $isOverdue,
        ]);
    }

    /**
     * Mark overdue loans (admin function)
     */
    public function markOverdue()
    {
        $result = $this->loanService->markOverdueLoans();

        return response()->json([
            'message' => 'Overdue loans marked successfully',
            'result' => $result,
        ]);
    }

    /**
     * Format loan response
     */
    private function formatLoanResponse(Loan $loan): array
    {
        return [
            'id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'member' => [
                'id' => $loan->member->id,
                'member_number' => $loan->member->member_number,
                'full_name' => $loan->member->full_name,
            ],
            'amount' => (float) $loan->amount,
            'interest_rate' => (float) $loan->interest_rate,
            'term_months' => $loan->term_months,
            'purpose' => $loan->purpose,
            'status' => $loan->status,
            'monthly_repayment' => (float) $loan->monthly_repayment,
            'outstanding_balance' => (float) $loan->outstanding_balance,
            'application_date' => $loan->application_date->format('Y-m-d'),
            'approval_date' => $loan->approval_date?->format('Y-m-d'),
            'disbursement_date' => $loan->disbursement_date?->format('Y-m-d'),
            'first_repayment_date' => $loan->first_repayment_date?->format('Y-m-d'),
            'guarantors' => $loan->guarantors->map(fn($g) => [
                'id' => $g->id,
                'member' => [
                    'id' => $g->guarantor->id,
                    'member_number' => $g->guarantor->member_number,
                    'full_name' => $g->guarantor->full_name,
                ],
                'guaranteed_amount' => (float) $g->guaranteed_amount,
                'status' => $g->status,
                'approval_date' => $g->approval_date?->format('Y-m-d'),
            ]),
            'total_repaid' => (float) $loan->getTotalRepaid(),
            'remaining_balance' => (float) $loan->getRemainingBalance(),
            'approved_by' => $loan->approvedBy ? [
                'id' => $loan->approvedBy->id,
                'name' => $loan->approvedBy->name,
            ] : null,
            'disbursed_by' => $loan->disbursedBy ? [
                'id' => $loan->disbursedBy->id,
                'name' => $loan->disbursedBy->name,
            ] : null,
            'approval_comment' => $loan->approval_comment,
            'rejection_reason' => $loan->rejection_reason,
            'disbursement_method' => $loan->disbursement_method,
            'created_at' => $loan->created_at->toIso8601String(),
            'updated_at' => $loan->updated_at->toIso8601String(),
        ];
    }
}
