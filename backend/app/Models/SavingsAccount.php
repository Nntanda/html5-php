<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id',
        'account_number',
        'balance',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    /**
     * Account status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CLOSED = 'closed';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Generate unique account number
     */
    public static function generateAccountNumber(): string
    {
        $year = date('Y');
        $lastAccount = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastAccount) {
            $lastNumber = (int) substr($lastAccount->account_number, -6);
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return "SAV{$year}{$newNumber}";
    }

    /**
     * Relationship: Savings account belongs to member
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Relationship: Savings account has many transactions
     */
    public function transactions()
    {
        return $this->hasMany(SavingsTransaction::class, 'account_id');
    }

    /**
     * Relationship: Account approved by user
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
