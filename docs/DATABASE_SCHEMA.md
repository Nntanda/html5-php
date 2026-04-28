# SACCO Management System - Database Schema

## Overview

This document describes the database schema for the SACCO Management System. The system uses MySQL or PostgreSQL as the database engine.

## Entity Relationship Diagram

```
users (1) ----< (1) members
members (1) ----< (*) savings_accounts
savings_accounts (1) ----< (*) savings_transactions
members (1) ----< (*) loans
loans (1) ----< (*) loan_guarantors
loans (1) ----< (*) loan_repayments
members (1) ----< (*) loan_guarantors (as guarantor)
users (1) ----< (*) notifications
users (1) ----< (*) audit_logs
```

## Tables

### users

Stores system users (staff and members).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| name | VARCHAR(255) | NOT NULL | Full name |
| email | VARCHAR(255) | NOT NULL, UNIQUE | Email address |
| password | VARCHAR(255) | NOT NULL | Hashed password |
| role | ENUM | NOT NULL | User role: SuperAdmin, LoanOfficer, Accountant, Member |
| status | ENUM | NOT NULL, DEFAULT 'active' | Account status: active, inactive, suspended |
| email_verified_at | TIMESTAMP | NULLABLE | Email verification timestamp |
| remember_token | VARCHAR(100) | NULLABLE | Remember me token |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX (email)
- INDEX (role)
- INDEX (status)

### members

Stores SACCO member information.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| user_id | BIGINT UNSIGNED | NOT NULL, UNIQUE, FOREIGN KEY | Reference to users table |
| member_number | VARCHAR(50) | NOT NULL, UNIQUE | Unique member number |
| first_name | VARCHAR(100) | NOT NULL | First name |
| last_name | VARCHAR(100) | NOT NULL | Last name |
| phone | VARCHAR(20) | NOT NULL | Phone number |
| address | TEXT | NULLABLE | Physical address |
| employment_info | JSON | NULLABLE | Employment details (employer, employee_id, department) |
| status | ENUM | NOT NULL, DEFAULT 'active' | Member status: active, inactive, suspended |
| created_at | TIMESTAMP | NOT NULL | Registration timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX (user_id)
- UNIQUE INDEX (member_number)
- INDEX (status)
- INDEX (phone)

### savings_accounts

Stores member savings accounts.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| member_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY | Reference to members table |
| account_number | VARCHAR(50) | NOT NULL, UNIQUE | Unique account number |
| balance | DECIMAL(15,2) | NOT NULL, DEFAULT 0.00 | Current balance |
| created_at | TIMESTAMP | NOT NULL | Account creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX (account_number)
- INDEX (member_id)

### savings_transactions

Stores all savings transactions (deposits and withdrawals).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| account_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY | Reference to savings_accounts table |
| type | ENUM | NOT NULL | Transaction type: deposit, withdrawal |
| amount | DECIMAL(15,2) | NOT NULL | Transaction amount |
| source | VARCHAR(50) | NOT NULL | Source: cash, bank_transfer, salary_deduction |
| reference | VARCHAR(100) | NULLABLE | Transaction reference number |
| transaction_date | DATE | NOT NULL | Date of transaction |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (account_id)
- INDEX (transaction_date)
- INDEX (type)
- INDEX (reference)

### loans

Stores loan applications and details.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| member_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY | Reference to members table |
| loan_number | VARCHAR(50) | NOT NULL, UNIQUE | Unique loan number |
| amount | DECIMAL(15,2) | NOT NULL | Loan amount |
| interest_rate | DECIMAL(5,2) | NOT NULL | Annual interest rate percentage |
| term_months | INT | NOT NULL | Loan term in months |
| purpose | TEXT | NOT NULL | Loan purpose |
| status | ENUM | NOT NULL, DEFAULT 'pending' | Status: pending, approved, rejected, disbursed, active, paid, overdue |
| application_date | DATE | NOT NULL | Application submission date |
| approval_date | DATE | NULLABLE | Loan approval date |
| disbursement_date | DATE | NULLABLE | Loan disbursement date |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX (loan_number)
- INDEX (member_id)
- INDEX (status)
- INDEX (application_date)

### loan_guarantors

Stores loan guarantor information and approvals.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| loan_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY | Reference to loans table |
| guarantor_member_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY | Reference to members table (guarantor) |
| guaranteed_amount | DECIMAL(15,2) | NOT NULL | Amount guaranteed |
| status | ENUM | NOT NULL, DEFAULT 'pending' | Status: pending, approved, rejected |
| approval_date | DATE | NULLABLE | Guarantor approval date |
| notes | TEXT | NULLABLE | Guarantor notes |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (loan_id)
- INDEX (guarantor_member_id)
- INDEX (status)
- UNIQUE INDEX (loan_id, guarantor_member_id)

### loan_repayments

