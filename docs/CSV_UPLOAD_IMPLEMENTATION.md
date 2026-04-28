# CSV Upload for Salary Deductions - Implementation Guide

## Overview

The CSV upload feature for salary deductions allows batch processing of salary savings transactions. This document describes the implementation, API usage, and testing procedures.

## Feature Requirements

### Functional Requirements

1. **File Upload Handling**
   - Accept CSV files via POST request
   - Validate file format and size (max 5MB)
   - Support standard CSV format with headers

2. **CSV Parsing**
   - Parse CSV using native PHP functions
   - Validate CSV structure and headers
   - Handle various data formats and edge cases

3. **Batch Processing**
   - Process multiple salary deductions in a single transaction
   - Use database transactions for data consistency
   - Rollback on any critical error

4. **Error Handling**
   - Validate each row independently
   - Collect all errors for reporting
   - Continue processing valid rows even if some fail

5. **Summary Report**
   - Generate upload summary with statistics
   - Report successful and failed records
   - Calculate total amount processed
   - Include processing timestamp

## API Endpoint

### POST /api/savings/upload-deductions

Upload a CSV file containing salary deductions for batch processing.

**Authentication:** Required (Sanctum token)

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| file | file | Yes | CSV file (max 5MB, .csv or .txt) |
| salary_period | string | Yes | Salary period identifier (e.g., "January 2024") |

**CSV Format:**

```csv
member_number,amount,reference
MEM001,50000,SAL-2024-01
MEM002,75000,SAL-2024-01-02
MEM003,100000,SAL-2024-01-03
```

**CSV Headers (Required):**
- `member_number`: Member's unique identifier
- `amount`: Salary deduction amount (positive number)
- `reference`: Unique transaction reference

**Response (Success - 200):**

```json
{
  "message": "CSV upload completed",
  "summary": {
    "total_records": 3,
    "successful": 3,
    "failed": 0,
    "total_amount_processed": 225000,
    "salary_period": "January 2024",
    "processed_at": "2024-01-15T10:30:00Z"
  },
  "processed_records": [
    {
      "line": 2,
      "member_number": "MEM001",
      "amount": 50000,
      "reference": "SAL-2024-01",
      "transaction_id": 1,
      "status": "success"
    },
    {
      "line": 3,
      "member_number": "MEM002",
      "amount": 75000,
      "reference": "SAL-2024-01-02",
      "transaction_id": 2,
      "status": "success"
    },
    {
      "line": 4,
      "member_number": "MEM003",
      "amount": 100000,
      "reference": "SAL-2024-01-03",
      "transaction_id": 3,
      "status": "success"
    }
  ],
  "errors": []
}
```

**Response (Validation Error - 422):**

```json
{
  "message": "Invalid CSV format. Expected headers: member_number, amount, reference",
  "errors": {
    "file": ["CSV headers do not match expected format"]
  }
}
```

**Response (Server Error - 500):**

```json
{
  "message": "Failed to process CSV upload",
  "error": "Error message details"
}
```

## Implementation Details

### Controller Method

Location: `backend/app/Http/Controllers/SavingsTransactionController.php`

The `uploadDeductions()` method handles:

1. **File Validation**
   - Validates file exists and is CSV format
   - Checks file size (max 5MB)
   - Validates salary_period parameter

2. **CSV Parsing**
   - Reads CSV file using PHP's `file()` function
   - Parses CSV using `str_getcsv()`
   - Validates header row

3. **Row Processing**
   - Validates each row independently
   - Checks member exists and is active
   - Verifies savings account exists
   - Ensures reference is unique
   - Validates amount is positive

4. **Transaction Creation**
   - Creates SavingsTransaction record
   - Updates SavingsAccount balance
   - Uses database transaction for consistency

5. **Error Collection**
   - Collects all errors with line numbers
   - Continues processing valid rows
   - Returns comprehensive error report

### Database Transaction

