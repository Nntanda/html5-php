<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\SavingsTransactionController;
use App\Http\Controllers\WithdrawalRequestController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\GuarantorController;
use App\Http\Controllers\RepaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BackupController;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no throttling for now to test)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Member self-registration (public)
Route::post('/members/register', [MemberController::class, 'register']);

// Member search for referee selection (public)
Route::get('/members/search', [MemberController::class, 'searchForReferee']);

// Protected routes
Route::middleware(['auth:sanctum', 'check.status'])->group(function () {
    
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/me', [AuthController::class, 'me']); // Shortcut for /auth/me
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/change-password', [AuthController::class, 'changePassword']); // Shortcut for /auth/change-password
    
    // Current member routes (for logged-in member)
    Route::get('/member/profile', [MemberController::class, 'currentMemberProfile']);
    Route::get('/member/summary', [MemberController::class, 'currentMemberSummary']);
    Route::put('/member/profile', [MemberController::class, 'updateCurrentMember']);
    Route::get('/member/savings', [SavingsTransactionController::class, 'currentMemberSavings']);
    
    // Enhanced transaction routes for members
    Route::get('/member/transactions', [SavingsTransactionController::class, 'memberTransactions']);
    Route::get('/member/transactions/analytics', [SavingsTransactionController::class, 'memberTransactionAnalytics']);
    Route::get('/member/transactions/export', [SavingsTransactionController::class, 'exportMemberTransactions']);
    Route::get('/member/transactions/{id}', [SavingsTransactionController::class, 'transactionDetails']);
    Route::get('/member/transactions/{id}/receipt', [SavingsTransactionController::class, 'transactionReceipt']);
    Route::post('/member/transactions/{id}/dispute', [SavingsTransactionController::class, 'disputeTransaction']);
    Route::delete('/member/transactions/{id}/cancel', [SavingsTransactionController::class, 'cancelPendingTransaction']);

    // User management routes (Super Admin only)
    Route::middleware(['check.role:' . User::ROLE_SUPER_ADMIN])->group(function () {
        Route::apiResource('users', UserController::class);
    });

    // Member management routes
    Route::apiResource('members', MemberController::class);
    Route::post('/members/batch-lookup', [MemberController::class, 'batchLookup']);
    Route::get('/members/{id}/summary', [MemberController::class, 'summary']);
    Route::get('/members/{id}/loan-eligibility', [MemberController::class, 'loanEligibility']);
    Route::get('/members/{id}/potential-guarantors', [MemberController::class, 'potentialGuarantors']);
    Route::get('/members/{id}/guarantor-requests', [GuarantorController::class, 'pendingRequests']);
    Route::put('/members/{id}/approve', [MemberController::class, 'approve']);
    Route::post('/members/{id}/reject', [MemberController::class, 'reject']);

    // Savings management routes
Route::get('/savings/stats', [\App\Http\Controllers\SavingsAccountController::class, 'stats']);
    Route::get('/savings/accounts', [\App\Http\Controllers\SavingsAccountController::class, 'index']);
    Route::post('/savings/accounts', [\App\Http\Controllers\SavingsAccountController::class, 'store']);
    Route::post('/savings/accounts/admin-create', [\App\Http\Controllers\SavingsAccountController::class, 'adminCreate']);
    Route::post('/savings/accounts/{id}/approve', [\App\Http\Controllers\SavingsAccountController::class, 'approve']);
    Route::post('/savings/accounts/{id}/reject', [\App\Http\Controllers\SavingsAccountController::class, 'reject']);
    Route::get('/savings/salary-deduction-report/excel', [SavingsTransactionController::class, 'salaryDeductionReportExcel']);
    Route::get('/savings/salary-deduction-report/pdf', [SavingsTransactionController::class, 'salaryDeductionReportPDF']);
    Route::get('/savings/transactions', [SavingsTransactionController::class, 'index']);
    Route::post('/savings/upload-deductions', [SavingsTransactionController::class, 'uploadDeductions']);
    Route::get('/savings/upload-history', [SavingsTransactionController::class, 'uploadHistory']);
    Route::get('/savings/download-receipt/{path}', [SavingsTransactionController::class, 'downloadReceipt'])->where('path', '.*');
    Route::post('/savings/deposit', [SavingsTransactionController::class, 'deposit']);
    Route::post('/savings/withdraw', [SavingsTransactionController::class, 'withdraw']);
    Route::post('/savings/transactions/{id}/reverse', [SavingsTransactionController::class, 'reverse']);
    Route::get('/savings/transactions/pending', [SavingsTransactionController::class, 'pendingTransactions']);
    Route::post('/savings/transactions/{id}/approve', [SavingsTransactionController::class, 'approveTransaction']);
    Route::post('/savings/transactions/{id}/reject', [SavingsTransactionController::class, 'rejectTransaction']);

    // Withdrawal request routes
    Route::get('/withdrawal-requests', [WithdrawalRequestController::class, 'index']);
    Route::post('/withdrawal-requests', [WithdrawalRequestController::class, 'store']);
    Route::post('/withdrawal-requests/{id}/approve', [WithdrawalRequestController::class, 'approve']);
    Route::post('/withdrawal-requests/{id}/reject', [WithdrawalRequestController::class, 'reject']);

    // Loan management routes
    Route::post('/loans/apply', [LoanController::class, 'apply']);
    Route::post('/loans/calculate', [LoanController::class, 'calculate']);
    Route::post('/loans/bulk-approve', [LoanController::class, 'bulkApprove']);
    Route::post('/loans/bulk-reject', [LoanController::class, 'bulkReject']);
    Route::post('/loans/mark-overdue', [LoanController::class, 'markOverdue']);
    Route::get('/loans', [LoanController::class, 'index']);
    Route::get('/loans/{id}', [LoanController::class, 'show']);
    Route::get('/loans/{id}/eligibility', [LoanController::class, 'eligibility']);
    Route::get('/loans/{id}/repayment-schedule', [LoanController::class, 'repaymentSchedule']);
    Route::get('/loans/{id}/early-settlement', [LoanController::class, 'earlySettlement']);
    Route::get('/loans/{id}/penalty', [LoanController::class, 'penalty']);
    Route::post('/loans/{id}/early-settlement', [LoanController::class, 'processEarlySettlement']);
    Route::put('/loans/{id}', [LoanController::class, 'update']);
    Route::put('/loans/{id}/approve', [LoanController::class, 'approve']);
    Route::put('/loans/{id}/reject', [LoanController::class, 'reject']);
    Route::put('/loans/{id}/disburse', [LoanController::class, 'disburse']);

    // Guarantor management routes
    Route::post('/loans/{loanId}/guarantors', [GuarantorController::class, 'store']);
    Route::get('/loans/{loanId}/guarantors', [GuarantorController::class, 'index']);
    Route::put('/loans/{loanId}/guarantors/{guarantorId}', [GuarantorController::class, 'update']);
    Route::get('/members/{id}/guarantor-requests', [GuarantorController::class, 'pendingRequests']);

    // Repayment management routes
    Route::post('/loans/{loanId}/repayments', [RepaymentController::class, 'store']);
    Route::get('/loans/{loanId}/repayments', [RepaymentController::class, 'index']);
    Route::post('/loans/repayments/upload-deductions', [RepaymentController::class, 'uploadDeductions']);
    Route::get('/loans/{loanId}/tracking', [RepaymentController::class, 'trackingInfo']);

    // Report routes
    Route::get('/reports/member/{id}/statement', [ReportController::class, 'memberStatement']);
    Route::get('/reports/member/{id}/loan-summary', [ReportController::class, 'memberLoanSummary']);
    Route::get('/reports/savings-summary', [ReportController::class, 'savingsSummary']);
    Route::get('/reports/loans-summary', [ReportController::class, 'loansSummary']);
    Route::get('/reports/transactions', [ReportController::class, 'transactions']);
    Route::get('/reports/overdue-loans', [ReportController::class, 'overdueLoans']);

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::post('/notifications/send', [NotificationController::class, 'send']);

    // System configuration routes (Super Admin only)
    Route::middleware(['check.role:' . User::ROLE_SUPER_ADMIN])->group(function () {
        Route::get('/config', [ConfigController::class, 'index']);
        Route::put('/config', [ConfigController::class, 'update']);
        Route::get('/config/{key}', [ConfigController::class, 'show']);
        Route::put('/config/{key}', [ConfigController::class, 'updateOne']);
    });

    // Audit log routes (Super Admin only)
    Route::middleware(['check.role:' . User::ROLE_SUPER_ADMIN])->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/audit-logs/{id}', [AuditLogController::class, 'show']);
        Route::get('/audit-logs/entity/{entityType}/{entityId}', [AuditLogController::class, 'entityLogs']);
        Route::get('/audit-logs/user/{userId}', [AuditLogController::class, 'userLogs']);
        Route::get('/audit-logs/export', [AuditLogController::class, 'export']);
    });

    // Backup routes (Super Admin only)
    Route::middleware(['check.role:' . User::ROLE_SUPER_ADMIN])->group(function () {
        Route::post('/backups/create', [BackupController::class, 'create']);
        Route::get('/backups', [BackupController::class, 'index']);
        Route::get('/backups/stats', [BackupController::class, 'stats']);
        Route::get('/backups/{id}', [BackupController::class, 'show']);
        Route::post('/backups/{id}/restore', [BackupController::class, 'restore']);
        Route::delete('/backups/{id}', [BackupController::class, 'destroy']);
    });
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Test CORS endpoint
Route::get('/test-cors', function () {
    return response()->json([
        'message' => 'CORS test successful',
        'origin' => request()->header('Origin'),
        'method' => request()->method(),
    ]);
});

Route::post('/test-cors', function () {
    return response()->json([
        'message' => 'CORS POST test successful',
        'data' => request()->all(),
    ]);
});
