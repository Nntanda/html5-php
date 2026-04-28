# Quick Reference: Loan Approval, Disbursement & Repayment

## Loan Approval Workflow

### 1. Approve Loan (Loan Officer)
```bash
PUT /api/loans/{id}/approve
{
  "approval_comment": "Approved"
}
```
**Precondition:** Loan status = `guarantors_approved`, all guarantors approved
**Result:** Loan status → `approved_pending_disbursement`

### 2. Reject Loan (Loan Officer)
```bash
PUT /api/loans/{id}/reject
{
  "rejection_reason": "Reason for rejection"
}
```
**Precondition:** Loan status = `guarantors_approved`
**Result:** Loan status → `rejected`

---

## Loan Disbursement Workflow

### 3. Disburse Loan (Accountant)
```bash
PUT /api/loans/{id}/disburse
{
  "disbursement_method": "bank_transfer",
  "first_repayment_date": "2024-02-20"
}
```
**Precondition:** Loan status = `approved_pending_disbursement`
**Result:** 
- Loan status → `active`
- Member's savings account credited
- Disbursement transaction created

**Disbursement Methods:**
- `bank_transfer`
- `mobile_money`
- `cash`
- `cheque`

---

## Repayment Processing

### 4. Record Manual Repayment
```bash
POST /api/loans/{loanId}/repayments
{
  "amount": 175832,
  "payment_date": "2024-02-20",
  "source": "manual",
  "reference": "MANUAL_001",
  "notes": "First payment"
}
```
**Precondition:** Loan status = `active`
**Result:** 
- Repayment recorded
- Outstanding balance updated
- Loan auto-closes if fully repaid

**Payment Sources:**
- `manual`
- `cash`
- `bank_transfer`
- `mobile_money`
- `salary_deduction`

### 5. Get Loan Repayments
```bash
GET /api/loans/{loanId}/repayments?page=1&per_page=15
```
**Returns:** Paginated list of repayments

### 6. Upload Salary Deductions
```bash
POST /api/loans/repayments/upload-deductions
Content-Type: multipart/form-data
file: [CSV file]
```

**CSV Format:**
```csv
loan_number,member_number,amount,payment_date
LN202400001,MEM202400001,175832,2024-02-20
LN202400002,MEM202400002,150000,2024-02-20
```

**Result:** Batch processes repayments, returns summary

---

## Loan Tracking

### 7. Get Loan Tracking Info
```bash
GET /api/loans/{loanId}/tracking
```

**Returns:**
- Outstanding balance (with accrued interest)
- Total repaid
- Remaining payments
- Is overdue (30+ days)
- Next payment due date
- Payment history (last 10)
- Full repayment schedule

---

## Loan Status Lifecycle

```
pending
  ↓
guarantors_approved (all guarantors approve)
  ├→ approved_pending_disbursement (loan officer approves)
  │   ↓
  │   active (accountant disburses)
  │   ├→ closed (fully repaid)
  │   └→ overdue (30+ days late)
  │
  └→ rejected (loan officer rejects)
```

---

## Key Calculations

### Monthly Repayment (Reducing Balance)
```
M = P × [r(1+r)^n] / [(1+r)^n - 1]

P = Principal
r = Monthly rate (annual / 12 / 100)
n = Number of months
```

### Interest Breakdown
```
Interest = Outstanding Balance × (Annual Rate / 100 / 12)
Principal = Payment - Interest
```

### Outstanding Balance
```
Balance = Loan Amount - Total Repaid + Accrued Interest
```

---

## Error Codes

| Status | Message | Cause |
|--------|---------|-------|
| 422 | Loan can only be approved when all guarantors have approved | Guarantors not all approved |
| 422 | Loan can only be disbursed when in approved_pending_disbursement status | Wrong loan status |
| 422 | Loan must be active to record repayments | Loan not active |
| 404 | Not found | Loan/repayment doesn't exist |
| 401 | Unauthenticated | Missing auth token |
| 403 | Forbidden | Wrong user role |

---

## Role Requirements

| Endpoint | Role |
|----------|------|
| PUT /api/loans/{id}/approve | loan_officer |
| PUT /api/loans/{id}/reject | loan_officer |
| PUT /api/loans/{id}/disburse | accountant |
| POST /api/loans/{loanId}/repayments | accountant |
| GET /api/loans/{loanId}/repayments | any |
| POST /api/loans/repayments/upload-deductions | accountant |
| GET /api/loans/{loanId}/tracking | any |

---

## CSV Upload Error Handling

| Error | Cause | Action |
|-------|-------|--------|
| Missing fields | loan_number, member_number, or amount missing | Record skipped |
| Loan not found | Invalid loan_number | Record skipped |
| Member mismatch | member_number doesn't match loan member | Record skipped |
| Loan not active | Loan status is not active | Record skipped |
| Invalid amount | Amount ≤ 0 | Record skipped |

---

## Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/LoanApprovalAndDisbursementTest.php
php artisan test tests/Feature/LoanRepaymentTest.php
php artisan test tests/Unit/LoanRepaymentScheduleTest.php
```

---

## Configuration (.env)

```env
SACCO_INTEREST_RATE=15
SACCO_MAX_LOAN_MULTIPLIER=3
SACCO_MIN_GUARANTORS=2
SACCO_OVERDUE_THRESHOLD=30
```

---

## Common Workflows

### Complete Loan Approval to Repayment
1. Guarantors approve loan → status: `guarantors_approved`
2. Loan officer approves → status: `approved_pending_disbursement`
3. Accountant disburses → status: `active`
4. Member makes repayments → balance decreases
5. When fully repaid → status: `closed`

### Batch Salary Deduction Processing
1. Prepare CSV with loan_number, member_number, amount, payment_date
2. Upload via POST /api/loans/repayments/upload-deductions
3. System validates and processes each record
4. Returns summary with success/failure counts
5. Failed records logged with error messages

### Check Loan Status
1. GET /api/loans/{loanId}/tracking
2. Review outstanding_balance, is_overdue, next_payment_due
3. View payment_history and repayment_schedule
4. Identify overdue loans for follow-up
