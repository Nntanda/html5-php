<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'member_number',
        'first_name',
        'last_name',
        'national_id',
        'passport_photo',
        'phone',
        'email',
        'address',
        'employment_info',
        'status',
        'category',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'employment_info' => 'array',
    ];

    /**
     * Member status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Member category constants
     */
    const CATEGORY_STAFF = 'staff';
    const CATEGORY_ACT_PROGRAM = 'act_program';
    const CATEGORY_NURSING_SCHOOL = 'nursing_school';
    const CATEGORY_HC_STAFF = 'hc_staff';
    const CATEGORY_NON_HOSPITAL_STAFF = 'non_hospital_staff';

    /**
     * Check if member is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Generate unique member number
     */
    public static function generateMemberNumber(): string
    {
        $year = date('Y');
        $lastMember = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastMember) {
            $lastNumber = (int) substr($lastMember->member_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "MEM{$year}{$newNumber}";
    }

    /**
     * Relationship: Member belongs to user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Member has one savings account
     */
    public function savingsAccount()
    {
        return $this->hasOne(SavingsAccount::class);
    }

    /**
     * Relationship: Member has many loans
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Relationship: Member has many guarantor records
     */
    public function guarantorRecords()
    {
        return $this->hasMany(LoanGuarantor::class, 'guarantor_member_id');
    }

    /**
     * Relationship: Member has many repayments (as recorded by)
     */
    public function recordedRepayments()
    {
        return $this->hasMany(LoanRepayment::class, 'recorded_by');
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
