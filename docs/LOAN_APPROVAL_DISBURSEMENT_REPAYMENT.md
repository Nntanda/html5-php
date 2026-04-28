# Loan Approval, Disbursement, and Repayment API Reference

## Overview

This document provides a comprehensive reference for the loan approval, disbursement, and repayment APIs implemented in Tasks 6 and 7 of the SACCO Management System.

## Base URL
```
/api
```

## Authentication
All endpoints require authentication using Laravel Sanctum tokens.

```
Authorization: Bearer {token}
Content-Type: application/json
```

---

## Task 6: Loan Approval and Disbursement

### 6.1 Loan Approval Endpoints

#### Approve Loan (Loan Officer)
```http
PUT /api/loans/{id}/approve
```

**Requirements:**
- User must have `loan_officer` role
- Loan must be in `guarantors_approved` status
- All guarantors must have approved the loan

**Request Body:**
```json
{
  "approval_comment": "Loan approved - all requirements met"
}
```

**Response (200 OK):**
```json
{
  "message": "Loan approved successfully",
  "loan": {
    "id": 1,
    "loan_number": "LN202400001",
    "status": "approved_pending_disbursement",
    "approval_date": "2024-01-20",
    "approved_by": {
      "id": 2,
      "name": "John Officer"
    },
    "approval_comment": "Loan approved - all requirements met"
  }
}
```

**Error Response (422):**
```json
{
  "message": "Loan can only be approved when all guarantors have approved",
  "current_status": "pending"
}
```

#### Reject Loan (Loan Officer)
```http
PUT /api/loans/{id}/reject
```

**Requirements:**
- User must have `loan_officer` role
- Loan must be in `guarantors_approved` status

**Request Body:**
```json
{
  "rejection_reason": "Insufficient guarantor coverage"
}
```

**Response (200 OK):**
```json
{
  "message": "Loan rejected successfully",
  "loan": {
    "id": 1,
    "loan_number": "LN202400001",
    "status": "rejected",
    "rejection_reason": "Insufficient guarantor coverage"
  }
}
```

### 6.2 Loan Disbursement Endpoint

#### Disburse Loan (Accountant)
```http
PUT /api/loans/{id}/disburse
```

**Requirements:**
- User must have `accountant` role
- Loan must be in `approved_pending_disbursement` status
- First repayment date must be in the future

**Request Body:**
```json
{
  "disbursement_method": "bank_transfer",
  "first_repayment_date": "2024-02-20"
}
```

**Disbursement Methods:**
- `bank_transfer` - Direct bank transfer
- `mobile_money` - Mobile money transfer
- `cash` - Cash disbursement
- `cheque` - Cheque disbursement

**Response (200 OK):**
```json
{
  "message": "Loan disbursed successfully",
  "loan": {
    "id": 1,
    "loan_number": "LN202400001",
    "status": "active",
    "disbursement_date": "2024-01-20",
    "disbursement_method": "bank_transfer",
    "first_repayment_date": "2024-02-20",
    "disbursed_by": {
      "id": 3,
      "name": "Jane Accountant"
    }
  }
}
```

**Disbursement Process:**
1. Validates loan is in `approved_pending_disbursement` status
2. Updates loan status to `active`
3. Records disbursement date and method
4. Credits member's savings account with loan amount
5. Creates savings transaction record for audit trail

### 6.3 Repayment Schedule Calculation

The repayment schedule is automatically calculated when a loan is disbursed using the reducing balance method.

**Formula:**
```
M = P * [r(1+r)^n] / [(1+r)^n - 1]

Where:
  M = Monthly payment
  P = Principal amount
  r = Monthly interest rate (annual rate / 12 / 100)
  n = Number of months
```

**Schedule Structure:**
```json
{
  "month": 1,
  "due_date": "2024-02-20",
  "principal": 150000,
  "interest": 25832,
  "payment": 175832,
  "balance": 1850000
}
```

---

## Task 7: Loan Repayment Processing

### 7.1 Manual Repayment Endpoint

#### Record Manual Repayment
```http
POST /api/loans/{loanId}/repayments
```

**Requirements:**
- Loan must be in `active` status
- Amount must be greater than 0
- Payment date must be on or before today

