<?php

namespace App\Http\Controllers;

use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\Member;
use App\Models\UploadLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PDF;

class SavingsTransactionController extends Controller
{
    /**
     * Record a direct deposit.
     */
    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'nullable|exists:members,id', // Optional for member-initiated
            'amount' => 'required|numeric|min:0.01',
            'deposit_date' => 'nullable|date',
            'payment_method' => ['required', Rule::in([
                SavingsTransaction::SOURCE_CASH,
                SavingsTransaction::SOURCE_BANK_TRANSFER,
                SavingsTransaction::SOURCE_MOBILE_MONEY,
                SavingsTransaction::SOURCE_CHECK,
            ])],
            'description' => 'nullable|string',
            'reference' => 'nullable|string',
            'evidence_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // 2MB max
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // 2MB max
        ]);

        DB::beginTransaction();
        try {
            $user = $request->user();
            
            // Determine if this is admin or member-initiated
            $isAdminInitiated = $request->has('member_id');
            
            if ($isAdminInitiated) {
                // Admin recording deposit for a member
                $member = Member::findOrFail($validated['member_id']);
            } else {
                // Member recording their own deposit
                $member = Member::where('user_id', $user->id)->first();
                if (!$member) {
                    return response()->json([
                        'message' => 'Member profile not found',
                    ], 404);
                }
            }

            $account = $member->savingsAccount;

            if (!$account) {
                return response()->json([
                    'message' => 'Member does not have a savings account',
                ], 400);
            }

            // Check if account is active
            if ($account->status !== SavingsAccount::STATUS_ACTIVE) {
                return response()->json([
                    'message' => 'Savings account is not active. Status: ' . $account->status,
                ], 400);
            }

            // Check member category to determine if approval is needed
            $isStaffCategory = in_array($member->category, [
                Member::CATEGORY_STAFF,
                Member::CATEGORY_ACT_PROGRAM,
                Member::CATEGORY_NURSING_SCHOOL,
                Member::CATEGORY_HC_STAFF,
            ]);

            $isNonHospitalStaff = $member->category === Member::CATEGORY_NON_HOSPITAL_STAFF;

            // For non-hospital staff, evidence is required
            if ($isNonHospitalStaff && !$request->hasFile('evidence_file')) {
                return response()->json([
                    'message' => 'Evidence file is required for non-hospital staff deposits',
                ], 422);
            }

            // Handle evidence file upload
            $evidenceFilePath = null;
            if ($request->hasFile('evidence_file')) {
                $file = $request->file('evidence_file');
                $fileName = time() . '_evidence_' . $file->getClientOriginalName();
                $evidenceFilePath = $file->storeAs('deposit_evidence', $fileName, 'public');
            }

            // Handle receipt file upload
            $receiptFilePath = null;
            if ($request->hasFile('receipt_file')) {
                $file = $request->file('receipt_file');
                $fileName = time() . '_receipt_' . $file->getClientOriginalName();
                $receiptFilePath = $file->storeAs('deposit_receipts', $fileName, 'public');
            }

            // Determine transaction status
            // Admin-initiated for staff: auto-approved
            // Member-initiated: requires approval (even for staff)
            // Non-hospital staff: always requires approval
            $requiresApproval = !$isAdminInitiated || $isNonHospitalStaff;
            $status = $requiresApproval ? SavingsTransaction::STATUS_PENDING : SavingsTransaction::STATUS_APPROVED;
            $approvedBy = !$requiresApproval ? $user->id : null;
            $approvedAt = !$requiresApproval ? now() : null;

            // Create transaction
            $transaction = SavingsTransaction::create([
                'account_id' => $account->id,
                'type' => SavingsTransaction::TYPE_DIRECT_DEPOSIT,
                'amount' => $validated['amount'],
                'source' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? SavingsTransaction::generateReference(),
                'transaction_date' => $validated['deposit_date'] ?? now()->toDateString(),
                'description' => $validated['description'] ?? 'Direct deposit',
                'status' => $status,
                'evidence_file' => $evidenceFilePath,
                'receipt_file' => $receiptFilePath,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
            ]);

            // Update account balance only if auto-approved
            if ($status === SavingsTransaction::STATUS_APPROVED) {
                $account->increment('balance', $validated['amount']);
            }

            DB::commit();

            $message = $status === SavingsTransaction::STATUS_APPROVED 
                ? 'Deposit recorded successfully' 
                : 'Deposit submitted for approval';

            return response()->json([
                'message' => $message,
                'transaction' => $transaction,
                'new_balance' => $account->fresh()->balance,
                'requires_approval' => $status === SavingsTransaction::STATUS_PENDING,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to record deposit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record a withdrawal.
     */
    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'amount' => 'required|numeric|min:0.01',
            'withdrawal_date' => 'required|date',
            'payment_method' => ['required', Rule::in([
                SavingsTransaction::SOURCE_CASH,
                SavingsTransaction::SOURCE_BANK_TRANSFER,
                SavingsTransaction::SOURCE_MOBILE_MONEY,
            ])],
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $member = Member::findOrFail($validated['member_id']);
            $account = $member->savingsAccount;

            if (!$account) {
                return response()->json([
                    'message' => 'Member does not have a savings account',
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

            // Create transaction
            $transaction = SavingsTransaction::create([
                'account_id' => $account->id,
                'type' => SavingsTransaction::TYPE_WITHDRAWAL,
                'amount' => $validated['amount'],
                'source' => $validated['payment_method'],
                'reference' => SavingsTransaction::generateReference(),
                'transaction_date' => $validated['withdrawal_date'],
                'description' => $validated['description'] ?? 'Withdrawal',
            ]);

            // Update account balance
            $account->decrement('balance', $validated['amount']);

            DB::commit();

            return response()->json([
                'message' => 'Withdrawal recorded successfully',
                'transaction' => $transaction,
                'new_balance' => $account->fresh()->balance,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to record withdrawal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reverse a transaction (within 24-48 hours).
     */
    public function reverse(Request $request, string $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $transaction = SavingsTransaction::findOrFail($id);

            if ($transaction->is_reversed) {
                return response()->json([
                    'message' => 'Transaction is already reversed',
                ], 400);
            }

            // Check time limit (24 hours for salary savings, 48 hours for direct deposits)
            $timeLimit = $transaction->type === SavingsTransaction::TYPE_SALARY_SAVINGS ? 24 : 48;
            $hoursSinceTransaction = now()->diffInHours($transaction->created_at);

            if ($hoursSinceTransaction > $timeLimit) {
                return response()->json([
                    'message' => "Transaction can only be reversed within {$timeLimit} hours",
                ], 400);
            }

            // Mark transaction as reversed
            $transaction->update([
                'is_reversed' => true,
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
                'description' => $transaction->description . ' [REVERSED: ' . $validated['reason'] . ']',
            ]);

            // Reverse the balance change
            $account = $transaction->account;
            if ($transaction->type === SavingsTransaction::TYPE_WITHDRAWAL) {
                $account->increment('balance', $transaction->amount);
            } else {
                $account->decrement('balance', $transaction->amount);
            }

            DB::commit();

            return response()->json([
                'message' => 'Transaction reversed successfully',
                'transaction' => $transaction,
                'new_balance' => $account->fresh()->balance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to reverse transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload CSV file for salary deductions.
     */
    public function uploadDeductions(Request $request)
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:2048', // 2MB max to match PHP limits
                'bank_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // 2MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'debug_info' => [
                    'has_file' => $request->hasFile('file'),
                    'file_info' => $request->hasFile('file') ? [
                        'name' => $request->file('file')->getClientOriginalName(),
                        'size' => $request->file('file')->getSize(),
                        'mime' => $request->file('file')->getMimeType(),
                    ] : null,
                    'has_bank_receipt' => $request->hasFile('bank_receipt'),
                ]
            ], 422);
        }

        $file = $validated['file'];
        $salaryPeriod = $request->input('salary_period', date('M-Y')); // Default to current month-year
        
        $successCount = 0;
        $failureCount = 0;
        $errors = [];
        $processedRecords = [];
        $totalAmount = 0;

        DB::beginTransaction();
        try {
            // Handle bank receipt upload
            $bankReceiptPath = null;
            if ($request->hasFile('bank_receipt')) {
                $receiptFile = $request->file('bank_receipt');
                $fileName = time() . '_bank_receipt_' . $receiptFile->getClientOriginalName();
                $bankReceiptPath = $receiptFile->storeAs('bank_receipts', $fileName, 'public');
            }

            // Read CSV file
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            $headers = array_shift($csvData); // Remove header row

            // Normalize headers (remove spaces, convert to lowercase)
            $normalizedHeaders = array_map(function($header) {
                return strtolower(trim($header));
            }, $headers);

            // Validate headers - be more flexible with header names
            $requiredFields = ['member_number', 'amount', 'source', 'reference'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!in_array($field, $normalizedHeaders)) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Missing required columns: ' . implode(', ', $missingFields),
                    'errors' => ['file' => 'CSV headers do not match expected format'],
                    'expected_headers' => $requiredFields,
                    'found_headers' => $normalizedHeaders,
                ], 422);
            }

            // Create header mapping
            $headerMap = array_flip($normalizedHeaders);

            // Process each row
            foreach ($csvData as $rowIndex => $row) {
                $lineNumber = $rowIndex + 2; // +2 because we removed header and arrays are 0-indexed

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Validate row has correct number of columns
                if (count($row) !== count($headers)) {
                    $failureCount++;
                    $errors[] = [
                        'row' => $lineNumber,
                        'error' => 'Invalid number of columns. Expected ' . count($headers) . ', got ' . count($row),
                    ];
                    continue;
                }

                // Extract data using header mapping
                $memberNumber = trim($row[$headerMap['member_number']] ?? '');
                $amount = trim($row[$headerMap['amount']] ?? '');
                $source = trim($row[$headerMap['source']] ?? 'salary');
                $reference = trim($row[$headerMap['reference']] ?? '');

                // Validate amount
                if (!is_numeric($amount) || $amount <= 0) {
                    $failureCount++;
                    $errors[] = [
                        'row' => $lineNumber,
                        'error' => 'Invalid amount. Must be a positive number',
                    ];
                    continue;
                }

                // Validate reference is not empty
                if (empty($reference)) {
                    $failureCount++;
                    $errors[] = [
                        'row' => $lineNumber,
                        'error' => 'Reference cannot be empty',
                    ];
                    continue;
                }

                // Find member by member number
                $member = Member::where('member_number', trim($memberNumber))->first();
                if (!$member) {
                    $failureCount++;
                    $errors[] = [
                        'line' => $lineNumber,
                        'member_number' => $memberNumber,
                        'error' => 'Member not found',
                    ];
                    continue;
                }

                // Check if member is active
                if (!$member->isActive()) {
                    $failureCount++;
                    $errors[] = [
                        'line' => $lineNumber,
                        'member_number' => $memberNumber,
                        'error' => 'Member is not active',
                    ];
                    continue;
                }

                // Get or create savings account
                $account = $member->savingsAccount;
                if (!$account) {
                    $failureCount++;
                    $errors[] = [
                        'line' => $lineNumber,
                        'member_number' => $memberNumber,
                        'error' => 'Member does not have a savings account',
                    ];
                    continue;
                }

                // Check if reference already exists
                $existingTransaction = SavingsTransaction::where('reference', trim($reference))->first();
                if ($existingTransaction) {
                    $failureCount++;
                    $errors[] = [
                        'line' => $lineNumber,
                        'member_number' => $memberNumber,
                        'reference' => $reference,
                        'error' => 'Transaction reference already exists',
                    ];
                    continue;
                }

                try {
                    // Create transaction
                    $transaction = SavingsTransaction::create([
                        'account_id' => $account->id,
                        'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
                        'amount' => (float) $amount,
                        'source' => SavingsTransaction::SOURCE_SALARY,
                        'reference' => trim($reference),
                        'transaction_date' => now()->toDateString(),
                        'description' => "Salary deduction - {$salaryPeriod}",
                        'salary_period' => $salaryPeriod,
                        'employer_reference' => trim($reference),
                    ]);

                    // Update account balance
                    $account->increment('balance', (float) $amount);

                    $successCount++;
                    $totalAmount += (float) $amount;
                    $processedRecords[] = [
                        'line' => $lineNumber,
                        'member_number' => $memberNumber,
                        'amount' => $amount,
                        'reference' => $reference,
                        'transaction_id' => $transaction->id,
                        'status' => 'success',
                    ];

                } catch (\Exception $e) {
                    $failureCount++;
                    $errors[] = [
                        'line' => $lineNumber,
                        'member_number' => $memberNumber,
                        'error' => 'Failed to create transaction: ' . $e->getMessage(),
                    ];
                }
            }

            // Generate summary report
            $summary = [
                'total_records' => $successCount + $failureCount,
                'successful' => $successCount,
                'failed' => $failureCount,
                'total_amount_processed' => $totalAmount,
                'salary_period' => $salaryPeriod,
                'processed_at' => now()->toIso8601String(),
            ];

            // Determine upload status
            $uploadStatus = $failureCount === 0 ? UploadLog::STATUS_COMPLETED : 
                           ($successCount === 0 ? UploadLog::STATUS_FAILED : UploadLog::STATUS_PARTIAL);

            // Log the upload
            UploadLog::create([
                'user_id' => auth()->id(),
                'upload_type' => UploadLog::TYPE_SALARY_DEDUCTIONS,
                'file_name' => $file->getClientOriginalName(),
                'total_records' => $successCount + $failureCount,
                'successful_records' => $successCount,
                'failed_records' => $failureCount,
                'total_amount_processed' => $totalAmount,
                'salary_period' => $salaryPeriod,
                'errors' => $errors,
                'summary' => $summary,
                'status' => $uploadStatus,
                'bank_receipt' => $bankReceiptPath,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'CSV upload completed',
                'data' => $summary,
                'processed_records' => $processedRecords,
                'errors' => $errors,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            // Log the failed upload
            UploadLog::create([
                'user_id' => auth()->id(),
                'upload_type' => UploadLog::TYPE_SALARY_DEDUCTIONS,
                'file_name' => $file->getClientOriginalName(),
                'total_records' => 0,
                'successful_records' => 0,
                'failed_records' => 0,
                'total_amount_processed' => 0,
                'salary_period' => $salaryPeriod,
                'status' => UploadLog::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'bank_receipt' => $bankReceiptPath ?? null,
            ]);

            return response()->json([
                'message' => 'Failed to process CSV upload',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
/**
     * Get savings transactions with pagination and filtering
     */
    public function index(Request $request)
    {
        $query = SavingsTransaction::with(['account.member']);

        // Filter by member if specified
        if ($request->has('member_id')) {
            $query->whereHas('account', function ($q) use ($request) {
                $q->where('member_id', $request->member_id);
            });
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('transaction_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('transaction_date', '<=', $request->to_date);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Order by most recent
        $query->orderBy('transaction_date', 'desc')
              ->orderBy('created_at', 'desc');

        // Paginate results
        $limit = $request->get('limit', 10);
        $transactions = $query->paginate($limit);

        return response()->json($transactions);
    }

    /**
     * Get current logged-in member's savings account and transactions
     */
    public function currentMemberSavings(Request $request)
    {
        $user = $request->user();
        $member = Member::where('user_id', $user->id)->with('savingsAccount')->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found',
            ], 404);
        }

        $account = $member->savingsAccount;

        if (!$account) {
            return response()->json([
                'message' => 'No savings account found',
                'account' => null,
                'transactions' => [],
            ]);
        }

        // Get recent transactions
        $transactions = SavingsTransaction::where('account_id', $account->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'account' => $account,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Get comprehensive transaction history for current member
     */
    public function memberTransactions(Request $request)
    {
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
                'data' => [],
            ]);
        }

        $query = SavingsTransaction::where('account_id', $account->id)
            ->with(['account.member']);

        // Apply filters
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('source') && $request->source !== 'all') {
            $query->where('source', $request->source);
        }

        if ($request->has('from_date')) {
            $query->where('transaction_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('transaction_date', '<=', $request->to_date);
        }

        if ($request->has('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->has('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        if ($request->has('reference')) {
            $query->where('reference', 'like', '%' . $request->reference . '%');
        }

        if ($request->has('description')) {
            $query->where('description', 'like', '%' . $request->description . '%');
        }

        // Apply date presets
        if ($request->has('preset')) {
            switch ($request->preset) {
                case 'today':
                    $query->whereDate('transaction_date', today());
                    break;
                case 'week':
                    $query->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('transaction_date', now()->month)
                          ->whereYear('transaction_date', now()->year);
                    break;
                case 'quarter':
                    $query->whereBetween('transaction_date', [now()->startOfQuarter(), now()->endOfQuarter()]);
                    break;
                case 'year':
                    $query->whereYear('transaction_date', now()->year);
                    break;
            }
        }

        // Order by most recent
        $query->orderBy('transaction_date', 'desc')
              ->orderBy('created_at', 'desc');

        // Paginate results
        $limit = $request->get('limit', 20);
        $transactions = $query->paginate($limit);

        return response()->json($transactions);
    }

    /**
     * Get transaction analytics for current member
     */
    public function memberTransactionAnalytics(Request $request)
    {
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
                'analytics' => null,
            ]);
        }

        $period = $request->get('period', 'month'); // month, quarter, year
        $startDate = match($period) {
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $transactions = SavingsTransaction::where('account_id', $account->id)
            ->where('transaction_date', '>=', $startDate)
            ->where('status', SavingsTransaction::STATUS_APPROVED)
            ->get();

        $deposits = $transactions->where('type', SavingsTransaction::TYPE_DIRECT_DEPOSIT);
        $withdrawals = $transactions->where('type', SavingsTransaction::TYPE_WITHDRAWAL);

        $analytics = [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => now()->toDateString(),
            'total_transactions' => $transactions->count(),
            'total_deposits' => $deposits->count(),
            'total_withdrawals' => $withdrawals->count(),
            'total_deposit_amount' => $deposits->sum('amount'),
            'total_withdrawal_amount' => $withdrawals->sum('amount'),
            'net_change' => $deposits->sum('amount') - $withdrawals->sum('amount'),
            'average_deposit' => $deposits->count() > 0 ? $deposits->avg('amount') : 0,
            'average_withdrawal' => $withdrawals->count() > 0 ? $withdrawals->avg('amount') : 0,
            'largest_deposit' => $deposits->max('amount') ?? 0,
            'largest_withdrawal' => $withdrawals->max('amount') ?? 0,
            'transaction_frequency' => $transactions->count() / max(1, $startDate->diffInDays(now())),
            'monthly_breakdown' => $this->getMonthlyBreakdown($account->id, $startDate),
            'source_breakdown' => $this->getSourceBreakdown($account->id, $startDate),
        ];

        return response()->json(['analytics' => $analytics]);
    }

    /**
     * Export member transactions
     */
    public function exportMemberTransactions(Request $request)
    {
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
            ], 404);
        }

        $format = $request->get('format', 'csv'); // csv, excel, pdf
        $fromDate = $request->get('from_date', now()->subMonths(3)->toDateString());
        $toDate = $request->get('to_date', now()->toDateString());

        $transactions = SavingsTransaction::where('account_id', $account->id)
            ->whereBetween('transaction_date', [$fromDate, $toDate])
            ->orderBy('transaction_date', 'desc')
            ->get();

        if ($format === 'csv') {
            return $this->exportTransactionsCSV($transactions, $member, $fromDate, $toDate);
        } elseif ($format === 'pdf') {
            return $this->exportTransactionsPDF($transactions, $member, $fromDate, $toDate);
        }

        return response()->json(['message' => 'Invalid format'], 400);
    }

    /**
     * Get detailed transaction information
     */
    public function transactionDetails(Request $request, string $id)
    {
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
            ], 404);
        }

        $transaction = SavingsTransaction::where('account_id', $account->id)
            ->where('id', $id)
            ->with(['account.member', 'approvedByUser'])
            ->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found',
            ], 404);
        }

        return response()->json([
            'transaction' => $transaction,
            'related_transactions' => $this->getRelatedTransactions($transaction),
            'audit_trail' => $this->getTransactionAuditTrail($transaction),
        ]);
    }

    /**
     * Generate transaction receipt
     */
    public function transactionReceipt(Request $request, string $id)
    {
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
            ], 404);
        }

        $transaction = SavingsTransaction::where('account_id', $account->id)
            ->where('id', $id)
            ->with(['account.member'])
            ->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found',
            ], 404);
        }

        // Generate PDF receipt
        $pdf = \PDF::loadView('receipts.transaction', [
            'transaction' => $transaction,
            'member' => $member,
            'account' => $account,
        ]);

        return $pdf->download("transaction_receipt_{$transaction->reference}.pdf");
    }

    /**
     * Dispute a transaction
     */
    public function disputeTransaction(Request $request, string $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
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
            ], 404);
        }

        $transaction = SavingsTransaction::where('account_id', $account->id)
            ->where('id', $id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found',
            ], 404);
        }

        if ($transaction->status === SavingsTransaction::STATUS_DISPUTED) {
            return response()->json([
                'message' => 'Transaction is already disputed',
            ], 400);
        }

        // Update transaction status
        $transaction->update([
            'status' => SavingsTransaction::STATUS_DISPUTED,
            'dispute_reason' => $validated['reason'],
            'dispute_description' => $validated['description'] ?? null,
            'disputed_at' => now(),
            'disputed_by' => $user->id,
        ]);

        // Create notification for admin
        // TODO: Implement notification system

        return response()->json([
            'message' => 'Transaction dispute submitted successfully',
            'transaction' => $transaction->fresh(),
        ]);
    }

    /**
     * Cancel pending transaction
     */
    public function cancelPendingTransaction(Request $request, string $id)
    {
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
            ], 404);
        }

        $transaction = SavingsTransaction::where('account_id', $account->id)
            ->where('id', $id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found',
            ], 404);
        }

        if ($transaction->status !== SavingsTransaction::STATUS_PENDING) {
            return response()->json([
                'message' => 'Only pending transactions can be cancelled',
            ], 400);
        }

        // Check if transaction is within cancellation window (e.g., 30 minutes)
        $cancellationWindow = 30; // minutes
        if ($transaction->created_at->diffInMinutes(now()) > $cancellationWindow) {
            return response()->json([
                'message' => "Transaction can only be cancelled within {$cancellationWindow} minutes of submission",
            ], 400);
        }

        $transaction->update([
            'status' => SavingsTransaction::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Transaction cancelled successfully',
            'transaction' => $transaction->fresh(),
        ]);
    }

    // Helper methods
    private function getMonthlyBreakdown($accountId, $startDate)
    {
        $transactions = SavingsTransaction::where('account_id', $accountId)
            ->where('transaction_date', '>=', $startDate)
            ->where('status', SavingsTransaction::STATUS_APPROVED)
            ->selectRaw('YEAR(transaction_date) as year, MONTH(transaction_date) as month, type, SUM(amount) as total')
            ->groupBy('year', 'month', 'type')
            ->get();

        return $transactions->groupBy(function ($item) {
            return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
        })->map(function ($monthTransactions) {
            $deposits = $monthTransactions->where('type', SavingsTransaction::TYPE_DIRECT_DEPOSIT)->sum('total');
            $withdrawals = $monthTransactions->where('type', SavingsTransaction::TYPE_WITHDRAWAL)->sum('total');
            return [
                'deposits' => $deposits,
                'withdrawals' => $withdrawals,
                'net' => $deposits - $withdrawals,
            ];
        });
    }

    private function getSourceBreakdown($accountId, $startDate)
    {
        return SavingsTransaction::where('account_id', $accountId)
            ->where('transaction_date', '>=', $startDate)
            ->where('status', SavingsTransaction::STATUS_APPROVED)
            ->selectRaw('source, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('source')
            ->get()
            ->keyBy('source');
    }

    private function getRelatedTransactions($transaction)
    {
        // Find transactions with same reference or related to this transaction
        return SavingsTransaction::where('account_id', $transaction->account_id)
            ->where('id', '!=', $transaction->id)
            ->where(function ($query) use ($transaction) {
                $query->where('reference', $transaction->reference)
                      ->orWhere('related_transaction_id', $transaction->id)
                      ->orWhere('id', $transaction->related_transaction_id);
            })
            ->orderBy('transaction_date', 'desc')
            ->limit(5)
            ->get();
    }

    private function getTransactionAuditTrail($transaction)
    {
        // Return audit trail for the transaction
        return [
            [
                'action' => 'created',
                'timestamp' => $transaction->created_at,
                'user' => 'System',
                'details' => 'Transaction created',
            ],
            [
                'action' => 'status_change',
                'timestamp' => $transaction->approved_at ?? $transaction->updated_at,
                'user' => $transaction->approvedByUser->name ?? 'System',
                'details' => "Status changed to {$transaction->status}",
            ],
        ];
    }

    private function exportTransactionsCSV($transactions, $member, $fromDate, $toDate)
    {
        $filename = "transactions_{$member->member_number}_{$fromDate}_to_{$toDate}.csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'Date',
                'Type',
                'Description',
                'Amount',
                'Source',
                'Reference',
                'Status',
            ]);

            // Add transaction data
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->transaction_date,
                    $transaction->type,
                    $transaction->description,
                    $transaction->amount,
                    $transaction->source,
                    $transaction->reference,
                    $transaction->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportTransactionsPDF($transactions, $member, $fromDate, $toDate)
    {
        $pdf = \PDF::loadView('reports.member-transactions', [
            'transactions' => $transactions,
            'member' => $member,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);

        $filename = "transactions_{$member->member_number}_{$fromDate}_to_{$toDate}.pdf";
        return $pdf->download($filename);
    }

    /**
     * Get comprehensive member transactions including loan transactions
     */
    public function memberTransactions(Request $request)
    {
        $user = $request->user();
        $member = Member::where('user_id', $user->id)->with(['savingsAccount', 'loans'])->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found',
            ], 404);
        }

        $query = collect();

        // Get savings transactions
        if ($member->savingsAccount) {
            $savingsTransactions = SavingsTransaction::where('account_id', $member->savingsAccount->id)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => 'savings_' . $transaction->type,
                        'category' => 'savings',
                        'amount' => $transaction->amount,
                        'description' => $transaction->description ?: $transaction->source,
                        'reference' => $transaction->reference,
                        'transaction_date' => $transaction->transaction_date,
                        'status' => $transaction->status ?? 'approved',
                        'source' => $transaction->source,
                        'evidence_file' => $transaction->evidence_file,
                        'receipt_file' => $transaction->receipt_file,
                        'created_at' => $transaction->created_at,
                        'updated_at' => $transaction->updated_at,
                    ];
                });
            $query = $query->merge($savingsTransactions);
        }

        // Get loan transactions (disbursements and repayments)
        foreach ($member->loans as $loan) {
            // Add loan disbursement
            if ($loan->disbursement_date) {
                $query->push([
                    'id' => 'loan_disbursement_' . $loan->id,
                    'type' => 'loan_disbursement',
                    'category' => 'loan',
                    'amount' => $loan->amount,
                    'description' => "Loan disbursement - {$loan->loan_number}",
                    'reference' => $loan->loan_number,
                    'transaction_date' => $loan->disbursement_date,
                    'status' => 'approved',
                    'source' => 'loan_disbursement',
                    'loan_id' => $loan->id,
                    'created_at' => $loan->created_at,
                    'updated_at' => $loan->updated_at,
                ]);
            }

            // Add loan repayments
            $repayments = \App\Models\LoanRepayment::where('loan_id', $loan->id)->get();
            foreach ($repayments as $repayment) {
                $query->push([
                    'id' => 'loan_repayment_' . $repayment->id,
                    'type' => 'loan_repayment',
                    'category' => 'loan',
                    'amount' => $repayment->amount,
                    'description' => "Loan repayment - {$loan->loan_number}",
                    'reference' => $repayment->reference,
                    'transaction_date' => $repayment->payment_date,
                    'status' => 'approved',
                    'source' => $repayment->source,
                    'loan_id' => $loan->id,
                    'principal_amount' => $repayment->principal_amount,
                    'interest_amount' => $repayment->interest_amount,
                    'penalty_amount' => $repayment->penalty_amount,
                    'created_at' => $repayment->created_at,
                    'updated_at' => $repayment->updated_at,
                ]);
            }
        }

        // Apply filters
        if ($request->has('type') && $request->type !== 'all') {
            $query = $query->filter(function ($transaction) use ($request) {
                return $transaction['type'] === $request->type || 
                       $transaction['category'] === $request->type;
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query = $query->filter(function ($transaction) use ($request) {
                return $transaction['status'] === $request->status;
            });
        }

        if ($request->has('from_date')) {
            $query = $query->filter(function ($transaction) use ($request) {
                return $transaction['transaction_date'] >= $request->from_date;
            });
        }

        if ($request->has('to_date')) {
            $query = $query->filter(function ($transaction) use ($request) {
                return $transaction['transaction_date'] <= $request->to_date;
            });
        }

        if ($request->has('min_amount')) {
            $query = $query->filter(function ($transaction) use ($request) {
                return $transaction['amount'] >= (float) $request->min_amount;
            });
        }

        if ($request->has('max_amount')) {
            $query = $query->filter(function ($transaction) use ($request) {
                return $transaction['amount'] <= (float) $request->max_amount;
            });
        }

        if ($request->has('search')) {
            $search = strtolower($request->search);
            $query = $query->filter(function ($transaction) use ($search) {
                return str_contains(strtolower($transaction['description']), $search) ||
                       str_contains(strtolower($transaction['reference']), $search) ||
                       str_contains(strtolower($transaction['source']), $search);
            });
        }

        // Sort by date (most recent first)
        $query = $query->sortByDesc('transaction_date')->values();

        // Paginate
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);
        $total = $query->count();
        $items = $query->forPage($page, $perPage);

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Get transaction analytics for member
     */
    public function memberTransactionAnalytics(Request $request)
    {
        $user = $request->user();
        $member = Member::where('user_id', $user->id)->with(['savingsAccount', 'loans'])->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found',
            ], 404);
        }

        $period = $request->get('period', '12months'); // 30days, 90days, 12months, all
        $startDate = match($period) {
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '12months' => now()->subYear(),
            default => null,
        };

        $analytics = [
            'summary' => [
                'total_deposits' => 0,
                'total_withdrawals' => 0,
                'total_loan_disbursements' => 0,
                'total_loan_repayments' => 0,
                'net_savings_flow' => 0,
                'transaction_count' => 0,
                'average_transaction_amount' => 0,
            ],
            'monthly_trends' => [],
            'transaction_breakdown' => [
                'by_type' => [],
                'by_source' => [],
                'by_status' => [],
            ],
            'balance_history' => [],
        ];

        // Get savings transactions
        if ($member->savingsAccount) {
            $savingsQuery = SavingsTransaction::where('account_id', $member->savingsAccount->id);
            if ($startDate) {
                $savingsQuery->where('transaction_date', '>=', $startDate);
            }
            $savingsTransactions = $savingsQuery->get();

            foreach ($savingsTransactions as $transaction) {
                $analytics['summary']['transaction_count']++;
                
                if ($transaction->type === 'deposit' || $transaction->type === 'salary_savings') {
                    $analytics['summary']['total_deposits'] += $transaction->amount;
                    $analytics['summary']['net_savings_flow'] += $transaction->amount;
                } else {
                    $analytics['summary']['total_withdrawals'] += $transaction->amount;
                    $analytics['summary']['net_savings_flow'] -= $transaction->amount;
                }

                // Group by type
                $type = $transaction->type;
                if (!isset($analytics['transaction_breakdown']['by_type'][$type])) {
                    $analytics['transaction_breakdown']['by_type'][$type] = ['count' => 0, 'amount' => 0];
                }
                $analytics['transaction_breakdown']['by_type'][$type]['count']++;
                $analytics['transaction_breakdown']['by_type'][$type]['amount'] += $transaction->amount;

                // Group by source
                $source = $transaction->source;
                if (!isset($analytics['transaction_breakdown']['by_source'][$source])) {
                    $analytics['transaction_breakdown']['by_source'][$source] = ['count' => 0, 'amount' => 0];
                }
                $analytics['transaction_breakdown']['by_source'][$source]['count']++;
                $analytics['transaction_breakdown']['by_source'][$source]['amount'] += $transaction->amount;

                // Group by status
                $status = $transaction->status ?? 'approved';
                if (!isset($analytics['transaction_breakdown']['by_status'][$status])) {
                    $analytics['transaction_breakdown']['by_status'][$status] = ['count' => 0, 'amount' => 0];
                }
                $analytics['transaction_breakdown']['by_status'][$status]['count']++;
                $analytics['transaction_breakdown']['by_status'][$status]['amount'] += $transaction->amount;
            }
        }

        // Get loan transactions
        foreach ($member->loans as $loan) {
            if ($loan->disbursement_date && (!$startDate || $loan->disbursement_date >= $startDate)) {
                $analytics['summary']['total_loan_disbursements'] += $loan->amount;
                $analytics['summary']['transaction_count']++;
            }

            $repaymentQuery = \App\Models\LoanRepayment::where('loan_id', $loan->id);
            if ($startDate) {
                $repaymentQuery->where('payment_date', '>=', $startDate);
            }
            $repayments = $repaymentQuery->get();

            foreach ($repayments as $repayment) {
                $analytics['summary']['total_loan_repayments'] += $repayment->amount;
                $analytics['summary']['transaction_count']++;
            }
        }

        // Calculate average
        if ($analytics['summary']['transaction_count'] > 0) {
            $totalAmount = $analytics['summary']['total_deposits'] + 
                          $analytics['summary']['total_withdrawals'] + 
                          $analytics['summary']['total_loan_disbursements'] + 
                          $analytics['summary']['total_loan_repayments'];
            $analytics['summary']['average_transaction_amount'] = $totalAmount / $analytics['summary']['transaction_count'];
        }

        // Generate monthly trends (last 12 months)
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthKey = $month->format('Y-m');
            
            $analytics['monthly_trends'][] = [
                'month' => $monthKey,
                'month_name' => $month->format('M Y'),
                'deposits' => 0,
                'withdrawals' => 0,
                'loan_repayments' => 0,
                'net_flow' => 0,
            ];
        }

        return response()->json($analytics);
    }

    /**
     * Export member transactions
     */
    public function exportMemberTransactions(Request $request)
    {
        $user = $request->user();
        $member = Member::where('user_id', $user->id)->with(['savingsAccount', 'loans'])->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found',
            ], 404);
        }

        $format = $request->get('format', 'csv'); // csv, excel, pdf
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Get all transactions (reuse logic from memberTransactions)
        $transactionsResponse = $this->memberTransactions($request);
        $transactions = $transactionsResponse->getData()->data;

        $data = [];
        $data[] = ['KITOVU HOSPITAL STAFF SAVING SCHEME'];
        $data[] = ['MEMBER TRANSACTION STATEMENT'];
        $data[] = ['Member: ' . $member->full_name];
        $data[] = ['Member Number: ' . $member->member_number];
        $data[] = ['Period: ' . ($fromDate ? $fromDate : 'All time') . ' to ' . ($toDate ? $toDate : 'Present')];
        $data[] = ['Generated: ' . now()->format('F j, Y g:i A')];
        $data[] = []; // Empty row

        $data[] = ['Date', 'Type', 'Description', 'Reference', 'Amount', 'Status', 'Source'];

        foreach ($transactions as $transaction) {
            $data[] = [
                $transaction->transaction_date,
                ucwords(str_replace('_', ' ', $transaction->type)),
                $transaction->description,
                $transaction->reference,
                number_format($transaction->amount, 0),
                ucfirst($transaction->status),
                ucwords(str_replace('_', ' ', $transaction->source)),
            ];
        }

        $filename = 'transaction_statement_' . $member->member_number . '_' . date('Y-m-d');

        if ($format === 'csv') {
            $output = fopen('php://temp', 'r+');
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');
        }

        // For now, default to CSV. PDF and Excel can be implemented later
        return $this->exportMemberTransactions($request->merge(['format' => 'csv']));
    }

    /**
     * Get detailed transaction information
     */
    public function transactionDetails(Request $request, string $id)
    {
        $user = $request->user();
        $member = Member::where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found',
            ], 404);
        }

        // Handle different transaction ID formats
        if (str_starts_with($id, 'loan_disbursement_')) {
            $loanId = str_replace('loan_disbursement_', '', $id);
            $loan = \App\Models\Loan::where('id', $loanId)
                ->where('member_id', $member->id)
                ->with(['member', 'approvedBy', 'disbursedBy'])
                ->first();

            if (!$loan) {
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            return response()->json([
                'id' => $id,
                'type' => 'loan_disbursement',
                'category' => 'loan',
                'amount' => $loan->amount,
                'description' => "Loan disbursement - {$loan->loan_number}",
                'reference' => $loan->loan_number,
                'transaction_date' => $loan->disbursement_date,
                'status' => 'approved',
                'details' => [
                    'loan_number' => $loan->loan_number,
                    'loan_amount' => $loan->amount,
                    'interest_rate' => $loan->interest_rate,
                    'term_months' => $loan->term_months,
                    'purpose' => $loan->purpose,
                    'approved_by' => $loan->approvedBy?->name,
                    'disbursed_by' => $loan->disbursedBy?->name,
                    'approval_date' => $loan->approval_date,
                    'disbursement_date' => $loan->disbursement_date,
                ],
            ]);
        }

        if (str_starts_with($id, 'loan_repayment_')) {
            $repaymentId = str_replace('loan_repayment_', '', $id);
            $repayment = \App\Models\LoanRepayment::where('id', $repaymentId)
                ->whereHas('loan', function ($query) use ($member) {
                    $query->where('member_id', $member->id);
                })
                ->with(['loan', 'recordedBy'])
                ->first();

            if (!$repayment) {
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            return response()->json([
                'id' => $id,
                'type' => 'loan_repayment',
                'category' => 'loan',
                'amount' => $repayment->amount,
                'description' => "Loan repayment - {$repayment->loan->loan_number}",
                'reference' => $repayment->reference,
                'transaction_date' => $repayment->payment_date,
                'status' => 'approved',
                'details' => [
                    'loan_number' => $repayment->loan->loan_number,
                    'principal_amount' => $repayment->principal_amount,
                    'interest_amount' => $repayment->interest_amount,
                    'penalty_amount' => $repayment->penalty_amount,
                    'source' => $repayment->source,
                    'recorded_by' => $repayment->recordedBy?->name,
                    'notes' => $repayment->notes,
                ],
            ]);
        }

        // Regular savings transaction
        $transaction = SavingsTransaction::where('id', $id)
            ->whereHas('account', function ($query) use ($member) {
                $query->where('member_id', $member->id);
            })
            ->with(['account', 'approvedByUser'])
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        return response()->json([
            'id' => $transaction->id,
            'type' => 'savings_' . $transaction->type,
            'category' => 'savings',
            'amount' => $transaction->amount,
            'description' => $transaction->description ?: $transaction->source,
            'reference' => $transaction->reference,
            'transaction_date' => $transaction->transaction_date,
            'status' => $transaction->status ?? 'approved',
            'source' => $transaction->source,
            'evidence_file' => $transaction->evidence_file,
            'receipt_file' => $transaction->receipt_file,
            'details' => [
                'account_number' => $transaction->account->account_number,
                'approved_by' => $transaction->approvedByUser?->name,
                'approved_at' => $transaction->approved_at,
                'rejection_reason' => $transaction->rejection_reason,
                'salary_period' => $transaction->salary_period,
                'employer_reference' => $transaction->employer_reference,
                'is_reversed' => $transaction->is_reversed,
                'reversed_at' => $transaction->reversed_at,
            ],
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at,
        ]);
    }

    /**
     * Generate transaction receipt
     */
    public function transactionReceipt(Request $request, string $id)
    {
        $transactionDetails = $this->transactionDetails($request, $id);
        
        if ($transactionDetails->getStatusCode() !== 200) {
            return $transactionDetails;
        }

        $transaction = $transactionDetails->getData();
        $user = $request->user();
        $member = Member::where('user_id', $user->id)->first();

        $receiptData = [
            'organization' => 'KITOVU HOSPITAL STAFF SAVING SCHEME',
            'receipt_number' => 'RCP-' . strtoupper(substr($transaction->reference, -8)),
            'transaction' => $transaction,
            'member' => $member,
            'generated_at' => now()->format('F j, Y g:i A'),
        ];

        // For now, return JSON. Later can implement PDF generation
        return response()->json([
            'receipt' => $receiptData,
            'download_url' => null, // Will implement PDF download later
        ]);
    }

    /**
     * Dispute a transaction
     */
    public function disputeTransaction(Request $request, string $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $member = Member::where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found',
            ], 404);
        }

        // Only allow disputes for savings transactions
        $transaction = SavingsTransaction::where('id', $id)
            ->whereHas('account', function ($query) use ($member) {
                $query->where('member_id', $member->id);
            })
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // Create dispute record (you may need to create a disputes table)
        // For now, we'll add a note to the transaction
        $transaction->update([
            'description' => $transaction->description . ' [DISPUTED: ' . $validated['reason'] . ']'
        ]);

        return response()->json([
            'message' => 'Transaction dispute submitted successfully',
            'dispute_reference' => 'DSP-' . time(),
        ]);
    }

    /**
     * Cancel pending transaction
     */
    public function cancelPendingTransaction(Request $request, string $id)
    {
        $user = $request->user();
        $member = Member::where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found',
            ], 404);
        }

        $transaction = SavingsTransaction::where('id', $id)
            ->whereHas('account', function ($query) use ($member) {
                $query->where('member_id', $member->id);
            })
            ->where('status', SavingsTransaction::STATUS_PENDING)
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Pending transaction not found'], 404);
        }

        $transaction->update([
            'status' => SavingsTransaction::STATUS_REJECTED,
            'rejection_reason' => 'Cancelled by member',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Transaction cancelled successfully',
        ]);
    }
