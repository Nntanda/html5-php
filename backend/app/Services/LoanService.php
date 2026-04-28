<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Member;
use App\Models\SavingsAccount;
use Illuminate\Support\Facades\DB;

class LoanService
{
    /**
     * Calculate maximum loan amount based on member's savings
     */
    public function calculateMaxLoanAmount(Member $member): float
    {
        $savingsAccount = $member->savingsAccount;
        
        if (!$savingsAccount) {
            return 0;
        }

        // Get system configuration
        $maxLoanMultiplier = (float) config('sacco.max_loan_multiplier', 3);
        
        // Calculate maximum based on savings
        $maxLoanAmount = $savingsAccount->balance * $maxLoanMultiplier;

        return (float) $maxLoanAmount;
    }

    /**
     * Validate loan eligibility
     */
    public function validateLoanEligibility(Member $member, float $requestedAmount): array
    {
        $errors = [];

        // Check member status
        if (!$member->isActive()) {
            $errors[] = 'Member is not active';
        }

        // Check if member has savings account
        if (!$member->savingsAccount) {
            $errors[] = 'Member does not have a savings account';
        }

        // Check minimum savings period
        if ($member->savingsAccount) {
            $minSavingsPeriodMonths = (int) config('sacco.min_savings_period_months', 6);
            $monthsSinceSavings = $member->savingsAccount->created_at->diffInMonths(now());
            
            if ($monthsSinceSavings < $minSavingsPeriodMonths) {
                $errors[] = "Member must have savings for at least {$minSavingsPeriodMonths} months";
            }
        }

        // Check maximum loan amount
        $maxLoanAmount = $this->calculateMaxLoanAmount($member);
        if ($requestedAmount > $maxLoanAmount) {
            $errors[] = "Requested amount exceeds maximum eligible amount of " . number_format($maxLoanAmount, 2);
        }

        // Check existing active loans
        $activeLoans = $member->loans()
            ->whereIn('status', [
                Loan::STATUS_ACTIVE,
                Loan::STATUS_APPROVED_PENDING_DISBURSEMENT,
                Loan::STATUS_GUARANTORS_APPROVED,
            ])
            ->count();

        if ($activeLoans > 0) {
            $errors[] = 'Member has existing active loans';
        }

        // Check repayment history
        $overdueLoans = $member->loans()
            ->where('status', Loan::STATUS_OVERDUE)
            ->count();

        if ($overdueLoans > 0) {
            $errors[] = 'Member has overdue loans';
        }

        return [
            'eligible' => count($errors) === 0,
            'errors' => $errors,
            'max_loan_amount' => $maxLoanAmount,
        ];
    }

    /**
     * Calculate monthly repayment amount using reducing balance method
     */
    public function calculateMonthlyRepayment(float $principal, float $annualInterestRate, int $termMonths): float
    {
        $monthlyRate = $annualInterestRate / 100 / 12;
        
        if ($monthlyRate === 0) {
            return $principal / $termMonths;
        }

        // Using the formula: M = P * [r(1+r)^n] / [(1+r)^n - 1]
        $numerator = $monthlyRate * pow(1 + $monthlyRate, $termMonths);
        $denominator = pow(1 + $monthlyRate, $termMonths) - 1;
        
        return $principal * ($numerator / $denominator);
    }

    /**
     * Generate repayment schedule
     */
    public function generateRepaymentSchedule(Loan $loan): array
    {
        $schedule = [];
        $balance = (float) $loan->amount;
        $monthlyPayment = (float) $loan->monthly_repayment;
        $monthlyInterestRate = ((float) $loan->interest_rate / 100) / 12;
        
        $currentDate = $loan->first_repayment_date ? 
            \Carbon\Carbon::parse($loan->first_repayment_date) : 
            now()->addMonth();

        for ($i = 1; $i <= $loan->term_months; $i++) {
            $interestPayment = $balance * $monthlyInterestRate;
            $principalPayment = $monthlyPayment - $interestPayment;
            $balance -= $principalPayment;

            $schedule[] = [
                'month' => $i,
                'due_date' => $currentDate->format('Y-m-d'),
                'principal' => round($principalPayment, 2),
                'interest' => round($interestPayment, 2),
                'payment' => round($monthlyPayment, 2),
                'balance' => round(max(0, $balance), 2),
            ];

            $currentDate->addMonth();
        }

        return $schedule;
    }

    /**
     * Calculate interest for a specific period
     */
    public function calculateInterest(float $principal, float $annualRate, int $days): float
    {
        return ($principal * $annualRate / 100) * ($days / 365);
    }

