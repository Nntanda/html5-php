<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'upload_type',
        'file_name',
        'total_records',
        'successful_records',
        'failed_records',
        'total_amount_processed',
        'salary_period',
        'errors',
        'summary',
        'status',
        'error_message',
        'bank_receipt',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'errors' => 'array',
        'summary' => 'array',
        'total_amount_processed' => 'decimal:2',
    ];

    /**
     * Upload type constants
     */
    const TYPE_SALARY_DEDUCTIONS = 'salary_deductions';
    const TYPE_LOAN_REPAYMENTS = 'loan_repayments';

    /**
     * Upload status constants
     */
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_PARTIAL = 'partial';

    /**
     * Relationship: Upload log belongs to user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