**Request Body:**
```json
{
  "amount": 175832,
  "payment_date": "2024-02-20",
  "source": "manual",
  "reference": "MANUAL_001",
  "notes": "First monthly payment"
}
```

**Payment Sources:**
- `manual` - Manual payment entry
- `cash` - Cash payment
- `bank_transfer` - Bank transfer
- `mobile_money` - Mobile money payment
- `salary_deduction` - Automatic salary deduction

**Response (201 Created):**
```json
{
  "message": "Repayment recorded successfully",
  "repayment": {
    "id": 1,
    "loan_id": 1,
    "amount": 175832,
    "principal_amount": 150000,
    "interest_amount": 25832,
    "penalty_amount": 0,
    "payment_date": "2024-02-20",
    "source": "manual",
    "reference": "MANUAL_001",
    "recorded_by": {
      "id": 3,
      "name": "Jane Accountant"
    },
    "notes": "First monthly payment"
  },
  "loan_outstanding_balance": 1824168
}
```

**Repayment Processing:**
1. Validates loan is active
2. Calculates principal and interest breakdown
3. Creates repayment record
4. Updates loan outstanding balance
5. Automatically closes loan if fully repaid

#### Get Loan Repayments
```http
GET /api/loans/{loanId}/repayments?page=1&per_page=15
```

**Response (200 OK):**
```json
{
  "loan_id": 1,
  "loan_number": "LN202400001",
  "data": [
    {
      "id": 1,
      "loan_id": 1,
      "amount": 175832,
      "principal_amount": 150000,
      "interest_amount": 25832,
      "payment_date": "2024-02-20",
      "source": "manual",
      "reference": "MANUAL_001"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 5,
    "per_page": 15,
    "last_page": 1
  }
}
```

### 7.2 Automatic Salary Deduction Repayment

#### Upload Salary Deduction CSV
```http
POST /api/loans/repayments/upload-deductions
```

**Requirements:**
- File must be CSV format
- Loans must be in `active` status
- Member numbers must match loan member

**CSV Format:**
```csv
loan_number,member_number,amount,payment_date
LN202400001,MEM202400001,175832,2024-02-20
LN202400002,MEM202400002,150000,2024-02-20
```

**Request:**
```
Content-Type: multipart/form-data

file: [CSV file]
```

**Response (200 OK):**
```json
{
  "message": "Salary deduction repayments processed",
  "summary": {
    "total_amount": 325832,
    "total_repayments": 2,
    "processed_records": 2,
    "failed_records": 0,
    "errors": []
  }
}
```

**CSV Processing:**
1. Parses CSV file
2. Validates each record (loan exists, member matches, loan is active)
3. Calculates principal and interest breakdown
4. Creates repayment records with `salary_deduction` source
5. Updates loan balances
6. Closes loans if fully repaid
7. Returns summary with processed and failed records

**Error Handling:**
- Invalid loan number: Record skipped, error logged
- Member mismatch: Record skipped, error logged
- Non-active loan: Record skipped, error logged
- Missing fields: Record skipped, error logged

### 7.3 Loan Status Tracking

#### Get Loan Tracking Information
```http
GET /api/loans/{loanId}/tracking
```

**Response (200 OK):**
```json
{
  "loan": {
    "id": 1,
    "loan_number": "LN202400001",
    "amount": 2000000,
    "status": "active",
    "interest_rate": 15,
    "term_months": 12,
    "monthly_repayment": 175832
  },
  "tracking": {
    "outstanding_balance": 1824168,
    "total_repaid": 175832,
    "remaining_payments": 11,
    "is_overdue": false,
    "next_payment_due": "2024-03-20",
    "disbursement_date": "2024-01-20",
    "first_repayment_date": "2024-02-20"
  },
  "payment_history": [
    {
      "id": 1,
      "amount": 175832,
      "principal_amount": 150000,
      "interest_amount": 25832,
      "payment_date": "2024-02-20",
      "source": "manual"
    }
  ],
  "repayment_schedule": [
    {
      "month": 1,
      "due_date": "2024-02-20",
      "principal": 150000,
      "interest": 25832,
      "payment": 175832,
      "balance": 1850000
    }
  ]
}
```

