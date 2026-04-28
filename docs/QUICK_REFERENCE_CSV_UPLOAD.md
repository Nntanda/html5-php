# Quick Reference: CSV Upload for Salary Deductions

## API Endpoint

```
POST /api/savings/upload-deductions
```

## Request

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Parameters:**
- `file` (required): CSV file
- `salary_period` (required): Period identifier (e.g., "January 2024")

## CSV Format

```csv
member_number,amount,reference
MEM001,50000,SAL-2024-01
MEM002,75000,SAL-2024-02
```

**Required Columns (in order):**
1. `member_number` - Member's unique ID
2. `amount` - Positive number
3. `reference` - Unique transaction reference

## Success Response

```json
{
  "message": "CSV upload completed",
  "summary": {
    "total_records": 2,
    "successful": 2,
    "failed": 0,
    "total_amount_processed": 125000,
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
    }
  ],
  "errors": []
}
```

## Common Errors

| Error | Cause | Fix |
|-------|-------|-----|
| Invalid CSV format | Wrong headers | Use: member_number, amount, reference |
| Member not found | Member doesn't exist | Verify member number |
| Member is not active | Member status inactive | Activate member first |
| Invalid amount | Amount ≤ 0 or not numeric | Use positive numbers |
| Transaction reference already exists | Duplicate reference | Use unique reference |

## cURL Example

```bash
curl -X POST http://localhost:8000/api/savings/upload-deductions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@salary_deductions.csv" \
  -F "salary_period=January 2024"
```

## JavaScript Example

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
```

## Validation Rules

- File: CSV/TXT, max 5MB
- Member: Must exist and be active
- Amount: Must be positive number
- Reference: Must be unique and non-empty
- Headers: Must match exactly

## Testing

```bash
# Run all tests
php artisan test

# Run feature tests only
php artisan test tests/Feature/SavingsTransactionUploadTest.php

# Run unit tests only
php artisan test tests/Unit/SavingsTransactionUploadLogicTest.php
```

## Key Features

✅ Batch processing with database transactions
✅ Comprehensive error handling
✅ Partial success support
✅ Detailed summary reports
✅ Input validation
✅ Authentication required
✅ File size limits
✅ Unique reference enforcement

## Documentation

- Full guide: [CSV_UPLOAD_IMPLEMENTATION.md](CSV_UPLOAD_IMPLEMENTATION.md)
- API docs: [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- Database schema: [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md)

## Support

For issues:
1. Check error message in response
2. Review validation rules above
3. Verify CSV format
4. Check member status
5. Review full documentation
