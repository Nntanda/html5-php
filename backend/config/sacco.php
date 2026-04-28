<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SACCO Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration values for the SACCO management system
    |
    */

    // Loan Configuration
    'max_loan_multiplier' => env('SACCO_MAX_LOAN_MULTIPLIER', 3), // 3x savings balance
    'min_savings_period_months' => env('SACCO_MIN_SAVINGS_PERIOD_MONTHS', 6), // 6 months minimum
    'interest_rate' => env('SACCO_INTEREST_RATE', 15), // 15% per annum
    'penalty_rate' => env('SACCO_PENALTY_RATE', 2), // 2% per month for overdue loans
    'early_settlement_discount' => env('SACCO_EARLY_SETTLEMENT_DISCOUNT', 0.5), // 50% discount on remaining interest

    // Guarantor Configuration
    'max_guarantee_multiplier' => env('SACCO_MAX_GUARANTEE_MULTIPLIER', 2), // 2x savings balance
    'min_guarantors_required' => env('SACCO_MIN_GUARANTORS_REQUIRED', 2), // Minimum 2 guarantors

    // System Configuration
    'currency' => env('SACCO_CURRENCY', 'UGX'),
    'currency_symbol' => env('SACCO_CURRENCY_SYMBOL', 'UGX'),
    'locale' => env('SACCO_LOCALE', 'en-UG'),

    // Notification Configuration
    'send_email_notifications' => env('SACCO_SEND_EMAIL_NOTIFICATIONS', true),
    'send_sms_notifications' => env('SACCO_SEND_SMS_NOTIFICATIONS', false),

    // File Upload Configuration
    'max_file_size' => env('SACCO_MAX_FILE_SIZE', 5120), // 5MB in KB
    'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],

    // Backup Configuration
    'backup_retention_days' => env('SACCO_BACKUP_RETENTION_DAYS', 30),
    'max_backups_to_keep' => env('SACCO_MAX_BACKUPS_TO_KEEP', 10),
];