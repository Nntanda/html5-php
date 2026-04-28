<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\LoanRepayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Generate member statement
     */
    public function generateMemberStatement(Member $member, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonths(12);
        $endDate = $endDate ?? now();

        $savingsAccount = $member->savingsAccount;
        $loans = $member->loans()
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_CLOSED])
            ->get();

        $savingsTransactions = [];
        if ($savingsAccount) {
            $savingsTransactions = $savingsAccount->transactions()
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->orderBy('transaction_date', 'desc')
                ->get()
                ->map(fn($t) => [
                    'date' => $t->transaction_date->format('Y-m-d'),
                    'type' => $t->type,
                    'amount' => (float) $t->amount,
                    'source' => $t->source,
                    'reference' => $t->reference,
                ])
                ->toArray();
        }

        $loanTransactions = [];
        foreach ($loans as $loan) {
            $repayments = $loan->repayments()
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->get();

            foreach ($repayments as $repayment) {
                $loanTransactions[] = [
                    'date' => $repayment->payment_date->format('Y-m-d'),
                    'loan_number' => $loan->loan_number,
                    'type' => 'loan_repayment',
                    'amount' => (float) $repayment->amount,
                    'source' => $repayment->source,
                    'reference' => $repayment->reference,
                ];
            }
        }

        return [
            'member_id' => $member->id,
            'member_number' => $member->member_number,
            'member_name' => $member->full_name,
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'savings' => [
                'current_balance' => $savingsAccount ? (float) $savingsAccount->balance : 0,
                'transactions' => $savingsTransactions,
            ],
            'loans' => [
                'active_loans' => $loans->where('status', Loan::STATUS_ACTIVE)->count(),
                'closed_loans' => $loans->where('status', Loan::STATUS_CLOSED)->count(),
                'total_borrowed' => (float) $loans->sum('amount'),
                'total_repaid' => (float) $loans->sum(fn($l) => $l->getTotalRepaid()),
            ],
            'transactions' => $loanTransactions,
        ];
    }

    /**
     * Generate member loan summary
     */
    public function generateMemberLoanSummary(Member $member, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonths(12);
        $endDate = $endDate ?? now();

        $loans = $member->loans()
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_CLOSED, Loan::STATUS_OVERDUE])
            ->get();

        $loanSummaries = $loans->map(function ($loan) use ($startDate, $endDate) {
            $repayments = $loan->repayments()
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->get();

            return [
                'loan_id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'amount' => (float) $loan->amount,
                'interest_rate' => (float) $loan->interest_rate,
                'term_months' => $loan->term_months,
                'status' => $loan->status,
                'application_date' => $loan->application_date->format('Y-m-d'),
                'disbursement_date' => $loan->disbursement_date?->format('Y-m-d'),
                'total_repaid' => (float) $loan->getTotalRepaid(),
                'outstanding_balance' => (float) $loan->getRemainingBalance(),
                'monthly_repayment' => (float) $loan->monthly_repayment,
                'repayments_in_period' => $repayments->count(),
                'total_repaid_in_period' => (float) $repayments->sum('amount'),
            ];
        })->toArray();

        return [
            'member_id' => $member->id,
            'member_number' => $member->member_number,
            'member_name' => $member->full_name,
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_loans' => $loans->count(),
                'active_loans' => $loans->where('status', Loan::STATUS_ACTIVE)->count(),
                'closed_loans' => $loans->where('status', Loan::STATUS_CLOSED)->count(),
                'overdue_loans' => $loans->where('status', Loan::STATUS_OVERDUE)->count(),
                'total_borrowed' => (float) $loans->sum('amount'),
                'total_repaid' => (float) $loans->sum(fn($l) => $l->getTotalRepaid()),
                'total_outstanding' => (float) $loans->sum(fn($l) => $l->getRemainingBalance()),
            ],
            'loans' => $loanSummaries,
        ];
    }

    /**
     * Generate SACCO savings summary
     */
    public function generateSavingsSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonths(12);
        $endDate = $endDate ?? now();

        $savingsAccounts = SavingsAccount::all();
        $transactions = SavingsTransaction::whereBetween('transaction_date', [$startDate, $endDate])->get();

        $totalDeposits = (float) $transactions->where('type', SavingsTransaction::TYPE_SALARY_SAVINGS)
            ->sum('amount');
        $totalWithdrawals = (float) $transactions->where('type', SavingsTransaction::TYPE_WITHDRAWAL)
            ->sum('amount');

        $transactionsBySource = $transactions->groupBy('source')
            ->map(fn($group) => (float) $group->sum('amount'))
            ->toArray();

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_members' => $savingsAccounts->count(),
                'total_savings' => (float) $savingsAccounts->sum('balance'),
                'average_savings_per_member' => $savingsAccounts->count() > 0 
                    ? (float) $savingsAccounts->sum('balance') / $savingsAccounts->count() 
                    : 0,
                'total_deposits' => $totalDeposits,
                'total_withdrawals' => $totalWithdrawals,
                'net_change' => $totalDeposits - $totalWithdrawals,
            ],
            'by_source' => $transactionsBySource,
            'transaction_count' => $transactions->count(),
        ];
    }

    /**
     * Generate SACCO loans summary
     */
    public function generateLoansSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonths(12);
        $endDate = $endDate ?? now();

        $loans = Loan::all();
        $repayments = LoanRepayment::whereBetween('payment_date', [$startDate, $endDate])->get();

        $activeLoans = $loans->where('status', Loan::STATUS_ACTIVE);
        $closedLoans = $loans->where('status', Loan::STATUS_CLOSED);
        $overdueLoans = $loans->where('status', Loan::STATUS_OVERDUE);

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_loans' => $loans->count(),
                'active_loans' => $activeLoans->count(),
                'closed_loans' => $closedLoans->count(),
                'overdue_loans' => $overdueLoans->count(),
                'pending_loans' => $loans->where('status', Loan::STATUS_PENDING)->count(),
                'total_disbursed' => (float) $loans->sum('amount'),
                'total_outstanding' => (float) $activeLoans->sum(fn($l) => $l->getRemainingBalance()),
                'total_repaid' => (float) $repayments->sum('amount'),
                'average_loan_amount' => $loans->count() > 0 ? (float) $loans->sum('amount') / $loans->count() : 0,
            ],
            'by_status' => [
                'pending' => $loans->where('status', Loan::STATUS_PENDING)->count(),
                'guarantors_approved' => $loans->where('status', Loan::STATUS_GUARANTORS_APPROVED)->count(),
                'approved_pending_disbursement' => $loans->where('status', Loan::STATUS_APPROVED_PENDING_DISBURSEMENT)->count(),
                'active' => $activeLoans->count(),
                'closed' => $closedLoans->count(),
                'overdue' => $overdueLoans->count(),
                'rejected' => $loans->where('status', Loan::STATUS_REJECTED)->count(),
            ],
            'repayments_in_period' => $repayments->count(),
        ];
    }

    /**
     * Generate transaction report
     */
    public function generateTransactionReport(?Carbon $startDate = null, ?Carbon $endDate = null, ?string $type = null): array
    {
        $startDate = $startDate ?? now()->subMonths(12);
        $endDate = $endDate ?? now();

        $savingsTransactions = SavingsTransaction::whereBetween('transaction_date', [$startDate, $endDate]);
        $loanRepayments = LoanRepayment::whereBetween('payment_date', [$startDate, $endDate]);

        if ($type === 'savings') {
            $savingsTransactions = $savingsTransactions->get();
            $loanRepayments = collect();
        } elseif ($type === 'loan') {
            $savingsTransactions = collect();
            $loanRepayments = $loanRepayments->get();
        } else {
            $savingsTransactions = $savingsTransactions->get();
            $loanRepayments = $loanRepayments->get();
        }

        $savingsData = $savingsTransactions->map(fn($t) => [
            'date' => $t->transaction_date->format('Y-m-d'),
            'type' => 'savings_' . $t->type,
            'amount' => (float) $t->amount,
            'source' => $t->source,
            'reference' => $t->reference,
            'member_id' => $t->account->member_id,
        ])->toArray();

        $loanData = $loanRepayments->map(fn($r) => [
            'date' => $r->payment_date->format('Y-m-d'),
            'type' => 'loan_repayment',
            'amount' => (float) $r->amount,
            'source' => $r->source,
            'reference' => $r->reference,
            'member_id' => $r->loan->member_id,
        ])->toArray();

        $allTransactions = array_merge($savingsData, $loanData);
        usort($allTransactions, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        $totalAmount = array_sum(array_column($allTransactions, 'amount'));

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_transactions' => count($allTransactions),
                'total_amount' => (float) $totalAmount,
                'savings_transactions' => $savingsTransactions->count(),
                'loan_repayments' => $loanRepayments->count(),
            ],
            'transactions' => $allTransactions,
        ];
    }

    /**
     * Generate overdue loans report
     */
    public function generateOverdueLoansReport(?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();

        $overdueLoans = Loan::where('status', Loan::STATUS_ACTIVE)
            ->get()
            ->filter(function ($loan) use ($asOfDate) {
                $lastRepayment = $loan->repayments()
                    ->orderBy('payment_date', 'desc')
                    ->first();

                $lastPaymentDate = $lastRepayment 
                    ? $lastRepayment->payment_date 
                    : $loan->first_repayment_date;

                if (!$lastPaymentDate) {
                    return false;
                }

                $daysOverdue = $asOfDate->diffInDays($lastPaymentDate);
                return $daysOverdue > 30;
            });

        $overdueDetails = $overdueLoans->map(function ($loan) use ($asOfDate) {
            $lastRepayment = $loan->repayments()
                ->orderBy('payment_date', 'desc')
                ->first();

            $lastPaymentDate = $lastRepayment 
                ? $lastRepayment->payment_date 
                : $loan->first_repayment_date;

            $daysOverdue = $lastPaymentDate ? $asOfDate->diffInDays($lastPaymentDate) : 0;

            return [
                'loan_id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'member_id' => $loan->member_id,
                'member_name' => $loan->member->full_name,
                'member_phone' => $loan->member->phone,
                'loan_amount' => (float) $loan->amount,
                'outstanding_balance' => (float) $loan->getRemainingBalance(),
                'monthly_repayment' => (float) $loan->monthly_repayment,
                'last_payment_date' => $lastPaymentDate?->format('Y-m-d'),
                'days_overdue' => $daysOverdue,
                'estimated_penalty' => $this->calculatePenalty($loan, $daysOverdue),
            ];
        })->sortByDesc('days_overdue')->values()->toArray();

        return [
            'as_of_date' => $asOfDate->format('Y-m-d'),
            'summary' => [
                'total_overdue_loans' => count($overdueDetails),
                'total_overdue_amount' => (float) array_sum(array_column($overdueDetails, 'outstanding_balance')),
                'total_estimated_penalty' => (float) array_sum(array_column($overdueDetails, 'estimated_penalty')),
            ],
            'loans' => $overdueDetails,
        ];
    }

    /**
     * Calculate penalty for overdue loan
     */
    private function calculatePenalty(Loan $loan, int $daysOverdue): float
    {
        $penaltyRate = (float) \App\Models\SystemConfig::getValue('late_payment_penalty_rate', 2.0);
        $monthlyRepayment = (float) $loan->monthly_repayment;
        $monthsOverdue = ceil($daysOverdue / 30);

        return ($monthlyRepayment * $penaltyRate / 100) * $monthsOverdue;
    }
}