**Tracking Information:**
- `outstanding_balance` - Current amount owed including accrued interest
- `total_repaid` - Total amount paid to date
- `remaining_payments` - Number of payments still due
- `is_overdue` - Whether loan is 30+ days overdue
- `next_payment_due` - Date of next payment
- `payment_history` - Last 10 payments
- `repayment_schedule` - Full amortization schedule

---

## Loan Status Lifecycle

```
pending
  ↓
  ├─→ guarantors_approved (when all guarantors approve)
  │     ↓
  │     ├─→ approved_pending_disbursement (when loan officer approves)
  │     │     ↓
  │     │     └─→ active (when accountant disburses)
  │     │           ↓
  │     │           ├─→ closed (when fully repaid)
  │     │           └─→ overdue (if payment is 30+ days late)
  │     │
  │     └─→ rejected (if loan officer rejects)
  │
  └─→ rejected (if loan officer rejects at guarantors_approved)
```

---

## Repayment Breakdown

Each repayment is broken down into:
- **Principal Amount** - Portion reducing the loan balance
- **Interest Amount** - Portion for interest charges
- **Penalty Amount** - Late payment penalties (if applicable)

**Calculation:**
```
Interest = Outstanding Balance * (Annual Rate / 100 / 12)
Principal = Payment Amount - Interest
```

---

## Error Responses

### Validation Error (422)
```json
{
  "message": "Loan can only be approved when all guarantors have approved",
  "current_status": "pending"
}
```

### Not Found (404)
```json
{
  "message": "Not found"
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated"
}
```

### Forbidden (403)
```json
{
  "message": "Forbidden"
}
```

---

## Configuration

The following settings can be configured in `.env`:

```env
SACCO_INTEREST_RATE=15
SACCO_MAX_LOAN_MULTIPLIER=3
SACCO_MIN_GUARANTORS=2
SACCO_PROCESSING_FEE=2
SACCO_MAX_LOAN_TERM=36
SACCO_MIN_SAVINGS_PERIOD=6
SACCO_OVERDUE_THRESHOLD=30
SACCO_PENALTY_PERCENTAGE=2
```

---

## Database Transactions

All critical operations use database transactions to ensure data consistency:

- Loan approval: Updates loan status and approval details
- Loan disbursement: Updates loan status, credits savings account, creates transaction
- Repayment recording: Creates repayment record, updates loan balance, updates loan status
- CSV upload: Batch processes multiple repayments atomically

---

## Testing

Run the test suite:

```bash
# Loan approval and disbursement tests
php artisan test tests/Feature/LoanApprovalAndDisbursementTest.php

# Loan repayment tests
php artisan test tests/Feature/LoanRepaymentTest.php

# Repayment schedule unit tests
php artisan test tests/Unit/LoanRepaymentScheduleTest.php

# All tests
php artisan test
```

---

## Examples

### Example 1: Approve Loan
```bash
curl -X PUT http://localhost:8000/api/loans/1/approve \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "approval_comment": "Loan approved - all requirements met"
  }'
```

### Example 2: Disburse Loan
```bash
curl -X PUT http://localhost:8000/api/loans/1/disburse \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "disbursement_method": "bank_transfer",
    "first_repayment_date": "2024-02-20"
  }'
```

### Example 3: Record Repayment
```bash
curl -X POST http://localhost:8000/api/loans/1/repayments \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 175832,
    "payment_date": "2024-02-20",
    "source": "manual",
    "reference": "MANUAL_001"
  }'
```

### Example 4: Upload Salary Deductions
```bash
curl -X POST http://localhost:8000/api/loans/repayments/upload-deductions \
  -H "Authorization: Bearer {token}" \
  -F "file=@repayments.csv"
```

### Example 5: Get Loan Tracking
```bash
curl -X GET http://localhost:8000/api/loans/1/tracking \
  -H "Authorization: Bearer {token}"
```

---

## Notes

- All monetary values are in the system's base currency unit (e.g., UGX)
- Dates are in YYYY-MM-DD format
- Timestamps are in ISO8601 format
- All calculations use 2 decimal places
- Database transactions ensure data consistency
- Overdue threshold is 30 days by default
- Repayment schedules use reducing balance method
- Loan automatically closes when fully repaid
- CSV uploads process records atomically
