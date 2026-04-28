# SACCO Management System - API Documentation

## Base URL

```
Development: http://localhost:8000/api
Production: https://your-domain.com/api
```

## Authentication

All API endpoints (except login and registration) require authentication using Laravel Sanctum tokens.

### Headers

```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

## Authentication Endpoints

### Login

```http
POST /api/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "SuperAdmin"
  }
}
```

### Logout

```http
POST /api/logout
```

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

## User Management Endpoints

### List Users

```http
GET /api/users?page=1&per_page=15&search=john&role=SuperAdmin
```

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `search` (optional): Search by name or email
- `role` (optional): Filter by role

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "SuperAdmin",
      "status": "active",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 15
  }
}
```

### Create User

```http
POST /api/users
```

**Request Body:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "password123",
  "role": "LoanOfficer",
  "status": "active"
}
```

### Get User

```http
GET /api/users/{id}
```

### Update User

```http
PUT /api/users/{id}
```

### Delete User

```http
DELETE /api/users/{id}
```

## Member Management Endpoints

### Register Member

```http
POST /api/members
```

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Smith",
  "email": "john.smith@example.com",
  "phone": "+256700000000",
  "address": "123 Main St, Kampala",
  "employment_info": {
    "employer": "ABC Company",
    "employee_id": "EMP001",
    "department": "Finance"
  }
}
```

### List Members

```http
GET /api/members?page=1&search=john&status=active
```

### Get Member Profile

```http
GET /api/members/{id}
```

### Get Member Financial Summary

```http
GET /api/members/{id}/summary
```

**Response:**
```json
{
  "member": {
    "id": 1,
    "member_number": "MEM001",
    "name": "John Smith"
  },
  "savings": {
    "total_balance": 5000000,
    "total_deposits": 6000000,
    "account_count": 1
  },
  "loans": {
    "active_loans": 1,
    "total_borrowed": 10000000,
    "total_repaid": 3000000,
    "outstanding_balance": 7000000
  }
}
```

## Savings Management Endpoints

### Create Savings Account

```http
POST /api/savings/accounts
```

### Get Account Details

```http
GET /api/savings/accounts/{id}
```

### Get Transaction History

```http
GET /api/savings/accounts/{id}/transactions?from=2024-01-01&to=2024-12-31
```

### Record Direct Deposit

```http
POST /api/savings/deposit
```

**Request Body:**
```json
{
  "account_id": 1,
  "amount": 100000,
  "source": "cash",
  "reference": "DEP001"
}
```

### Upload Salary Deductions

```http
POST /api/savings/upload-deductions
Content-Type: multipart/form-data
```

**Form Data:**
- `file`: CSV file
- `transaction_date`: Date of deductions

**CSV Format:**
```csv
member_number,amount,reference
MEM001,50000,SAL-2024-01
MEM002,75000,SAL-2024-01
```

## Loan Management Endpoints

### Apply for Loan

```http
POST /api/loans/apply
```

**Request Body:**
```json
{
  "member_id": 1,
  "amount": 5000000,
  "term_months": 12,
  "purpose": "Business expansion",
  "guarantors": [
    {
      "member_id": 2,
      "guaranteed_amount": 2500000
    },
    {
      "member_id": 3,
      "guaranteed_amount": 2500000
    }
  ]
}
```

### List Loans

```http
GET /api/loans?status=pending&member_id=1
```

### Get Loan Details

```http
GET /api/loans/{id}
```

### Approve Loan

```http
PUT /api/loans/{id}/approve
```

**Request Body:**
```json
{
  "notes": "Approved based on good credit history"
}
```

### Reject Loan

```http
PUT /api/loans/{id}/reject
```

### Disburse Loan

```http
PUT /api/loans/{id}/disburse
```

**Request Body:**
```json
{
  "disbursement_method": "bank_transfer",
  "reference": "TXN123456"
}
```

### Record Loan Repayment

```http
POST /api/loans/{id}/repayments
```

**Request Body:**
```json
{
  "amount": 500000,
  "payment_date": "2024-01-15",
  "source": "cash",
  "reference": "REP001"
}
```

## Guarantor Endpoints

### Add Guarantor

```http
POST /api/loans/{id}/guarantors
```

### Approve/Reject Guarantor Request

```http
PUT /api/loans/{loanId}/guarantors/{guarantorId}
```

**Request Body:**
```json
{
  "status": "approved",
  "notes": "I agree to guarantee this loan"
}
```

### Get Pending Guarantor Requests

```http
GET /api/members/{id}/guarantor-requests
```

## Reports Endpoints

### Member Statement

```http
GET /api/reports/member/{id}/statement?from=2024-01-01&to=2024-12-31&format=pdf
```

### Loan Summary

```http
GET /api/reports/member/{id}/loan-summary
```

### Savings Summary Report

```http
GET /api/reports/savings-summary?from=2024-01-01&to=2024-12-31
```

### Loans Portfolio Report

```http
GET /api/reports/loans-summary?status=active
```

### Overdue Loans Report

```http
GET /api/reports/overdue-loans
```

## Notifications Endpoints

### Get User Notifications

```http
GET /api/notifications?unread=true
```

### Mark Notification as Read

```http
PUT /api/notifications/{id}/read
```

### Send Manual Notification

```http
POST /api/notifications/send
```

**Request Body:**
```json
{
  "user_id": 1,
  "type": "general",
  "channel": "email",
  "subject": "Important Notice",
  "message": "Your loan has been approved"
}
```

## System Configuration Endpoints

### Get Configuration

```http
GET /api/config
```

### Update Configuration

```http
PUT /api/config
```

**Request Body:**
```json
{
  "interest_rate": 15,
  "max_loan_multiplier": 3,
  "min_guarantors": 2,
  "processing_fee_percentage": 2
}
```

## Audit Log Endpoints

### View Audit Logs

```http
GET /api/audit-logs?user_id=1&action=create&from=2024-01-01&to=2024-12-31
```

## Backup Endpoints

### Create Backup

```http
POST /api/backups/create
```

### List Backups

```http
GET /api/backups
```

### Restore Backup

```http
POST /api/backups/{id}/restore
```

## Error Responses

All endpoints return consistent error responses:

```json
{
  "message": "Error message",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### HTTP Status Codes

- `200 OK`: Successful request
- `201 Created`: Resource created successfully
- `400 Bad Request`: Invalid request data
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation errors
- `500 Internal Server Error`: Server error

## Rate Limiting

API requests are rate-limited to prevent abuse:
- 60 requests per minute for authenticated users
- 10 requests per minute for unauthenticated requests

## Pagination

List endpoints support pagination with the following parameters:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)

Response includes pagination metadata:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "to": 15,
    "total": 100,
    "per_page": 15,
    "last_page": 7
  }
}
```
