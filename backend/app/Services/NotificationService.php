<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    /**
     * Send notification to user
     */
    public function sendNotification(
        User $user,
        string $type,
        string $subject,
        string $message,
        string $channel = Notification::CHANNEL_IN_APP
    ): Notification {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'channel' => $channel,
            'subject' => $subject,
            'message' => $message,
            'status' => Notification::STATUS_PENDING,
        ]);

        // Send based on channel
        if ($channel === Notification::CHANNEL_EMAIL) {
            $this->sendEmail($notification);
        } elseif ($channel === Notification::CHANNEL_SMS) {
            $this->sendSMS($notification);
        }

        return $notification;
    }

    /**
     * Send email notification
     */
    private function sendEmail(Notification $notification): void
    {
        try {
            // In production, implement actual email sending
            // For now, just mark as sent
            $notification->markAsSent();
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
        }
    }

    /**
     * Send SMS notification
     */
    private function sendSMS(Notification $notification): void
    {
        try {
            $user = $notification->user;
            
            if (!$user->member || !$user->member->phone) {
                throw new \Exception('User phone number not found');
            }

            // In production, integrate with Twilio or Africa's Talking
            // For now, just mark as sent
            $notification->markAsSent();
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
        }
    }

    /**
     * Send loan application submitted notification
     */
    public function notifyLoanApplicationSubmitted(User $user, string $loanNumber): void
    {
        $this->sendNotification(
            $user,
            Notification::TYPE_LOAN_APPLICATION_SUBMITTED,
            'Loan Application Submitted',
            "Your loan application {$loanNumber} has been submitted successfully. Please wait for guarantor approvals.",
            Notification::CHANNEL_IN_APP
        );
    }

    /**
     * Send guarantor request notification
     */
    public function notifyGuarantorRequest(User $guarantor, string $loanNumber, string $memberName): void
    {
        $this->sendNotification(
            $guarantor,
            Notification::TYPE_GUARANTOR_REQUEST,
            'Guarantor Request',
            "{$memberName} has requested you to be a guarantor for loan {$loanNumber}. Please review and approve or reject.",
            Notification::CHANNEL_IN_APP
        );
    }

    /**
     * Send loan approved notification
     */
    public function notifyLoanApproved(User $user, string $loanNumber): void
    {
        $this->sendNotification(
            $user,
            Notification::TYPE_LOAN_APPROVED,
            'Loan Approved',
            "Your loan application {$loanNumber} has been approved. It will be disbursed shortly.",
            Notification::CHANNEL_IN_APP
        );
    }

    /**
     * Send loan rejected notification
     */
    public function notifyLoanRejected(User $user, string $loanNumber, string $reason = null): void
    {
        $message = "Your loan application {$loanNumber} has been rejected.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }

        $this->sendNotification(
            $user,
            Notification::TYPE_LOAN_REJECTED,
            'Loan Rejected',
            $message,
            Notification::CHANNEL_IN_APP
        );
    }

    /**
     * Send loan disbursed notification
     */
    public function notifyLoanDisbursed(User $user, string $loanNumber, float $amount): void
    {
        $this->sendNotification(
            $user,
            Notification::TYPE_LOAN_DISBURSED,
            'Loan Disbursed',
            "Your loan {$loanNumber} for amount " . number_format($amount, 2) . " has been disbursed.",
            Notification::CHANNEL_IN_APP
        );
    }

    /**
     * Send repayment received notification
     */
    public function notifyRepaymentReceived(User $user, string $loanNumber, float $amount): void
    {
        $this->sendNotification(
            $user,
            Notification::TYPE_REPAYMENT_RECEIVED,
            'Repayment Received',
            "Your repayment of " . number_format($amount, 2) . " for loan {$loanNumber} has been received.",
            Notification::CHANNEL_IN_APP
        );
    }

    /**
     * Send payment overdue notification
     */
    public function notifyPaymentOverdue(User $user, string $loanNumber, int $daysOverdue): void
    {
        $this->sendNotification(
            $user,
            Notification::TYPE_PAYMENT_OVERDUE,
            'Payment Overdue',
            "Your loan {$loanNumber} payment is {$daysOverdue} days overdue. Please make payment immediately.",
            Notification::CHANNEL_IN_APP
        );
    }

    /**
     * Send payment reminder notification
     */
    public function notifyPaymentReminder(User $user, string $loanNumber, float $amount): void
    {
        $this->sendNotification(
            $user,
            Notification::TYPE_PAYMENT_REMINDER,
            'Payment Reminder',
            "Reminder: Your loan {$loanNumber} payment of " . number_format($amount, 2) . " is due soon.",
            Notification::CHANNEL_IN_APP
        );
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(User $user, int $limit = 20, int $offset = 0)
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()
            ->where('is_read', false)
            ->count();
    }
}
