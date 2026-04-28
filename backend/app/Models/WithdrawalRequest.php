<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'account_id',
        'amount',
        'payment_method',
        'reason',
        'status',
        'processed_by',
        'processed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Relationship: Request belongs to member
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Relationship: Request belongs to savings account
     */
    public function account()
    {
        return $this->belongsTo(SavingsAccount::class, 'account_id');
    }

    /**
     * Relationship: Request processed by user
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