/**
     * Approve a pending transaction (admin only)
     */
    public function approveTransaction(Request $request, string $id)
    {
        DB::beginTransaction();
        try {
            $transaction = SavingsTransaction::with('account')->findOrFail($id);

            if ($transaction->status !== SavingsTransaction::STATUS_PENDING) {
                return response()->json([
                    'message' => 'Transaction is not pending approval',
                ], 400);
            }

            // Update transaction status
            $transaction->update([
                'status' => SavingsTransaction::STATUS_APPROVED,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            // Update account balance
            $account = $transaction->account;
            if ($transaction->type === SavingsTransaction::TYPE_WITHDRAWAL) {
                $account->decrement('balance', $transaction->amount);
            } else {
                $account->increment('balance', $transaction->amount);
            }

            DB::commit();

            return response()->json([
                'message' => 'Transaction approved successfully',
                'transaction' => $transaction->fresh(['account', 'approvedByUser']),
                'new_balance' => $account->fresh()->balance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to approve transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a pending transaction (admin only)
     */
    public function rejectTransaction(Request $request, string $id)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $transaction = SavingsTransaction::findOrFail($id);

            if ($transaction->status !== SavingsTransaction::STATUS_PENDING) {
                return response()->json([
                    'message' => 'Transaction is not pending approval',
                ], 400);
            }

            $transaction->update([
                'status' => SavingsTransaction::STATUS_REJECTED,
                'rejection_reason' => $validated['rejection_reason'],
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            return response()->json([
                'message' => 'Transaction rejected',
                'transaction' => $transaction->fresh(['account', 'approvedByUser']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reject transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pending transactions (admin only)
     */
    public function pendingTransactions(Request $request)
    {
        $transactions = SavingsTransaction::with(['account.member', 'approvedByUser'])
            ->where('status', SavingsTransaction::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }

    /**
     * Generate salary deduction report in Excel format
     */
    public function salaryDeductionReportExcel()
    {
        try {
            $members = Member::with('savingsAccount')
                ->where('status', Member::STATUS_ACTIVE)
                ->whereNotNull('employment_info')
                ->get()
                ->filter(function ($member) {
                    $employmentInfo = $member->employment_info;
                    return is_array($employmentInfo) && 
                           isset($employmentInfo['monthly_savings']) && 
                           $employmentInfo['monthly_savings'] > 0;
                })
                ->sortBy(['category', 'member_number']);

            if ($members->isEmpty()) {
                return response()->json([
                    'message' => 'No members found with salary deduction information'
                ], 404);
            }

            // Group by category
            $groupedMembers = $members->groupBy('category');

            $data = [];
            $data[] = ['KITOVU HOSPITAL STAFF SAVING SCHEME'];
            $data[] = ['SALARY DEDUCTION REPORT'];
            $data[] = ['Generated on: ' . now()->format('F j, Y')];
            $data[] = []; // Empty row

            foreach ($groupedMembers as $category => $categoryMembers) {
                $categoryLabel = $this->getCategoryLabel($category);
                $data[] = [strtoupper($categoryLabel)];
                $data[] = ['Member Number', 'Member Name', 'Account Number', 'Monthly Deduction (UGX)', 'Category'];
                
                $categoryTotal = 0;
                foreach ($categoryMembers as $member) {
                    $employmentInfo = $member->employment_info;
                    $monthlyDeduction = is_array($employmentInfo) && isset($employmentInfo['monthly_savings']) 
                        ? $employmentInfo['monthly_savings'] 
                        : 0;
                    $categoryTotal += $monthlyDeduction;
                    
                    $data[] = [
                        $member->member_number ?? 'N/A',
                        $member->full_name ?? 'N/A',
                        $member->savingsAccount ? $member->savingsAccount->account_number : 'N/A',
                        number_format($monthlyDeduction, 0),
                        $categoryLabel
                    ];
                }
                
                $data[] = ['', '', 'SUBTOTAL:', number_format($categoryTotal, 0), ''];
                $data[] = []; // Empty row
            }

            $grandTotal = $members->sum(function ($member) {
                $employmentInfo = $member->employment_info;
                return is_array($employmentInfo) && isset($employmentInfo['monthly_savings']) 
                    ? $employmentInfo['monthly_savings'] 
                    : 0;
            });
            
            $data[] = ['', '', 'GRAND TOTAL:', number_format($grandTotal, 0), ''];

            // Create a simple CSV response instead of Excel for now
            $filename = 'salary_deduction_report_' . date('Y-m-d') . '.csv';
            
            $output = fopen('php://temp', 'r+');
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            \Log::error('Excel export error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Failed to generate Excel report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate salary deduction report in PDF format
     */
    public function salaryDeductionReportPDF()
    {
        $members = Member::with('savingsAccount')
            ->where('status', Member::STATUS_ACTIVE)
            ->whereNotNull('employment_info')
            ->get()
            ->filter(function ($member) {
                return isset($member->employment_info['monthly_savings']) && 
                       $member->employment_info['monthly_savings'] > 0;
            })
            ->sortBy(['category', 'member_number']);

        // Group by category
        $groupedMembers = $members->groupBy('category');
        
        $grandTotal = $members->sum(function ($member) {
            return $member->employment_info['monthly_savings'] ?? 0;
        });

        $data = [
            'groupedMembers' => $groupedMembers,
            'grandTotal' => $grandTotal,
            'generatedDate' => now()->format('F j, Y'),
            'getCategoryLabel' => [$this, 'getCategoryLabel']
        ];

        $pdf = \PDF::loadView('reports.salary-deduction', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->download('salary_deduction_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Get category label
     */
    private function getCategoryLabel($category)
    {
        $labels = [
            Member::CATEGORY_STAFF => 'Staff',
            Member::CATEGORY_ACT_PROGRAM => 'ACT Program',
            Member::CATEGORY_NURSING_SCHOOL => 'Nursing School',
            Member::CATEGORY_HC_STAFF => 'HC Staff',
            Member::CATEGORY_NON_HOSPITAL_STAFF => 'Non Hospital Staff'
        ];
        
        return $labels[$category] ?? ucfirst(str_replace('_', ' ', $category));
    }

    /**
     * Get upload history
     */
    public function uploadHistory(Request $request)
    {
        $query = UploadLog::with('user:id,name')
            ->where('upload_type', UploadLog::TYPE_SALARY_DEDUCTIONS)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('period') && $request->period !== 'all') {
            $period = $request->period;
            switch ($period) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'quarter':
                    $query->whereBetween('created_at', [now()->startOfQuarter(), now()->endOfQuarter()]);
                    break;
            }
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('file_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $uploads = $query->paginate(20);

        // Transform the data for frontend
        $uploads->getCollection()->transform(function ($upload) {
            return [
                'id' => $upload->id,
                'upload_type' => $upload->upload_type,
                'file_name' => $upload->file_name,
                'total_records' => $upload->total_records,
                'successful_records' => $upload->successful_records,
                'failed_records' => $upload->failed_records,
                'total_amount' => $upload->total_amount_processed,
                'upload_date' => $upload->created_at->toISOString(),
                'uploaded_by' => [
                    'name' => $upload->user->name ?? 'Unknown'
                ],
                'status' => $upload->status,
                'bank_receipt' => $upload->bank_receipt,
            ];
        });

        return response()->json([
            'data' => $uploads->items(),
            'pagination' => [
                'current_page' => $uploads->currentPage(),
                'last_page' => $uploads->lastPage(),
                'per_page' => $uploads->perPage(),
                'total' => $uploads->total(),
            ]
        ]);
    }

    /**
     * Download bank receipt
     */
    public function downloadReceipt($receiptPath)
    {
        try {
            $fullPath = storage_path('app/public/' . $receiptPath);
            
            if (!file_exists($fullPath)) {
                return response()->json([
                    'message' => 'Receipt file not found'
                ], 404);
            }

            return response()->download($fullPath);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to download receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
