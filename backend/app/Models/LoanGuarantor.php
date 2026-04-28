<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanGuarantor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_id',
        'guarantor_member_id',
        'guaranteed_amount',
        'status',
        'approval_date',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'guaranteed_amount' => 'decimal:2',
        'approval_date' => 'date',
    ];

    /**
     * Guarantor status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * Relationship: Guarantor belongs to loan
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Relationship: Guarantor is a member
     */
    public function guarantor()
    {
        return $this->belongsTo(Member::class, 'guarantor_member_id');
    }
}
