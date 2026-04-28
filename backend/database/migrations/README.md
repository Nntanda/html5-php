# Database Migrations

This directory contains all Laravel database migrations for the SACCO Management System.

## Migration Files

1. **2024_01_01_000001_create_users_table.php**
   - Creates the users table for authentication
   - Fields: id, name, email, password, role, status, timestamps
   - Roles: super_admin, loan_officer, accountant, member
   - Status: active, suspended, inactive

2. **2024_01_01_000002_create_members_table.php**
   - Creates the members table for SACCO member profiles
   - Fields: id, user_id, member_number, first_name, last_name, national_id, phone, email, address, employment_info, status, timestamps
   - Links to users table via user_id foreign key

3. **2024_01_01_000003_create_savings_accounts_table.php**
   - Creates the savings_accounts table
   - Fields: id, member_id, account_number, balance, timestamps
   - Links to members table via member_id foreign key

4. **2024_01_01_000004_create_savings_transactions_table.php**
   - Creates the savings_transactions table for all savings activities
   - Fields: id, account_id, type, amount, source, reference, transaction_date, description, salary_period, employer_reference, is_reversed, reversed_by, reversed_at, timestamps
   - Types: salary_savings, direct_deposit, withdrawal, reversal
   - Sources: salary, cash, bank_transfer, mobile_money

5. **2024_01_01_000005_create_loans_table.php**
   - Creates the loans table for loan applications and tracking
   - Fields: id, member_id, loan_number, amount, interest_rate, term_months, purpose, status, disbursement_method, application_date, approval_date, disbursement_date, first_repayment_date, outstanding_balance, monthly_repayment, approved_by, disbursed_by, approval_comment, rejection_reason, timestamps
   - Status: pending, guarantors_approved, approved_pending_disbursement, active, closed, rejected, overdue

6. **2024_01_01_000006_create_loan_guarantors_table.php**
   - Creates the loan_guarantors table for guarantor workflow
   - Fields: id, loan_id, guarantor_member_id, guaranteed_amount, status, approval_date, rejection_reason, timestamps
   - Status: pending, accepted, rejected, withdrawn

7. **2024_01_01_000007_create_loan_repayments_table.php**
   - Creates the loan_repayments table for tracking loan payments
   - Fields: id, loan_id, amount, principal_amount, interest_amount, penalty_amount, payment_date, source, reference, recorded_by, notes, timestamps
   - Sources: manual, salary_deduction, bank_transfer, mobile_money, cash

8. **2024_01_01_000008_create_notifications_table.php**
   - Creates the notifications table for system notifications
   - Fields: id, user_id, type, channel, subject, message, status, sent_at, is_read, read_at, retry_count, error_message, timestamps
   - Types: loan_application_submitted, guarantor_request, loan_approved, loan_rejected, loan_disbursed, repayment_received, payment_overdue, payment_reminder
   - Channels: email, sms, in_app

9. **2024_01_01_000009_create_audit_logs_table.php**
   - Creates the audit_logs table for tracking all system actions
   - Fields: id, user_id, action, entity_type, entity_id, changes, ip_address, user_agent, timestamps

10. **2024_01_01_000010_create_system_config_table.php**
    - Creates the system_config table for system-wide configuration
    - Fields: id, key, value, description, type, updated_at
    - Includes default configuration values for interest rates, loan limits, etc.

## Running Migrations

Once Laravel is installed, run the migrations using:

```bash
cd backend
php artisan migrate
```

## Rolling Back Migrations

To rollback the last batch of migrations:

```bash
php artisan migrate:rollback
```

To rollback all migrations:

```bash
php artisan migrate:reset
```

## Fresh Migration

To drop all tables and re-run all migrations:

```bash
php artisan migrate:fresh
```

## Database Schema Diagram

```
users (1) ----< (1) members
                    |
                    +----< (1) savings_accounts ----< (*) savings_transactions
                    |
                    +----< (*) loans ----< (*) loan_guarantors
                                    |
                                    +----< (*) loan_repayments
users (1) ----< (*) notifications
users (1) ----< (*) audit_logs
system_config (standalone)
```

## Indexes

All tables include appropriate indexes for:
- Primary keys (id)
- Foreign keys
- Frequently queried fields (status, dates, reference numbers)
- Unique constraints (email, member_number, account_number, loan_number, etc.)

## Notes

- All monetary values use `decimal(15, 2)` for precision
- All foreign keys include `onDelete` constraints for referential integrity
- Timestamps are automatically managed by Laravel
- The system_config table is pre-populated with default values
