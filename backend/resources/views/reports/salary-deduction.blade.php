<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Deduction Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
        }
        
        .header p {
            margin: 5px 0;
            font-size: 12px;
        }
        
        .category-section {
            margin-bottom: 25px;
        }
        
        .category-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            padding: 5px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            font-size: 11px;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .subtotal-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        
        .grand-total {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border: 2px solid #000;
        }
        
        .signatures {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 40px;
        }
        
        .signature-box {
            display: table-cell;
            width: 33.33%;
            padding: 0 10px;
            vertical-align: top;
        }
        
        .signature-title {
            font-weight: bold;
            text-align: center;
            margin-bottom: 40px;
            text-transform: uppercase;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            height: 40px;
            margin-bottom: 5px;
        }
        
        .signature-label {
            text-align: center;
            font-size: 10px;
            margin-bottom: 20px;
        }
        
        .stamp-box {
            border: 1px solid #000;
            height: 60px;
            margin-top: 10px;
            text-align: center;
            padding-top: 25px;
            font-size: 10px;
            color: #666;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            
            .signatures {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Kitovu Hospital Staff Saving Scheme</h1>
        <h2>Salary Deduction Report</h2>
        <p>Generated on: {{ $generatedDate }}</p>
    </div>

    @foreach($groupedMembers as $category => $members)
        <div class="category-section">
            <div class="category-title">
                {{ call_user_func($getCategoryLabel, $category) }}
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%">Member Number</th>
                        <th style="width: 30%">Member Name</th>
                        <th style="width: 20%">Account Number</th>
                        <th style="width: 20%">Monthly Deduction (UGX)</th>
                        <th style="width: 15%">Category</th>
                    </tr>
                </thead>
                <tbody>
                    @php $categoryTotal = 0; @endphp
                    @foreach($members as $member)
                        @php 
                            $monthlyDeduction = $member->employment_info['monthly_savings'] ?? 0;
                            $categoryTotal += $monthlyDeduction;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $member->member_number }}</td>
                            <td>{{ $member->full_name }}</td>
                            <td class="text-center">
                                {{ $member->savingsAccount ? $member->savingsAccount->account_number : 'N/A' }}
                            </td>
                            <td class="text-right">{{ number_format($monthlyDeduction, 0) }}</td>
                            <td class="text-center">{{ call_user_func($getCategoryLabel, $category) }}</td>
                        </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="3" class="text-right"><strong>SUBTOTAL:</strong></td>
                        <td class="text-right"><strong>{{ number_format($categoryTotal, 0) }}</strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="grand-total">
        <strong>GRAND TOTAL: UGX {{ number_format($grandTotal, 0) }}</strong>
    </div>

    <div class="signatures">
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-title">Chairperson</div>
                <div class="signature-line"></div>
                <div class="signature-label">Signature & Date</div>
                <div class="stamp-box">OFFICIAL STAMP</div>
            </div>
            
            <div class="signature-box">
                <div class="signature-title">Treasurer</div>
                <div class="signature-line"></div>
                <div class="signature-label">Signature & Date</div>
                <div class="stamp-box">OFFICIAL STAMP</div>
            </div>
            
            <div class="signature-box">
                <div class="signature-title">Loans Officer</div>
                <div class="signature-line"></div>
                <div class="signature-label">Signature & Date</div>
                <div class="stamp-box">OFFICIAL STAMP</div>
            </div>
        </div>
    </div>
</body>
</html>