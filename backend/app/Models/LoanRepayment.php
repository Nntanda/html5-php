<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_id',
        'amount',
        'principal_amount',
        'interest_amount',
        'penalty_amount',
        'payment_date',
        'source',
        'reference',
        'recorded_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Repayment source constants
     */
    const SOURCE_MANUAL = 'manual';
    const SOURCE_SALARY_DEDUCTION = 'salary_deduction';
    const SOURCE_BANK_TRANSFER = 'bank_transfer';
    const SOURCE_MOBILE_MONEY = 'mobile_money';
    const SOURCE_CASH = 'cash';

    /**
     * Generate unique repayment reference
     */
    public static function generateReference(): string
    {
        return 'REP' . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * Relationship: Repayment belongs to loan
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Relationship: Repayment recorded by user
     */
    public function recordedByUser()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
