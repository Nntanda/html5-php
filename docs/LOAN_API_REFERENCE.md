# Loan Application and Guarantor Workflow API Reference

## Overview
This document provides a quick reference for the loan application and guarantor workflow APIs implemented in Task 5.

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

## Loan Application Endpoints

### Apply for Loan
```http
POST /api/loans/apply
```

**Request Body:**
```json
{
  "member_id": 1,
  "amount": 2000000,
  "term_months": 12,
  "purpose": "Business expansion",
  "guarantors": [
    {
      "member_id": 2,
      "guaranteed_amount": 1000000
    },
    {
      "member_id": 3,
      "guaranteed_amount": 1000000
    }
  ]
}
```

**Response (201 Created):**
```json
{
  "message": "Loan application submitted successfully",
  "loan": {
    "id": 1,
    "loan_number": "LN202400001",
    "member": {
      "id": 1,
      "member_number": "MEM202400001",
      "full_name": "John Doe"
    },
    "amount": 2000000,
    "interest_rate": 15,
    "term_months": 12,
    "purpose": "Business expansion",
    "status": "pending",
    "monthly_repayment": 175832,
    "outstanding_balance": 2000000,
    "application_date": "2024-01-15",
    "guarantors": [
      {
        "id": 1,
        "member": {
          "id": 2,
          "member_number": "MEM202400002",
          "full_name": "Jane Smith"
        },
        "guaranteed_amount": 1000000,
        "status": "pending"
      }
    ],
    "total_repaid": 0,
    "remaining_balance": 2000000,
    "created_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

**Validation Errors (422):**
```json
{
  "message": "Loan application not eligible",
  "errors": [
    "Member is not active",
    "Requested amount exceeds maximum eligible amount of 3000000.00"
  ]
}
```

### Get Loan Details
```http
GET /api/loans/{id}
```

**Response (200 OK):**
```json
{
  "loan": {
    "id": 1,
    "loan_number": "LN202400001",
    "member": { ... },
    "amount": 2000000,
    "status": "pending",
    "guarantors": [ ... ],
    "total_repaid": 0,
    "remaining_balance": 2000000
  }
}
```

### List Loans
```http
GET /api/loans?status=pending&member_id=1&from_date=2024-01-01&to_date=2024-12-31&search=LN2024&page=1&per_page=15
```

**Query Parameters:**
- `status` (optional) - Filter by loan status
- `member_id` (optional) - Filter by member
- `from_date` (optional) - Filter from date (YYYY-MM-DD)
- `to_date` (optional) - Filter to date (YYYY-MM-DD)
- `search` (optional) - Search by loan number or member name
- `page` (optional) - Page number (default: 1)
- `per_page` (optional) - Items per page (default: 15)

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "loan_number": "LN202400001",
      "member": { ... },
      "amount": 2000000,
      "status": "pending",
      "application_date": "2024-01-15"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 15,
    "last_page": 4
  }
}
```

### Update Loan Application
```http
PUT /api/loans/{id}
```

**Request Body:**
```json
{
  "amount": 2500000,
  "term_months": 18,
  "purpose": "Business expansion and equipment"
}
```

**Response (200 OK):**
```json
{
  "message": "Loan application updated successfully",
  "loan": { ... }
}
```

**Note:** Only pending loans can be updated.

## Guarantor Workflow Endpoints

### Add Guarantor to Loan
```http
POST /api/loans/{loanId}/guarantors
```

**Request Body:**
```json
{
  "guarantor_member_id": 2,
  "guaranteed_amount": 1000000
}
```

