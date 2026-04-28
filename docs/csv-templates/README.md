# CSV Templates for SACCO Management System

This directory contains CSV templates for bulk data uploads in the SACCO Management System.

## Available Templates

### 1. Salary Deductions Template

**File:** `salary_deductions_template.csv`

**Purpose:** Upload monthly salary deductions for member savings accounts.

**Format:**
```csv
member_number,amount,reference
MEM001,50000,SAL-2024-01
```

**Fields:**
- `member_number` (required): Unique member identifier (e.g., MEM001)
- `amount` (required): Deduction amount in local currency (no commas or currency symbols)
- `reference` (required): Transaction reference (e.g., SAL-2024-01 for January 2024 salary)

**Usage:**
1. Download the template
2. Fill in member numbers, amounts, and reference
3. Save as CSV (UTF-8 encoding)
4. Upload via Admin Portal → Savings → Upload Salary Deductions

**Validation Rules:**
- Member number must exist in the system
- Amount must be a positive number
- Reference should be unique for tracking
- Maximum 1000 rows per upload

**Example:**
```csv
member_number,amount,reference
MEM001,50000,SAL-2024-01
MEM002,75000,SAL-2024-01
MEM003,100000,SAL-2024-01
```

### 2. Loan Repayments Template

**File:** `loan_repayments_template.csv`

**Purpose:** Upload bulk loan repayments from salary deductions.

**Format:**
```csv
loan_number,amount,reference
LOAN001,500000,REP-2024-01
```

**Fields:**
- `loan_number` (required): Unique loan identifier (e.g., LOAN001)
- `amount` (required): Repayment amount in local currency
- `reference` (required): Payment reference (e.g., REP-2024-01)

**Usage:**
1. Download the template
2. Fill in loan numbers, amounts, and reference
3. Save as CSV (UTF-8 encoding)
4. Upload via Admin Portal → Loans → Upload Repayments

**Validation Rules:**
- Loan number must exist and be active
- Amount must be a positive number
- Amount cannot exceed outstanding loan balance
- Reference should be unique for tracking
- Maximum 1000 rows per upload

**Example:**
```csv
loan_number,amount,reference
LOAN001,500000,REP-2024-01
LOAN002,750000,REP-2024-01
LOAN003,1000000,REP-2024-01
```

## General CSV Guidelines

### File Format
- **Encoding:** UTF-8 (recommended)
- **Delimiter:** Comma (,)
- **Line Ending:** LF or CRLF
- **Header Row:** Required (first row must contain column names)

### Data Format
- **Numbers:** No commas or currency symbols (use 50000, not 50,000 or UGX 50,000)
- **Dates:** YYYY-MM-DD format (e.g., 2024-01-15)
- **Text:** Avoid special characters that might break CSV format
- **Empty Fields:** Leave blank, don't use NULL or N/A

### Best Practices

1. **Always use the provided templates** to ensure correct column order
2. **Validate data before upload** to avoid errors
3. **Keep a backup** of your CSV files
4. **Use meaningful references** for easy tracking
5. **Test with small batches** first (5-10 rows)
6. **Check upload summary** after processing

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| Invalid member number | Member doesn't exist | Verify member is registered |
| Invalid amount | Non-numeric value | Remove commas and currency symbols |
| Duplicate reference | Reference already used | Use unique reference per upload |
| File too large | More than 1000 rows | Split into multiple files |
| Encoding error | Wrong file encoding | Save as UTF-8 |

### Creating CSV Files

#### Using Microsoft Excel
1. Open the template in Excel
2. Fill in your data
3. File → Save As
4. Choose "CSV UTF-8 (Comma delimited) (*.csv)"
5. Click Save

#### Using Google Sheets
1. Open the template in Google Sheets
2. Fill in your data
3. File → Download → Comma Separated Values (.csv)

#### Using LibreOffice Calc
1. Open the template in Calc
2. Fill in your data
3. File → Save As
4. Choose "Text CSV (.csv)"
5. Set Character set to "Unicode (UTF-8)"
6. Set Field delimiter to ","
7. Click OK

### Upload Process

1. **Prepare CSV file** using the template
2. **Login to Admin Portal** with appropriate role
3. **Navigate to upload section**
   - Savings deductions: Savings → Upload Deductions
   - Loan repayments: Loans → Upload Repayments
4. **Select file** and upload
5. **Review summary** showing:
   - Total rows processed
   - Successful transactions
   - Failed transactions with reasons
6. **Download error report** if any failures
7. **Verify transactions** in the system

### Troubleshooting

**Upload fails immediately:**
- Check file format (must be .csv)
- Verify file encoding (UTF-8)
- Ensure header row is present

**Some rows fail:**
- Download error report
- Fix issues in original file
- Re-upload only failed rows

**All rows fail:**
- Verify column names match template exactly
- Check for special characters
- Ensure amounts are numeric

### Security Notes

- CSV files may contain sensitive financial data
- Store files securely
- Delete files after successful upload
- Don't share files via unsecured channels
- Use encrypted storage for backups

## Support

For issues with CSV uploads:
1. Check the error report from the upload summary
2. Verify your data against the template
3. Review the validation rules above
4. Contact system administrator if issues persist

## Version History

- **v1.0** - Initial templates for salary deductions and loan repayments
