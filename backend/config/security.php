<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration options for the application.
    |
    */

    // Password requirements
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_special_chars' => false,
        'max_age_days' => 90, // Force password change after 90 days
    ],

    // Session security
    'session' => [
        'timeout_minutes' => 120, // 2 hours
        'concurrent_sessions' => 1, // Only one active session per user
    ],

    // Rate limiting
    'rate_limit' => [
        'login_attempts' => 5,
        'login_decay_minutes' => 1,
        'api_requests' => 60,
        'api_decay_minutes' => 1,
    ],

    // File upload security
    'uploads' => [
        'max_size' => 5120, // 5MB in KB
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/jpg',
            'application/pdf',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf', 'xls', 'xlsx'],
        'scan_for_viruses' => false, // Enable if you have antivirus scanner
    ],

    // IP whitelist (optional - leave empty to allow all)
    'ip_whitelist' => [
        // '127.0.0.1',
        // '192.168.1.0/24',
    ],

    // Security headers
    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ],

    // Audit logging
    'audit' => [
        'enabled' => true,
        'log_all_requests' => false,
        'log_sensitive_actions' => true,
        'sensitive_actions' => [
            'login',
            'logout',
            'password_change',
            'user_create',
            'user_update',
            'user_delete',
            'loan_approve',
            'loan_disburse',
            'member_approve',
        ],
    ],

    // Account lockout
    'lockout' => [
        'enabled' => true,
        'max_attempts' => 5,
        'lockout_duration_minutes' => 30,
    ],
];