Stores loan repayment transactions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| loan_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY | Reference to loans table |
| amount | DECIMAL(15,2) | NOT NULL | Repayment amount |
| payment_date | DATE | NOT NULL | Date of payment |
| source | VARCHAR(50) | NOT NULL | Source: cash, bank_transfer, salary_deduction |
| reference | VARCHAR(100) | NULLABLE | Payment reference number |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (loan_id)
- INDEX (payment_date)
- INDEX (reference)

### notifications

Stores system notifications.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| user_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY | Reference to users table |
| type | VARCHAR(50) | NOT NULL | Notification type |
| channel | ENUM | NOT NULL | Channel: email, sms, in_app |
| subject | VARCHAR(255) | NOT NULL | Notification subject |
| message | TEXT | NOT NULL | Notification message |
| status | ENUM | NOT NULL, DEFAULT 'pending' | Status: pending, sent, failed, read |
| sent_at | TIMESTAMP | NULLABLE | Sent timestamp |
| read_at | TIMESTAMP | NULLABLE | Read timestamp |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (user_id)
- INDEX (status)
- INDEX (type)
- INDEX (created_at)

### audit_logs

Stores audit trail of user actions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| user_id | BIGINT UNSIGNED | NULLABLE, FOREIGN KEY | Reference to users table |
| action | VARCHAR(50) | NOT NULL | Action performed: create, update, delete, login, logout |
| entity_type | VARCHAR(100) | NULLABLE | Entity type affected |
| entity_id | BIGINT UNSIGNED | NULLABLE | Entity ID affected |
| changes | JSON | NULLABLE | Changes made (before/after values) |
| ip_address | VARCHAR(45) | NULLABLE | User IP address |
| user_agent | TEXT | NULLABLE | User agent string |
| created_at | TIMESTAMP | NOT NULL | Action timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (user_id)
- INDEX (action)
- INDEX (entity_type, entity_id)
- INDEX (created_at)

### system_config

Stores system configuration settings.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| key | VARCHAR(100) | NOT NULL, UNIQUE | Configuration key |
| value | TEXT | NOT NULL | Configuration value |
| description | TEXT | NULLABLE | Configuration description |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX (key)

## Foreign Key Constraints

```sql
-- members table
ALTER TABLE members
  ADD CONSTRAINT fk_members_user_id 
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- savings_accounts table
ALTER TABLE savings_accounts
  ADD CONSTRAINT fk_savings_accounts_member_id 
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE;

-- savings_transactions table
ALTER TABLE savings_transactions
  ADD CONSTRAINT fk_savings_transactions_account_id 
  FOREIGN KEY (account_id) REFERENCES savings_accounts(id) ON DELETE CASCADE;

-- loans table
ALTER TABLE loans
  ADD CONSTRAINT fk_loans_member_id 
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE;

-- loan_guarantors table
ALTER TABLE loan_guarantors
  ADD CONSTRAINT fk_loan_guarantors_loan_id 
  FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_loan_guarantors_guarantor_member_id 
  FOREIGN KEY (guarantor_member_id) REFERENCES members(id) ON DELETE CASCADE;

-- loan_repayments table
ALTER TABLE loan_repayments
  ADD CONSTRAINT fk_loan_repayments_loan_id 
  FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE;

-- notifications table
ALTER TABLE notifications
  ADD CONSTRAINT fk_notifications_user_id 
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- audit_logs table
ALTER TABLE audit_logs
  ADD CONSTRAINT fk_audit_logs_user_id 
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
```

## Default Configuration Values

The following configuration values should be seeded into the `system_config` table:

| Key | Value | Description |
|-----|-------|-------------|
| interest_rate | 15 | Default annual interest rate (%) |
| max_loan_multiplier | 3 | Maximum loan amount as multiple of savings |
| min_guarantors | 2 | Minimum number of guarantors required |
| processing_fee_percentage | 2 | Loan processing fee (%) |
| max_loan_term_months | 36 | Maximum loan term in months |
| min_savings_period_months | 6 | Minimum savings period before loan eligibility |

## Data Types and Precision

- **DECIMAL(15,2)**: Used for monetary values, supports up to 999,999,999,999.99
- **VARCHAR**: Used for text fields with known maximum length
- **TEXT**: Used for longer text content
- **JSON**: Used for structured data (employment_info, changes)
- **ENUM**: Used for fields with predefined values
- **TIMESTAMP**: Automatically managed by Laravel

## Backup and Maintenance

- Regular backups should be scheduled (daily recommended)
- Audit logs should be archived periodically (monthly/quarterly)
- Indexes should be optimized regularly for performance
- Old notifications can be purged after a retention period

## Security Considerations

- All passwords are hashed using bcrypt
- Sensitive data should be encrypted at rest
- Database access should be restricted to application only
- Regular security audits should be performed
- Backup files should be encrypted