**Response (201 Created):**
```json
{
  "message": "Guarantor added successfully",
  "guarantor": {
    "id": 1,
    "loan_id": 1,
    "member": {
      "id": 2,
      "member_number": "MEM202400002",
      "full_name": "Jane Smith"
    },
    "guaranteed_amount": 1000000,
    "status": "pending",
    "approval_date": null,
    "rejection_reason": null,
    "created_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

### Approve/Reject Guarantor Request
```http
PUT /api/loans/{loanId}/guarantors/{guarantorId}
```

**Request Body (Approval):**
```json
{
  "status": "accepted"
}
```

**Request Body (Rejection):**
```json
{
  "status": "rejected",
  "rejection_reason": "Cannot guarantee at this time"
}
```

**Response (200 OK):**
```json
{
  "message": "Guarantor request updated successfully",
  "guarantor": {
    "id": 1,
    "loan_id": 1,
    "member": { ... },
    "guaranteed_amount": 1000000,
    "status": "accepted",
    "approval_date": "2024-01-15"
  },
  "loan_status": "guarantors_approved"
}
```

**Note:** When all guarantors approve, the loan status automatically updates to `guarantors_approved`.

### List Loan Guarantors
```http
GET /api/loans/{loanId}/guarantors
```

**Response (200 OK):**
```json
{
  "loan_id": 1,
  "loan_number": "LN202400001",
  "guarantors": [
    {
      "id": 1,
      "loan_id": 1,
      "member": {
        "id": 2,
        "member_number": "MEM202400002",
        "full_name": "Jane Smith"
      },
      "guaranteed_amount": 1000000,
      "status": "accepted",
      "approval_date": "2024-01-15"
    }
  ]
}
```

### Get Pending Guarantor Requests for Member
```http
GET /api/members/{memberId}/guarantor-requests
```

**Response (200 OK):**
```json
{
  "member_id": 2,
  "member_number": "MEM202400002",
  "full_name": "Jane Smith",
  "pending_requests": [
    {
      "id": 1,
      "loan": {
        "id": 1,
        "loan_number": "LN202400001",
        "amount": 2000000,
        "purpose": "Business expansion",
        "term_months": 12,
        "interest_rate": 15,
        "applicant": {
          "id": 1,
          "member_number": "MEM202400001",
          "full_name": "John Doe"
        }
      },
      "guaranteed_amount": 1000000,
      "status": "pending",
      "created_at": "2024-01-15T10:30:00.000000Z"
    }
  ]
}
```

## Loan Status Lifecycle

```
pending
  ↓
  ├─→ guarantors_approved (when all guarantors approve)
  │     ↓
  │     └─→ approved_pending_disbursement (when loan officer approves)
  │           ↓
  │           └─→ active (when accountant disburses)
  │                 ↓
  │                 ├─→ closed (when fully repaid)
  │                 └─→ overdue (if payment is 30+ days late)
  │
  └─→ rejected (if loan officer rejects)
```

## Guarantor Status Lifecycle

```
pending
  ├─→ accepted (when guarantor approves)
  ├─→ rejected (when guarantor rejects)
  └─→ withdrawn (when guarantor withdraws)
```

## Error Responses

### Validation Error (422)
```json
{
  "message": "Loan application not eligible",
  "errors": [
    "Member is not active",
    "Requested amount exceeds maximum eligible amount"
  ]
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

## Loan Eligibility Requirements

A member is eligible for a loan if:
1. Member status is active
2. Member has a savings account
3. Member has maintained savings for at least 6 months
4. Requested amount does not exceed 3x their savings balance
5. Member has no existing active loans
6. Member has no overdue loans

## Guarantor Requirements

- Minimum 2 guarantors required per loan
- Total guaranteed amount must be at least equal to loan amount
- Guarantor cannot be the loan applicant
- Each member can only guarantee once per loan

## Loan Calculations

### Monthly Repayment
Uses the reducing balance method:
```
M = P * [r(1+r)^n] / [(1+r)^n - 1]

Where:
  M = Monthly payment
  P = Principal amount
  r = Monthly interest rate (annual rate / 12 / 100)
  n = Number of months
```

### Interest Calculation
```
Interest = Principal * (Annual Rate / 100) * (Days / 365)
```

### Outstanding Balance
```
Outstanding = Loan Amount - Total Repaid + Accrued Interest
```

## Examples

### Example 1: Apply for Loan
```bash
curl -X POST http://localhost:8000/api/loans/apply \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "member_id": 1,
    "amount": 2000000,
    "term_months": 12,
    "purpose": "Business expansion",
    "guarantors": [
      {"member_id": 2, "guaranteed_amount": 1000000},
      {"member_id": 3, "guaranteed_amount": 1000000}
    ]
  }'
```

### Example 2: Approve Guarantor Request
```bash
curl -X PUT http://localhost:8000/api/loans/1/guarantors/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "accepted"
  }'
```

### Example 3: Get Pending Guarantor Requests
```bash
curl -X GET http://localhost:8000/api/members/2/guarantor-requests \
  -H "Authorization: Bearer {token}"
```

## Testing

Run the test suite:
```bash
# Unit tests
php artisan test tests/Unit/LoanServiceTest.php

# Feature tests
php artisan test tests/Feature/LoanApplicationTest.php
php artisan test tests/Feature/GuarantorWorkflowTest.php

# All tests
php artisan test
```

## Notes

- All monetary values are in the system's base currency unit (e.g., UGX)
- Dates are in YYYY-MM-DD format
- Timestamps are in ISO8601 format
- All calculations use 2 decimal places
- Database transactions ensure data consistency