All operations are wrapped in a database transaction:

```php
DB::beginTransaction();
try {
    // Process CSV rows
    // Create transactions
    // Update balances
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    // Return error response
}
```

### Validation Rules

**File Validation:**
- Required: file must be provided
- Type: must be CSV or TXT file
- Size: maximum 5MB

**CSV Header Validation:**
- Must contain exactly: `member_number`, `amount`, `reference`
- Order must match expected format

**Row Validation:**
- Member number must exist in database
- Member must have active status
- Member must have savings account
- Amount must be numeric and positive (> 0)
- Reference must not be empty
- Reference must be unique (not already in database)

**Data Type Validation:**
- Amount: converted to float
- Reference: trimmed of whitespace
- Member number: trimmed of whitespace

## Error Handling

### Validation Errors

Each row is validated independently. Common errors include:

| Error | Cause | Resolution |
|-------|-------|-----------|
| Invalid number of columns | Row has wrong number of fields | Check CSV format |
| Invalid amount | Amount is not numeric or ≤ 0 | Use positive numbers |
| Member not found | Member number doesn't exist | Verify member number |
| Member is not active | Member status is not 'active' | Activate member first |
| Member does not have a savings account | No savings account created | Create savings account |
| Transaction reference already exists | Reference is duplicate | Use unique reference |
| Failed to create transaction | Database error | Check database connection |

### Error Response Format

Each error includes:
- `line`: CSV line number (1-indexed)
- `member_number`: Member number from CSV (if available)
- `error`: Error message description
- `data`: Row data (if applicable)

Example:

```json
{
  "errors": [
    {
      "line": 3,
      "member_number": "MEM999",
      "error": "Member not found"
    },
    {
      "line": 5,
      "member_number": "MEM001",
      "error": "Invalid amount. Must be a positive number",
      "amount": "-50000"
    }
  ]
}
```

## Usage Examples

### cURL Example

```bash
curl -X POST http://localhost:8000/api/savings/upload-deductions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@salary_deductions.csv" \
  -F "salary_period=January 2024"
```

### PHP Example

```php
$client = new \GuzzleHttp\Client();

$response = $client->post('http://localhost:8000/api/savings/upload-deductions', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
    ],
    'multipart' => [
        [
            'name' => 'file',
            'contents' => fopen('salary_deductions.csv', 'r'),
        ],
        [
            'name' => 'salary_period',
            'contents' => 'January 2024',
        ],
    ],
]);

$result = json_decode($response->getBody(), true);
```

### JavaScript/Fetch Example

```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('salary_period', 'January 2024');

const response = await fetch('/api/savings/upload-deductions', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
    },
    body: formData,
});

const result = await response.json();
console.log(result);
```

## Testing

### Test Files

1. **Feature Tests**: `backend/tests/Feature/SavingsTransactionUploadTest.php`
   - Tests API endpoint behavior
   - Tests file upload and validation
   - Tests error handling
   - Tests authentication

2. **Unit Tests**: `backend/tests/Unit/SavingsTransactionUploadLogicTest.php`
   - Tests transaction creation logic
   - Tests balance updates
   - Tests model relationships
   - Tests data casting

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/SavingsTransactionUploadTest.php

# Run with coverage
php artisan test --coverage

