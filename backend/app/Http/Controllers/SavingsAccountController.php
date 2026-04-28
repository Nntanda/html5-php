<?php

namespace App\Http\Controllers;

use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use Illuminate\Http\Request;

class SavingsAccountController extends Controller
{
    /**
     * Get savings statistics (admin only)
     */
    public function stats(Request $request)
    {
        $totalAccounts = SavingsAccount::count();
        $activeAccounts = SavingsAccount::where('status', SavingsAccount::STATUS_ACTIVE)->count();
        $pendingAccounts = SavingsAccount::where('status', SavingsAccount::STATUS_PENDING)->count();
        $totalBalance = SavingsAccount::where('status', SavingsAccount::STATUS_ACTIVE)->sum('balance');
        $averageBalance = $activeAccounts > 0 ? $totalBalance / $activeAccounts : 0;

        // Today's transactions
        $today = now()->toDateString();
        $depositsToday = SavingsTransaction::where('type', SavingsTransaction::TYPE_DIRECT_DEPOSIT)
            ->orWhere('type', SavingsTransaction::TYPE_SALARY_SAVINGS)
            ->whereDate('transaction_date', $today)
            ->where('status', SavingsTransaction::STATUS_APPROVED)
            ->sum('amount');

        $withdrawalsToday = SavingsTransaction::where('type', SavingsTransaction::TYPE_WITHDRAWAL)
            ->whereDate('transaction_date', $today)
            ->where('status', SavingsTransaction::STATUS_APPROVED)
            ->sum('amount');

        return response()->json([
            'total_accounts' => $totalAccounts,
            'active_accounts' => $activeAccounts,
            'pending_accounts' => $pendingAccounts,
            'total_balance' => $totalBalance,
            'average_balance' => $averageBalance,
            'total_deposits_today' => $depositsToday,
            'total_withdrawals_today' => $withdrawalsToday,
        ]);
    }

    /**
     * List all savings accounts (admin only)
     */
    public function index(Request $request)
    {
        $query = SavingsAccount::with(['member:id,member_number,first_name,last_name,category']);

        // Search by member name or number
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('member_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Filter by account status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by member status
        if ($request->has('member_status')) {
            $query->whereHas('member', function ($q) use ($request) {
                $q->where('status', $request->member_status);
            });
        }

        $perPage = $request->get('per_page', 20);
        $accounts = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($accounts);
    }

    /**
     * Create savings account (member-initiated)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $member = \App\Models\Member::where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found',
            ], 404);
        }

        // Check if member already has a savings account
        $existingAccount = SavingsAccount::where('member_id', $member->id)->first();
        if ($existingAccount) {
            return response()->json([
                'message' => 'You already have a savings account',
                'account' => $existingAccount,
            ], 400);
        }

        // Create savings account with pending status
        $account = SavingsAccount::create([
            'member_id' => $member->id,
            'account_number' => SavingsAccount::generateAccountNumber(),
            'balance' => 0,
            'status' => SavingsAccount::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'Savings account application submitted successfully. Awaiting approval.',
            'account' => $account->load('member'),
        ], 201);
    }

    /**
     * Create savings account for a member (admin-initiated)
     */
    public function adminCreate(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
        ]);

        $member = \App\Models\Member::findOrFail($validated['member_id']);

        // Check if member already has a savings account
        $existingAccount = SavingsAccount::where('member_id', $member->id)->first();
        if ($existingAccount) {
            return response()->json([
                'message' => 'Member already has a savings account',
                'account' => $existingAccount,
            ], 400);
        }

        // Check if member is active
        if ($member->status !== 'active') {
            return response()->json([
                'message' => 'Cannot create savings account for inactive member',
            ], 400);
        }

        // Create savings account with active status (admin-created accounts are auto-approved)
        $account = SavingsAccount::create([
            'member_id' => $member->id,
            'account_number' => SavingsAccount::generateAccountNumber(),
            'balance' => 0,
            'status' => SavingsAccount::STATUS_ACTIVE,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Savings account created successfully',
            'account' => $account->load('member'),
        ], 201);
    }

    /**
     * Approve savings account (admin only)
     */
    public function approve(Request $request, string $id)
    {
        $account = SavingsAccount::with(['member:id,member_number,first_name,last_name,category'])->findOrFail($id);

        if ($account->status !== SavingsAccount::STATUS_PENDING) {
            return response()->json([
                'message' => 'Account is not pending approval',
            ], 400);
        }

        $account->update([
            'status' => SavingsAccount::STATUS_ACTIVE,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Savings account approved successfully',
            'account' => $account->fresh(['member', 'approvedBy']),
        ]);
    }

    /**
     * Reject savings account (admin only)
     */
    public function reject(Request $request, string $id)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $account = SavingsAccount::with(['member:id,member_number,first_name,last_name,category'])->findOrFail($id);

        if ($account->status !== SavingsAccount::STATUS_PENDING) {
            return response()->json([
                'message' => 'Account is not pending approval',
            ], 400);
        }

        $account->update([
            'status' => SavingsAccount::STATUS_CLOSED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json([
            'message' => 'Savings account application rejected',
            'account' => $account->fresh(['member', 'approvedBy']),
        ]);
    }

    /**
     * Display the specified savings account.
     */
    public function show(string $id)
    {
        $account = SavingsAccount::with(['member:id,member_number,first_name,last_name,category'])->findOrFail($id);

        return response()->json([
            'account' => [
                'id' => $account->id,
                'account_number' => $account->account_number,
                'balance' => $account->balance,
                'member' => [
                    'id' => $account->member->id,
                    'member_number' => $account->member->member_number,
                    'full_name' => $account->member->full_name,
                    'category' => $account->member->category,
                ],
                'created_at' => $account->created_at,
            ],
        ]);
    }

    /**
     * Get account transactions.
     */
    public function transactions(Request $request, string $id)
    {
        $account = SavingsAccount::findOrFail($id);

        $query = $account->transactions()->orderBy('transaction_date', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        // Exclude reversed transactions by default
        if (!$request->has('include_reversed')) {
            $query->where('is_reversed', false);
        }

        $perPage = $request->get('per_page', 20);
        $transactions = $query->paginate($perPage);

        return response()->json($transactions);
    }

    /**
     * Get account balance.
     */
    public function balance(string $id)
    {
        $account = SavingsAccount::findOrFail($id);

        // Calculate available balance (excluding guarantor collateral)
        $member = $account->member;
        $guarantorExposure = $member->guarantorRecords()
            ->where('status', 'accepted')
            ->whereHas('loan', function ($query) {
                $query->where('status', 'active');
            })
            ->sum('guaranteed_amount');

        $availableBalance = max(0, $account->balance - $guarantorExposure);

        return response()->json([
            'account_number' => $account->account_number,
            'total_balance' => $account->balance,
            'guarantor_collateral' => $guarantorExposure,
            'available_balance' => $availableBalance,
        ]);
    }
}
