<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id',
        'loan_number',
        'amount',
        'interest_rate',
        'term_months',
        'purpose',
        'status',
        'disbursement_method',
        'application_date',
        'approval_date',
        'disbursement_date',
        'first_repayment_date',
        'outstanding_balance',
        'monthly_repayment',
        'approved_by',
        'disbursed_by',
        'approval_comment',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'monthly_repayment' => 'decimal:2',
        'application_date' => 'date',
        'approval_date' => 'date',
        'disbursement_date' => 'date',
        'first_repayment_date' => 'date',
    ];

    /**
     * Loan status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_GUARANTORS_APPROVED = 'guarantors_approved';
    const STATUS_APPROVED_PENDING_DISBURSEMENT = 'approved_pending_disbursement';
    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_OVERDUE = 'overdue';

    /**
     * Disbursement method constants
     */
    const DISBURSEMENT_BANK_TRANSFER = 'bank_transfer';
    const DISBURSEMENT_MOBILE_MONEY = 'mobile_money';
    const DISBURSEMENT_CASH = 'cash';
    const DISBURSEMENT_CHEQUE = 'cheque';

    /**
     * Generate unique loan number
     */
    public static function generateLoanNumber(): string
    {
        $year = date('Y');
        $lastLoan = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastLoan) {
            $lastNumber = (int) substr($lastLoan->loan_number, -5);
            $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '00001';
        }

        return "LN{$year}{$newNumber}";
    }

    /**
     * Relationship: Loan belongs to member
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Relationship: Loan has many guarantors
     */
    public function guarantors()
    {
        return $this->hasMany(LoanGuarantor::class);
    }

    /**
     * Relationship: Loan has many repayments
     */
    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class);
    }

    /**
     * Relationship: Loan approved by user
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relationship: Loan disbursed by user
     */
    public function disbursedBy()
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    /**
     * Check if all guarantors have approved
     */
    public function allGuarantorsApproved(): bool
    {
        $totalGuarantors = $this->guarantors()->count();
        $approvedGuarantors = $this->guarantors()
            ->where('status', LoanGuarantor::STATUS_ACCEPTED)
            ->count();

        return $totalGuarantors > 0 && $totalGuarantors === $approvedGuarantors;
    }

    /**
     * Get total guaranteed amount
     */
    public function getTotalGuaranteedAmount(): float
    {
        return (float) $this->guarantors()
            ->where('status', LoanGuarantor::STATUS_ACCEPTED)
            ->sum('guaranteed_amount');
    }

    /**
     * Get total repaid amount
     */
    public function getTotalRepaid(): float
    {
        return (float) $this->repayments()->sum('amount');
    }

    /**
     * Calculate remaining balance
     */
    public function getRemainingBalance(): float
    {
        return max(0, (float) $this->amount - $this->getTotalRepaid());
    }
}