# Run specific test method
php artisan test tests/Feature/SavingsTransactionUploadTest.php --filter test_upload_deductions_with_valid_csv
```

### Test Coverage

The test suite covers:

- ✅ Valid CSV upload with multiple records
- ✅ Invalid CSV headers
- ✅ Invalid amount values
- ✅ Non-existent members
- ✅ Inactive members
- ✅ Duplicate references
- ✅ Mixed valid and invalid records
- ✅ Missing file parameter
- ✅ Missing salary_period parameter
- ✅ Empty CSV rows
- ✅ Zero and negative amounts
- ✅ Empty references
- ✅ Whitespace handling
- ✅ Large amounts
- ✅ Decimal amounts
- ✅ Authentication requirement
- ✅ File size validation

## Database Schema

### savings_transactions Table

```sql
CREATE TABLE savings_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    account_id BIGINT NOT NULL,
    type ENUM('salary_savings', 'direct_deposit', 'withdrawal', 'reversal'),
    amount DECIMAL(15, 2),
    source ENUM('salary', 'cash', 'bank_transfer', 'mobile_money'),
    reference VARCHAR(255) UNIQUE,
    transaction_date DATE,
    description TEXT,
    salary_period VARCHAR(255),
    employer_reference VARCHAR(255),
    is_reversed BOOLEAN DEFAULT FALSE,
    reversed_by BIGINT,
    reversed_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES savings_accounts(id),
    FOREIGN KEY (reversed_by) REFERENCES users(id),
    INDEX (account_id),
    INDEX (type),
    INDEX (transaction_date),
    INDEX (reference)
);
```

### savings_accounts Table

```sql
CREATE TABLE savings_accounts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    member_id BIGINT NOT NULL,
    account_number VARCHAR(255) UNIQUE,
    balance DECIMAL(15, 2) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id),
    INDEX (account_number),
    INDEX (member_id)
);
```

## Performance Considerations

1. **Batch Processing**
   - All transactions processed in single database transaction
   - Reduces database round trips
   - Ensures data consistency

2. **Indexing**
   - Reference field indexed for uniqueness check
   - Account_id indexed for balance updates
   - Member_number indexed for lookups

3. **File Size Limit**
   - 5MB maximum file size
   - Prevents memory issues
   - Typical CSV with 10,000 records is ~500KB

4. **Error Handling**
   - Errors collected without stopping processing
   - Allows partial success scenarios
   - Reduces need for retry logic

## Security Considerations

1. **Authentication**
   - Requires valid Sanctum token
   - Protects against unauthorized access

2. **File Validation**
   - Validates file type (CSV/TXT only)
   - Validates file size
   - Prevents malicious file uploads

3. **Data Validation**
   - All input validated before database operations
   - Prevents SQL injection via Eloquent ORM
   - Validates numeric values

4. **Database Transactions**
   - Ensures data consistency
   - Prevents partial updates
   - Rollback on errors

## Troubleshooting

### Common Issues

**Issue: "Invalid CSV format" error**
- Solution: Verify CSV headers are exactly: `member_number,amount,reference`
- Check for extra spaces or different column order

**Issue: "Member not found" errors**
- Solution: Verify member numbers in CSV match database
- Check member_number field in members table

**Issue: "Transaction reference already exists"**
- Solution: Use unique reference values
- Check for duplicate references in CSV file

**Issue: File upload fails**
- Solution: Check file size (max 5MB)
- Verify file is valid CSV format
- Check file permissions

**Issue: Balance not updating**
- Solution: Verify member has savings account
- Check database transaction logs
- Verify no database errors

## Future Enhancements

1. **Async Processing**
   - Queue large file uploads
   - Process in background jobs
   - Send completion notifications

2. **CSV Template Download**
   - Provide downloadable template
   - Include example data
   - Reduce user errors

3. **Duplicate Detection**
   - Detect duplicate references in CSV
   - Warn before processing
   - Option to skip duplicates

4. **Batch History**
   - Track upload batches
   - Store upload metadata
   - Audit trail for uploads

5. **Retry Logic**
   - Automatic retry for failed records
   - Configurable retry attempts
   - Exponential backoff

## Related Documentation

- [API Documentation](API_DOCUMENTATION.md)
- [Database Schema](DATABASE_SCHEMA.md)
- [Development Guide](DEVELOPMENT_GUIDE.md)
- [CSV Template](csv-templates/salary_deductions_template.csv)

## Support

For issues or questions:
1. Review this documentation
2. Check test files for usage examples
3. Review error messages and logs
4. Contact development team
