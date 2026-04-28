<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Services\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use League\Csv\Reader;

class RepaymentController extends Controller
{
    protected LoanService $loanService;

    public function __construct(LoanService $loanService)
    {
        $this->loanService = $loanService;
    }

    /**
     * Record manual repayment
     */
    public function store(Request $request, string $loanId)
    {
        $loan = Loan::findOrFail($loanId);

        // Only allow repayments for active loans
        if ($loan->status !== Loan::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'Loan must be active to record repayments',
                'current_status' => $loan->status,
            ], 422);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date|before_or_equal:today',
            'source' => 'required|in:manual,cash,bank_transfer,mobile_money',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Calculate repayment breakdown
            $outstandingBalance = $this->loanService->calculateOutstandingBalance($loan);
            $repaymentAmount = min((float) $validated['amount'], $outstandingBalance);

            // Calculate principal and interest
            $monthlyInterestRate = ((float) $loan->interest_rate / 100) / 12;
            $interestPayment = $outstandingBalance * $monthlyInterestRate;
            $principalPayment = $repaymentAmount - $interestPayment;

            // Create repayment record
            $repayment = LoanRepayment::create([
                'loan_id' => $loanId,
                'amount' => $repaymentAmount,
                'principal_amount' => max(0, $principalPayment),
                'interest_amount' => min($interestPayment, $repaymentAmount),
                'penalty_amount' => 0,
                'payment_date' => $validated['payment_date'],
                'source' => $validated['source'],
                'reference' => $validated['reference'] ?? LoanRepayment::generateReference(),
                'recorded_by' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update loan outstanding balance
            $newBalance = max(0, $outstandingBalance - $repaymentAmount);
            $loan->update(['outstanding_balance' => $newBalance]);

            // Update loan status if fully repaid
            if ($newBalance <= 0) {
                $loan->update(['status' => Loan::STATUS_CLOSED]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Repayment recorded successfully',
                'repayment' => $this->formatRepaymentResponse($repayment),
                'loan_outstanding_balance' => (float) $newBalance,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to record repayment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loan repayments
     */
    public function index(string $loanId)
    {
        $loan = Loan::findOrFail($loanId);
        $repayments = $loan->repayments()
            ->orderBy('payment_date', 'desc')
            ->paginate(15);

        return response()->json([
            'loan_id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'data' => $repayments->map(fn($r) => $this->formatRepaymentResponse($r)),
            'meta' => [
                'current_page' => $repayments->currentPage(),
                'total' => $repayments->total(),
                'per_page' => $repayments->perPage(),
                'last_page' => $repayments->lastPage(),
            ],
        ]);
    }

    /**
     * Upload salary deduction repayments
     */
    public function uploadDeductions(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        try {
            DB::beginTransaction();

            $file = $validated['file'];
            $csv = Reader::createFromPath($file->getRealPath(), 'r');
            $csv->setHeaderOffset(0);

            $processed = 0;
            $failed = 0;
            $errors = [];
            $summary = [
                'total_amount' => 0,
                'total_repayments' => 0,
                'processed_records' => 0,
                'failed_records' => 0,
                'errors' => [],
            ];

            foreach ($csv->getRecords() as $index => $record) {
                try {
                    // Parse CSV record
                    $loanNumber = trim($record['loan_number'] ?? '');
                    $memberNumber = trim($record['member_number'] ?? '');
                    $amount = (float) ($record['amount'] ?? 0);
                    $paymentDate = $record['payment_date'] ?? date('Y-m-d');

                    // Validate required fields
                    if (!$loanNumber || !$memberNumber || $amount <= 0) {
                        $summary['failed_records']++;
                        $summary['errors'][] = "Row " . ($index + 2) . ": Missing or invalid required fields";
                        continue;
                    }

                    // Find loan
                    $loan = Loan::where('loan_number', $loanNumber)->first();
                    if (!$loan) {
                        $summary['failed_records']++;
                        $summary['errors'][] = "Row " . ($index + 2) . ": Loan {$loanNumber} not found";
                        continue;
                    }

                    // Verify member matches
                    if ($loan->member->member_number !== $memberNumber) {
                        $summary['failed_records']++;
                        $summary['errors'][] = "Row " . ($index + 2) . ": Member mismatch for loan {$loanNumber}";
                        continue;
                    }

                    // Only process active loans
                    if ($loan->status !== Loan::STATUS_ACTIVE) {
                        $summary['failed_records']++;
                        $summary['errors'][] = "Row " . ($index + 2) . ": Loan {$loanNumber} is not active";
                        continue;
                    }

                    // Calculate repayment breakdown
                    $outstandingBalance = $this->loanService->calculateOutstandingBalance($loan);
                    $repaymentAmount = min($amount, $outstandingBalance);

                    $monthlyInterestRate = ((float) $loan->interest_rate / 100) / 12;
                    $interestPayment = $outstandingBalance * $monthlyInterestRate;
                    $principalPayment = $repaymentAmount - $interestPayment;

                    // Create repayment record
                    LoanRepayment::create([
                        'loan_id' => $loan->id,
                        'amount' => $repaymentAmount,
                        'principal_amount' => max(0, $principalPayment),
                        'interest_amount' => min($interestPayment, $repaymentAmount),
                        'penalty_amount' => 0,
                        'payment_date' => $paymentDate,
                        'source' => LoanRepayment::SOURCE_SALARY_DEDUCTION,
                        'reference' => 'SALARY_DED_' . date('YmdHis') . '_' . $loan->id,
                        'recorded_by' => Auth::id(),
                        'notes' => 'Automatic salary deduction',
                    ]);

                    // Update loan outstanding balance
                    $newBalance = max(0, $outstandingBalance - $repaymentAmount);
                    $loan->update(['outstanding_balance' => $newBalance]);

                    // Update loan status if fully repaid
                    if ($newBalance <= 0) {
                        $loan->update(['status' => Loan::STATUS_CLOSED]);
                    }

                    $summary['total_amount'] += $repaymentAmount;
                    $summary['total_repayments']++;
                    $summary['processed_records']++;
                    $processed++;
                } catch (\Exception $e) {
                    $summary['failed_records']++;
                    $summary['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
                    $failed++;
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Salary deduction repayments processed',
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process salary deductions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loan status and tracking information
     */
    public function trackingInfo(string $loanId)
    {
        $loan = Loan::with(['repayments', 'member.savingsAccount'])->findOrFail($loanId);

        $outstandingBalance = $this->loanService->calculateOutstandingBalance($loan);
        $isOverdue = $this->loanService->isLoanOverdue($loan);
        $repaymentSchedule = $this->loanService->generateRepaymentSchedule($loan);

        // Calculate next payment due
        $nextPaymentDue = null;
        if ($loan->first_repayment_date) {
            $lastRepayment = $loan->repayments()
                ->orderBy('payment_date', 'desc')
                ->first();

            $lastPaymentDate = $lastRepayment ? 
                $lastRepayment->payment_date : 
                $loan->first_repayment_date;

            $nextPaymentDue = \Carbon\Carbon::parse($lastPaymentDate)->addMonth()->format('Y-m-d');
        }

        return response()->json([
            'loan' => [
                'id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'amount' => (float) $loan->amount,
                'status' => $loan->status,
                'interest_rate' => (float) $loan->interest_rate,
                'term_months' => $loan->term_months,
                'monthly_repayment' => (float) $loan->monthly_repayment,
            ],
            'tracking' => [
                'outstanding_balance' => (float) $outstandingBalance,
                'total_repaid' => (float) $loan->getTotalRepaid(),
                'remaining_payments' => max(0, $loan->term_months - count($loan->repayments)),
                'is_overdue' => $isOverdue,
                'next_payment_due' => $nextPaymentDue,
                'disbursement_date' => $loan->disbursement_date?->format('Y-m-d'),
                'first_repayment_date' => $loan->first_repayment_date?->format('Y-m-d'),
            ],
            'payment_history' => $loan->repayments()
                ->orderBy('payment_date', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($r) => $this->formatRepaymentResponse($r)),
            'repayment_schedule' => $repaymentSchedule,
        ]);
    }

    /**
     * Format repayment response
     */
    private function formatRepaymentResponse(LoanRepayment $repayment): array
    {
        return [
            'id' => $repayment->id,
            'loan_id' => $repayment->loan_id,
            'amount' => (float) $repayment->amount,
            'principal_amount' => (float) $repayment->principal_amount,
            'interest_amount' => (float) $repayment->interest_amount,
            'penalty_amount' => (float) $repayment->penalty_amount,
            'payment_date' => $repayment->payment_date->format('Y-m-d'),
            'source' => $repayment->source,
            'reference' => $repayment->reference,
            'recorded_by' => $repayment->recordedByUser ? [
                'id' => $repayment->recordedByUser->id,
                'name' => $repayment->recordedByUser->name,
            ] : null,
            'notes' => $repayment->notes,
            'created_at' => $repayment->created_at->toIso8601String(),
        ];
    }
}
