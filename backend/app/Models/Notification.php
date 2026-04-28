<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'channel',
        'subject',
        'message',
        'status',
        'sent_at',
        'is_read',
        'read_at',
        'retry_count',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Notification type constants
     */
    const TYPE_LOAN_APPLICATION_SUBMITTED = 'loan_application_submitted';
    const TYPE_GUARANTOR_REQUEST = 'guarantor_request';
    const TYPE_LOAN_APPROVED = 'loan_approved';
    const TYPE_LOAN_REJECTED = 'loan_rejected';
    const TYPE_LOAN_DISBURSED = 'loan_disbursed';
    const TYPE_REPAYMENT_RECEIVED = 'repayment_received';
    const TYPE_PAYMENT_OVERDUE = 'payment_overdue';
    const TYPE_PAYMENT_REMINDER = 'payment_reminder';

    /**
     * Notification channel constants
     */
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_IN_APP = 'in_app';

    /**
     * Notification status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    /**
     * Relationship: Notification belongs to user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed(string $errorMessage = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }
}
