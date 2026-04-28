<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalRequest;
use App\Models\Member;
use App\Models\SavingsTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WithdrawalRequestController extends Controller
{
    /**
     * List withdrawal requests
     * Admin: all requests
     * Member: own requests only
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = WithdrawalRequest::with(['member', 'account', 'processedBy']);

        // If user is a member, only show their requests
        if ($user->role === 'member') {
            $member = Member::where('user_id', $user->id)->first();
            if (!$member) {
                return response()->json([
                    'message' => 'Member profile not found',
                ], 404);
            }
            $query->where('member_id', $member->id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($requests);
    }

    /**
     * Create withdrawal request (member-initiated)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => ['required', Rule::in([
                SavingsTransaction::SOURCE_CASH,
                SavingsTransaction::SOURCE_BANK_TRANSFER,
                SavingsTransaction::SOURCE_MOBILE_MONEY,
            ])],
            'reason' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $member = Member::where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found',
            ], 404);
        }

        $account = $member->savingsAccount;

        if (!$account) {
            return response()->json([
                'message' => 'No savings account found',
            ], 400);
        }

        // Calculate available balance (excluding guarantor collateral)
        $guarantorExposure = $member->guarantorRecords()
            ->where('status', 'accepted')
            ->whereHas('loan', function ($query) {
                $query->where('status', 'active');
            })
            ->sum('guaranteed_amount');

        $availableBalance = $account->balance - $guarantorExposure;

        if ($validated['amount'] > $availableBalance) {
            return response()->json([
                'message' => 'Insufficient available balance',
                'available_balance' => $availableBalance,
                'requested_amount' => $validated['amount'],
            ], 400);
        }

        // Create withdrawal request
        $withdrawalRequest = WithdrawalRequest::create([
            'member_id' => $member->id,
            'account_id' => $account->id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'reason' => $validated['reason'],
            'status' => WithdrawalRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'Withdrawal request submitted successfully',
            'request' => $withdrawalRequest->load(['member', 'account']),
        ], 201);
    }

    /**
     * Approve withdrawal request (admin only)
     */
    public function approve(Request $request, string $id)
    {
        DB::beginTransaction();
        try {
            $withdrawalRequest = WithdrawalRequest::with(['member', 'account'])->findOrFail($id);

            if ($withdrawalRequest->status !== WithdrawalRequest::STATUS_PENDING) {
                return response()->json([
                    'message' => 'Request has already been processed',
                ], 400);
            }

            $account = $withdrawalRequest->account;
            $member = $withdrawalRequest->member;

            // Re-check available balance
            $guarantorExposure = $member->guarantorRecords()
                ->where('status', 'accepted')
                ->whereHas('loan', function ($query) {
                    $query->where('status', 'active');
                })
                ->sum('guaranteed_amount');

            $availableBalance = $account->balance - $guarantorExposure;

            if ($withdrawalRequest->amount > $availableBalance) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Insufficient available balance',
                    'available_balance' => $availableBalance,
                ], 400);
            }

            // Create withdrawal transaction
            $transaction = SavingsTransaction::create([
                'account_id' => $account->id,
                'type' => SavingsTransaction::TYPE_WITHDRAWAL,
                'amount' => $withdrawalRequest->amount,
                'source' => $withdrawalRequest->payment_method,
                'reference' => SavingsTransaction::generateReference(),
                'transaction_date' => now()->toDateString(),
                'description' => 'Withdrawal - ' . $withdrawalRequest->reason,
                'status' => SavingsTransaction::STATUS_APPROVED,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            // Update account balance
            $account->decrement('balance', $withdrawalRequest->amount);

            // Update withdrawal request
            $withdrawalRequest->update([
                'status' => WithdrawalRequest::STATUS_APPROVED,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Withdrawal request approved successfully',
                'request' => $withdrawalRequest->fresh(['member', 'account', 'processedBy']),
                'transaction' => $transaction,
                'new_balance' => $account->fresh()->balance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to approve withdrawal request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject withdrawal request (admin only)
     */
    public function reject(Request $request, string $id)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $withdrawalRequest = WithdrawalRequest::findOrFail($id);

            if ($withdrawalRequest->status !== WithdrawalRequest::STATUS_PENDING) {
                return response()->json([
                    'message' => 'Request has already been processed',
                ], 400);
            }

            $withdrawalRequest->update([
                'status' => WithdrawalRequest::STATUS_REJECTED,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            return response()->json([
                'message' => 'Withdrawal request rejected',
                'request' => $withdrawalRequest->fresh(['member', 'account', 'processedBy']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reject withdrawal request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
