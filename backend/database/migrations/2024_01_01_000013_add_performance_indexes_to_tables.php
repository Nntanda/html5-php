<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to users table (skip email as it's already unique)
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'users_role_index')) {
                $table->index('role');
            }
            if (!$this->indexExists('users', 'users_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('users', 'users_role_status_index')) {
                $table->index(['role', 'status']);
            }
        });

        // Add indexes to members table
        Schema::table('members', function (Blueprint $table) {
            if (!$this->indexExists('members', 'members_member_number_index')) {
                $table->index('member_number');
            }
            if (!$this->indexExists('members', 'members_user_id_index')) {
                $table->index('user_id');
            }
            if (!$this->indexExists('members', 'members_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('members', 'members_status_created_at_index')) {
                $table->index(['status', 'created_at']);
            }
        });

        // Add indexes to savings_accounts table
        Schema::table('savings_accounts', function (Blueprint $table) {
            if (!$this->indexExists('savings_accounts', 'savings_accounts_member_id_index')) {
                $table->index('member_id');
            }
            if (!$this->indexExists('savings_accounts', 'savings_accounts_account_number_index')) {
                $table->index('account_number');
            }
            if (!$this->indexExists('savings_accounts', 'savings_accounts_member_id_created_at_index')) {
                $table->index(['member_id', 'created_at']);
            }
        });

        // Add indexes to savings_transactions table
        Schema::table('savings_transactions', function (Blueprint $table) {
            if (!$this->indexExists('savings_transactions', 'savings_transactions_account_id_index')) {
                $table->index('account_id');
            }
            if (!$this->indexExists('savings_transactions', 'savings_transactions_type_index')) {
                $table->index('type');
            }
            if (!$this->indexExists('savings_transactions', 'savings_transactions_transaction_date_index')) {
                $table->index('transaction_date');
            }
            if (!$this->indexExists('savings_transactions', 'savings_transactions_account_id_transaction_date_index')) {
                $table->index(['account_id', 'transaction_date']);
            }
            if (!$this->indexExists('savings_transactions', 'savings_transactions_type_transaction_date_index')) {
                $table->index(['type', 'transaction_date']);
            }
        });

        // Add indexes to loans table
        Schema::table('loans', function (Blueprint $table) {
            if (!$this->indexExists('loans', 'loans_member_id_index')) {
                $table->index('member_id');
            }
            if (!$this->indexExists('loans', 'loans_loan_number_index')) {
                $table->index('loan_number');
            }
            if (!$this->indexExists('loans', 'loans_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('loans', 'loans_application_date_index')) {
                $table->index('application_date');
            }
            if (!$this->indexExists('loans', 'loans_disbursement_date_index')) {
                $table->index('disbursement_date');
            }
            if (!$this->indexExists('loans', 'loans_status_member_id_index')) {
                $table->index(['status', 'member_id']);
            }
            if (!$this->indexExists('loans', 'loans_status_disbursement_date_index')) {
                $table->index(['status', 'disbursement_date']);
            }
        });

        // Add indexes to loan_guarantors table
        Schema::table('loan_guarantors', function (Blueprint $table) {
            if (!$this->indexExists('loan_guarantors', 'loan_guarantors_loan_id_index')) {
                $table->index('loan_id');
            }
            if (!$this->indexExists('loan_guarantors', 'loan_guarantors_guarantor_member_id_index')) {
                $table->index('guarantor_member_id');
            }
            if (!$this->indexExists('loan_guarantors', 'loan_guarantors_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('loan_guarantors', 'loan_guarantors_loan_id_status_index')) {
                $table->index(['loan_id', 'status']);
            }
        });

        // Add indexes to loan_repayments table
        Schema::table('loan_repayments', function (Blueprint $table) {
            if (!$this->indexExists('loan_repayments', 'loan_repayments_loan_id_index')) {
                $table->index('loan_id');
            }
            if (!$this->indexExists('loan_repayments', 'loan_repayments_payment_date_index')) {
                $table->index('payment_date');
            }
            if (!$this->indexExists('loan_repayments', 'loan_repayments_loan_id_payment_date_index')) {
                $table->index(['loan_id', 'payment_date']);
            }
        });

        // Add indexes to notifications table
        Schema::table('notifications', function (Blueprint $table) {
            if (!$this->indexExists('notifications', 'notifications_user_id_index')) {
                $table->index('user_id');
            }
            if (!$this->indexExists('notifications', 'notifications_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('notifications', 'notifications_sent_at_index')) {
                $table->index('sent_at');
            }
            if (!$this->indexExists('notifications', 'notifications_user_id_status_index')) {
                $table->index(['user_id', 'status']);
            }
            if (!$this->indexExists('notifications', 'notifications_user_id_sent_at_index')) {
                $table->index(['user_id', 'sent_at']);
            }
        });

        // Add indexes to audit_logs table
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!$this->indexExists('audit_logs', 'audit_logs_user_id_index')) {
                $table->index('user_id');
            }
            if (!$this->indexExists('audit_logs', 'audit_logs_action_index')) {
                $table->index('action');
            }
            if (!$this->indexExists('audit_logs', 'audit_logs_entity_type_index')) {
                $table->index('entity_type');
            }
            if (!$this->indexExists('audit_logs', 'audit_logs_created_at_index')) {
                $table->index('created_at');
            }
            if (!$this->indexExists('audit_logs', 'audit_logs_user_id_created_at_index')) {
                $table->index(['user_id', 'created_at']);
            }
            if (!$this->indexExists('audit_logs', 'audit_logs_entity_type_entity_id_index')) {
                $table->index(['entity_type', 'entity_id']);
            }
        });

        // Add indexes to upload_logs table
        Schema::table('upload_logs', function (Blueprint $table) {
            if (!$this->indexExists('upload_logs', 'upload_logs_type_index')) {
                $table->index('type');
            }
            if (!$this->indexExists('upload_logs', 'upload_logs_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('upload_logs', 'upload_logs_uploaded_by_index')) {
                $table->index('uploaded_by');
            }
            if (!$this->indexExists('upload_logs', 'upload_logs_created_at_index')) {
                $table->index('created_at');
            }
            if (!$this->indexExists('upload_logs', 'upload_logs_type_status_index')) {
                $table->index(['type', 'status']);
            }
        });

        // Add indexes to backups table
        Schema::table('backups', function (Blueprint $table) {
            if (!$this->indexExists('backups', 'backups_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('backups', 'backups_created_at_index')) {
                $table->index('created_at');
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
        $doctrineTable = $doctrineSchemaManager->listTableDetails($table);
        
        return $doctrineTable->hasIndex($index);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from users table (skip email as it's part of unique constraint)
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropIndex(['role', 'status']);
        });

        // Drop indexes from members table
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['member_number']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['status', 'created_at']);
        });

        // Drop indexes from savings_accounts table
        Schema::table('savings_accounts', function (Blueprint $table) {
            $table->dropIndex(['member_id']);
            $table->dropIndex(['account_number']);
            $table->dropIndex(['member_id', 'created_at']);
        });

        // Drop indexes from savings_transactions table
        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
            $table->dropIndex(['type']);
            $table->dropIndex(['transaction_date']);
            $table->dropIndex(['account_id', 'transaction_date']);
            $table->dropIndex(['type', 'transaction_date']);
        });

        // Drop indexes from loans table
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['member_id']);
            $table->dropIndex(['loan_number']);
            $table->dropIndex(['status']);
            $table->dropIndex(['application_date']);
            $table->dropIndex(['disbursement_date']);
            $table->dropIndex(['status', 'member_id']);
            $table->dropIndex(['status', 'disbursement_date']);
        });

        // Drop indexes from loan_guarantors table
        Schema::table('loan_guarantors', function (Blueprint $table) {
            $table->dropIndex(['loan_id']);
            $table->dropIndex(['guarantor_member_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['loan_id', 'status']);
        });

        // Drop indexes from loan_repayments table
        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->dropIndex(['loan_id']);
            $table->dropIndex(['payment_date']);
            $table->dropIndex(['loan_id', 'payment_date']);
        });

        // Drop indexes from notifications table
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['sent_at']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['user_id', 'sent_at']);
        });

        // Drop indexes from audit_logs table
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['action']);
            $table->dropIndex(['entity_type']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['entity_type', 'entity_id']);
        });

        // Drop indexes from upload_logs table
        Schema::table('upload_logs', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['uploaded_by']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['type', 'status']);
        });

        // Drop indexes from backups table
        Schema::table('backups', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });
    }
};
