<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'type',
        'amount',
        'source',
        'reference',
        'transaction_date',
        'description',
        'salary_period',
        'employer_reference',
        'is_reversed',
        'reversed_by',
        'reversed_at',
        'status',
        'evidence_file',
        'receipt_file',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'dispute_reason',
        'dispute_description',
        'disputed_at',
        'disputed_by',
        'cancelled_at',
        'cancelled_by',
        'related_transaction_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'is_reversed' => 'boolean',
        'reversed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Transaction type constants
     */
    const TYPE_SALARY_SAVINGS = 'salary_savings';
    const TYPE_DIRECT_DEPOSIT = 'direct_deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_REVERSAL = 'reversal';

    /**
     * Transaction source constants
     */
    const SOURCE_SALARY = 'salary';
    const SOURCE_CASH = 'cash';
    const SOURCE_BANK_TRANSFER = 'bank_transfer';
    const SOURCE_MOBILE_MONEY = 'mobile_money';
    const SOURCE_CHECK = 'check';

    /**
     * Transaction status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_DISPUTED = 'disputed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Generate unique transaction reference
     */
    public static function generateReference(): string
    {
        return 'TXN' . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * Relationship: Transaction belongs to savings account
     */
    public function account()
    {
        return $this->belongsTo(SavingsAccount::class, 'account_id');
    }

    /**
     * Relationship: Transaction reversed by user
     */
    public function reversedByUser()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /**
     * Relationship: Transaction approved by user
     */
    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