    /**
     * Check if loan is overdue
     */
    public function isLoanOverdue(Loan $loan): bool
    {
        if ($loan->status !== Loan::STATUS_ACTIVE) {
            return false;
        }

        // Get the latest repayment
        $lastRepayment = $loan->repayments()
            ->orderBy('payment_date', 'desc')
            ->first();

        $lastPaymentDate = $lastRepayment ? 
            $lastRepayment->payment_date : 
            $loan->first_repayment_date;

        if (!$lastPaymentDate) {
            return false;
        }

        // Check if any payment is overdue (more than 30 days late)
        $daysOverdue = now()->diffInDays($lastPaymentDate);
        
        return $daysOverdue > 30;
    }

    /**
     * Calculate penalty for overdue loan
     */
    public function calculatePenalty(Loan $loan): float
    {
        if ($loan->status !== Loan::STATUS_ACTIVE) {
            return 0;
        }

        $lastRepayment = $loan->repayments()
            ->orderBy('payment_date', 'desc')
            ->first();

        $lastPaymentDate = $lastRepayment ? 
            $lastRepayment->payment_date : 
            $loan->first_repayment_date;

        if (!$lastPaymentDate) {
            return 0;
        }

        $daysOverdue = now()->diffInDays($lastPaymentDate);
        
        if ($daysOverdue <= 30) {
            return 0; // No penalty for first 30 days
        }

        // Calculate penalty: 2% of outstanding balance per month overdue
        $monthsOverdue = ceil(($daysOverdue - 30) / 30);
        $penaltyRate = (float) config('sacco.penalty_rate', 2); // 2% per month
        $outstandingBalance = $this->calculateOutstandingBalance($loan);
        
        return ($outstandingBalance * $penaltyRate / 100) * $monthsOverdue;
    }

    /**
     * Mark loans as overdue
     */
    public function markOverdueLoans(): array
    {
        $overdueLoans = Loan::where('status', Loan::STATUS_ACTIVE)
            ->whereHas('repayments', function ($query) {
                $query->whereDate('payment_date', '<', now()->subDays(30));
            })
            ->orWhere(function ($query) {
                $query->where('status', Loan::STATUS_ACTIVE)
                    ->whereDate('first_repayment_date', '<', now()->subDays(30))
                    ->doesntHave('repayments');
            })
            ->get();

        $markedCount = 0;
        foreach ($overdueLoans as $loan) {
            if ($this->isLoanOverdue($loan)) {
                $loan->update(['status' => Loan::STATUS_OVERDUE]);
                $markedCount++;
            }
        }

        return [
            'marked_count' => $markedCount,
            'total_checked' => $overdueLoans->count(),
        ];
    }

    /**
     * Calculate early settlement amount
     */
    public function calculateEarlySettlement(Loan $loan): array
    {
        if ($loan->status !== Loan::STATUS_ACTIVE) {
            return [
                'eligible' => false,
                'reason' => 'Loan is not active',
            ];
        }

        $outstandingPrincipal = $loan->getRemainingBalance();
        $totalRepaid = $loan->getTotalRepaid();
        
        // Calculate remaining interest (reduced for early settlement)
        $remainingMonths = $loan->term_months - $loan->repayments()->count();
        $monthlyInterestRate = ((float) $loan->interest_rate / 100) / 12;
        
        // Apply discount for early settlement (e.g., 50% of remaining interest)
        $discountRate = (float) config('sacco.early_settlement_discount', 0.5);
        $remainingInterest = $outstandingPrincipal * $monthlyInterestRate * $remainingMonths;
        $discountedInterest = $remainingInterest * (1 - $discountRate);
        
        $settlementAmount = $outstandingPrincipal + $discountedInterest;
        $savings = $remainingInterest - $discountedInterest;

        return [
            'eligible' => true,
            'outstanding_principal' => $outstandingPrincipal,
            'remaining_interest' => $remainingInterest,
            'discounted_interest' => $discountedInterest,
            'settlement_amount' => $settlementAmount,
            'savings' => $savings,
            'discount_rate' => $discountRate * 100,
        ];
    }

    /**
     * Calculate outstanding balance with interest
     */
    public function calculateOutstandingBalance(Loan $loan): float
    {
        $totalRepaid = $loan->getTotalRepaid();
        $baseBalance = (float) $loan->amount - $totalRepaid;

        if ($baseBalance <= 0) {
            return 0;
        }

        // Add accrued interest if loan is active
        if ($loan->status === Loan::STATUS_ACTIVE) {
            $lastRepayment = $loan->repayments()
                ->orderBy('payment_date', 'desc')
                ->first();

            $lastPaymentDate = $lastRepayment ? 
                $lastRepayment->payment_date : 
                $loan->first_repayment_date;

            if ($lastPaymentDate) {
                $daysElapsed = now()->diffInDays($lastPaymentDate);
                $accruedInterest = $this->calculateInterest(
                    $baseBalance,
                    (float) $loan->interest_rate,
                    $daysElapsed
                );
                $baseBalance += $accruedInterest;
            }
        }

        return round($baseBalance, 2);
    }
}
