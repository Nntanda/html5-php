<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanGuarantor;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GuarantorController extends Controller
{
    /**
     * Add guarantor to loan
     */
    public function store(Request $request, string $loanId)
    {
        $loan = Loan::findOrFail($loanId);

        // Only allow adding guarantors to pending loans
        if ($loan->status !== Loan::STATUS_PENDING) {
            return response()->json([
                'message' => 'Can only add guarantors to pending loan applications',
            ], 422);
        }

        $validated = $request->validate([
            'guarantor_member_id' => 'required|exists:members,id',
            'guaranteed_amount' => 'required|numeric|min:1000',
        ]);

        // Check if guarantor is already added
        $existingGuarantor = $loan->guarantors()
            ->where('guarantor_member_id', $validated['guarantor_member_id'])
            ->first();

        if ($existingGuarantor) {
            return response()->json([
                'message' => 'This member is already a guarantor for this loan',
            ], 422);
        }

        // Check if guarantor is the loan applicant
        if ($validated['guarantor_member_id'] === $loan->member_id) {
            return response()->json([
                'message' => 'Loan applicant cannot be a guarantor',
            ], 422);
        }

        try {
            $guarantor = LoanGuarantor::create([
                'loan_id' => $loanId,
                'guarantor_member_id' => $validated['guarantor_member_id'],
                'guaranteed_amount' => $validated['guaranteed_amount'],
                'status' => LoanGuarantor::STATUS_PENDING,
            ]);

            return response()->json([
                'message' => 'Guarantor added successfully',
                'guarantor' => $this->formatGuarantorResponse($guarantor),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add guarantor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Guarantor approval/rejection
     */
    public function update(Request $request, string $loanId, string $guarantorId)
    {
        $loan = Loan::findOrFail($loanId);
        $guarantor = LoanGuarantor::where('id', $guarantorId)
            ->where('loan_id', $loanId)
            ->firstOrFail();

        // Only allow updates for pending guarantor requests
        if ($guarantor->status !== LoanGuarantor::STATUS_PENDING) {
            return response()->json([
                'message' => 'Can only update pending guarantor requests',
            ], 422);
        }

        $validated = $request->validate([
            'status' => 'required|in:accepted,rejected',
            'rejection_reason' => 'required_if:status,rejected|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'status' => $validated['status'] === 'accepted' ? 
                    LoanGuarantor::STATUS_ACCEPTED : 
                    LoanGuarantor::STATUS_REJECTED,
            ];

            if ($validated['status'] === 'accepted') {
                $updateData['approval_date'] = now()->toDateString();
            } else {
                $updateData['rejection_reason'] = $validated['rejection_reason'] ?? null;
            }

            $guarantor->update($updateData);

            // Check if all guarantors have approved
            if ($loan->allGuarantorsApproved()) {
                $loan->update(['status' => Loan::STATUS_GUARANTORS_APPROVED]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Guarantor request updated successfully',
                'guarantor' => $this->formatGuarantorResponse($guarantor),
                'loan_status' => $loan->status,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update guarantor request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List loan guarantors
     */
    public function index(string $loanId)
    {
        $loan = Loan::findOrFail($loanId);
        $guarantors = $loan->guarantors()->with('guarantor')->get();

        return response()->json([
            'loan_id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'guarantors' => $guarantors->map(fn($g) => $this->formatGuarantorResponse($g)),
        ]);
    }

    /**
     * Get pending guarantor requests for member
     */
    public function pendingRequests(string $memberId)
    {
        $member = Member::findOrFail($memberId);

        $pendingRequests = LoanGuarantor::where('guarantor_member_id', $memberId)
            ->where('status', LoanGuarantor::STATUS_PENDING)
            ->with(['loan.member', 'guarantor'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'member_id' => $member->id,
            'member_number' => $member->member_number,
            'full_name' => $member->full_name,
            'pending_requests' => $pendingRequests->map(fn($request) => [
                'id' => $request->id,
                'loan' => [
                    'id' => $request->loan->id,
                    'loan_number' => $request->loan->loan_number,
                    'amount' => (float) $request->loan->amount,
                    'purpose' => $request->loan->purpose,
                    'term_months' => $request->loan->term_months,
                    'interest_rate' => (float) $request->loan->interest_rate,
                    'applicant' => [
                        'id' => $request->loan->member->id,
                        'member_number' => $request->loan->member->member_number,
                        'full_name' => $request->loan->member->full_name,
                    ],
                ],
                'guaranteed_amount' => (float) $request->guaranteed_amount,
                'status' => $request->status,
                'created_at' => $request->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Format guarantor response
     */
    private function formatGuarantorResponse(LoanGuarantor $guarantor): array
    {
        return [
            'id' => $guarantor->id,
            'loan_id' => $guarantor->loan_id,
            'member' => [
                'id' => $guarantor->guarantor->id,
                'member_number' => $guarantor->guarantor->member_number,
                'full_name' => $guarantor->guarantor->full_name,
            ],
            'guaranteed_amount' => (float) $guarantor->guaranteed_amount,
            'status' => $guarantor->status,
            'approval_date' => $guarantor->approval_date?->format('Y-m-d'),
            'rejection_reason' => $guarantor->rejection_reason,
            'created_at' => $guarantor->created_at->toIso8601String(),
            'updated_at' => $guarantor->updated_at->toIso8601String(),
        ];
    }
}
