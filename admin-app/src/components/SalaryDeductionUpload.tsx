import React, { useState, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { apiClient } from '../api/client';

interface UploadSummary {
  total_records: number;
  successful: number;
  failed: number;
  errors: Array<{
    row: number;
    error: string;
  }>;
}

interface PreviewRecord {
  row: number;
  member_number: string;
  member_name?: string;
  amount: number;
  source: string;
  reference: string;
  status: 'valid' | 'warning' | 'error';
  message?: string;
}

interface SalaryDeductionUploadProps {
  onSuccess: (summary: UploadSummary) => void;
  onCancel: () => void;
}

export const SalaryDeductionUpload: React.FC<SalaryDeductionUploadProps> = ({
  onSuccess,
  onCancel,
}) => {
  const navigate = useNavigate();
  const [isUploading, setIsUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [isProcessingPreview, setIsProcessingPreview] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Fetch member names for validation
  const fetchMemberNames = async (memberNumbers: string[]) => {
    try {
      const response = await apiClient.post('/members/batch-lookup', {
        member_numbers: memberNumbers
      });
      return response.data.members || {};
    } catch (error) {
      console.error('Failed to fetch member names:', error);
      return {};
    }
  };

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      if (!file.name.endsWith('.csv')) {
        setError('Please select a CSV file');
        return;
      }
      setSelectedFile(file);
      setError(null);
    }
  };

  const processPreview = async () => {
    if (!selectedFile) return;

    setIsProcessingPreview(true);
    setError(null);

    try {
      const text = await selectedFile.text();
      const lines = text.split('\n').filter(line => line.trim());
      
      if (lines.length < 2) {
        setError('File must contain at least a header row and one data row');
        setIsProcessingPreview(false);
        return;
      }

      const headers = lines[0].split(',').map(h => h.trim().toLowerCase());
      const requiredHeaders = ['member_number', 'amount', 'source', 'reference'];
      
      // Validate headers
      const missingHeaders = requiredHeaders.filter(h => !headers.includes(h));
      if (missingHeaders.length > 0) {
        setError(`Missing required columns: ${missingHeaders.join(', ')}`);
        setIsProcessingPreview(false);
        return;
      }

      // Extract member numbers for batch lookup
      const memberNumbers: string[] = [];
      for (let i = 1; i < lines.length; i++) {
        const values = lines[i].split(',').map(v => v.trim());
        const memberNumber = values[headers.indexOf('member_number')] || '';
        if (memberNumber && memberNumber.startsWith('MEM')) {
          memberNumbers.push(memberNumber);
        }
      }

      // Fetch member names
      const memberNames = await fetchMemberNames(memberNumbers);

      const records: PreviewRecord[] = [];
      let validCount = 0;
      let warningCount = 0;
      let errorCount = 0;

      for (let i = 1; i < lines.length; i++) {
        const values = lines[i].split(',').map(v => v.trim());
        
        if (values.length !== headers.length) {
          records.push({
            row: i + 1,
            member_number: values[0] || '',
            member_name: undefined,
            amount: 0,
            source: '',
            reference: '',
            status: 'error',
            message: 'Invalid number of columns'
          });
          errorCount++;
          continue;
        }

        const memberNumber = values[headers.indexOf('member_number')] || '';
        const amountStr = values[headers.indexOf('amount')] || '0';
        const source = values[headers.indexOf('source')] || '';
        const reference = values[headers.indexOf('reference')] || '';

        let status: 'valid' | 'warning' | 'error' = 'valid';
        let message = '';

        // Validate member number and check if member exists
        if (!memberNumber || !memberNumber.startsWith('MEM')) {
          status = 'error';
          message = 'Invalid member number format';
        } else if (!memberNames[memberNumber]) {
          status = 'error';
          message = 'Member not found in system';
        }

        // Validate amount
        const amount = parseFloat(amountStr);
        if (isNaN(amount) || amount <= 0) {
          status = 'error';
          message = 'Invalid amount';
        } else if (amount < 1000) {
          status = status === 'error' ? 'error' : 'warning';
          message = message || 'Amount seems low (< UGX 1,000)';
        }

        // Validate source
        if (!source || !['salary', 'manual', 'adjustment'].includes(source.toLowerCase())) {
          status = 'error';
          message = 'Invalid source (must be: salary, manual, or adjustment)';
        }

        // Validate reference
        if (!reference) {
          status = status === 'error' ? 'error' : 'warning';
          message = message || 'Missing reference';
        }

        records.push({
          row: i + 1,
          member_number: memberNumber,
          member_name: memberNames[memberNumber],
          amount: amount,
          source: source,
          reference: reference,
          status: status,
          message: message
        });

        if (status === 'valid') validCount++;
        else if (status === 'warning') warningCount++;
        else errorCount++;
      }

      // Navigate to preview page with data
      navigate('/savings/deduction-preview', {
        state: {
          records,
          stats: {
            total: records.length,
            valid: validCount,
            warnings: warningCount,
            errors: errorCount
          },
          file: selectedFile
        }
      });

    } catch (err) {
      setError('Failed to process file. Please check the file format.');
    } finally {
      setIsProcessingPreview(false);
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-UG', {
      style: 'currency',
      currency: 'UGX',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'valid':
        return <span className="text-green-600">✅</span>;
      case 'warning':
        return <span className="text-yellow-600">⚠️</span>;
      case 'error':
        return <span className="text-red-600">❌</span>;
      default:
        return null;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'valid':
        return 'text-green-600 bg-green-50';
      case 'warning':
        return 'text-yellow-600 bg-yellow-50';
      case 'error':
        return 'text-red-600 bg-red-50';
      default:
        return 'text-gray-600 bg-gray-50';
    }
  };

  const downloadTemplate = () => {
    const template = 'member_number,amount,source,reference\n' +
      'MEM20240001,50000,salary,Jan-2024\n' +
      'MEM20240002,75000,salary,Jan-2024\n' +
      'MEM20240003,60000,salary,Jan-2024\n';
    
    const blob = new Blob([template], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'salary_deductions_template.csv';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  };

  const handleUpload = async () => {
    if (!selectedFile) {
      setError('Please select a file');
      return;
    }

    setIsUploading(true);
    setError(null);
    setUploadProgress(0);

    try {
      const formData = new FormData();
      formData.append('file', selectedFile);

      const response = await apiClient.post<{ data: UploadSummary }>(
        '/savings/upload-deductions',
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
          onUploadProgress: (progressEvent: any) => {
            const progress = Math.round(
              (progressEvent.loaded / progressEvent.total) * 100
            );
            setUploadProgress(progress);
          },
        }
      );

      onSuccess(response.data.data);
    } catch (err: any) {
      setError(
        err.response?.data?.message || 'Failed to upload file'
      );
    } finally {
      setIsUploading(false);
      setUploadProgress(0);
    }
  };

  return (
    <div className="space-y-4">
      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {error}
        </div>
      )}

      {/* File Upload Section */}
      <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
        <input
          ref={fileInputRef}
          type="file"
          accept=".csv"
          onChange={handleFileSelect}
          disabled={isUploading}
          className="hidden"
        />
        
        <div className="mb-4">
          <div className="text-5xl text-gray-400 mx-auto w-fit">📄</div>
        </div>

        <p className="text-gray-600 mb-2">
          {selectedFile ? selectedFile.name : 'Drag and drop your CSV file here, or click to select'}
        </p>

        <div className="flex justify-center gap-3">
          <button
            type="button"
            onClick={() => fileInputRef.current?.click()}
            disabled={isUploading}
            className="text-blue-600 hover:text-blue-800 font-medium disabled:opacity-50"
          >
            Select File
          </button>
          
          <span className="text-gray-300">|</span>
          
          <button
            type="button"
            onClick={() => navigate('/savings/deduction-history')}
            className="inline-flex items-center text-gray-600 hover:text-gray-800 font-medium text-sm"
          >
            <span className="mr-1">⏰</span>
            View History
          </button>
        </div>
      </div>

      {/* File Actions */}
      {selectedFile && (
        <div className="flex gap-2">
          <button
            type="button"
            onClick={processPreview}
            disabled={isProcessingPreview}
            className="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 disabled:opacity-50"
          >
            {isProcessingPreview ? (
              <span className="mr-2 animate-spin">🔄</span>
            ) : (
              <span className="mr-2">👁️</span>
            )}
            {isProcessingPreview ? 'Processing...' : 'Preview & Upload'}
          </button>
        </div>
      )}

      {/* Upload Progress */}
      {isUploading && (
        <div className="space-y-2">
          <div className="flex justify-between">
            <span className="text-sm font-medium text-gray-700">Upload Progress</span>
            <span className="text-sm font-medium text-gray-700">{uploadProgress}%</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div
              className="bg-blue-600 h-2 rounded-full transition-all duration-300"
              style={{ width: `${uploadProgress}%` }}
            />
          </div>
        </div>
      )}

      {/* Template Download */}
      <div className="border-t pt-4">
        <button
          type="button"
          onClick={downloadTemplate}
          disabled={isUploading}
          className="text-blue-600 hover:text-blue-800 font-medium disabled:opacity-50 text-sm"
        >
          ↓ Download CSV Template
        </button>
      </div>

      {/* Action Buttons */}
      <div className="flex justify-end gap-2 pt-4 border-t">
        <button
          type="button"
          onClick={onCancel}
          disabled={isUploading}
          className="px-3 py-2 text-gray-700 bg-gray-200 text-sm rounded hover:bg-gray-300 disabled:opacity-50"
        >
          Cancel
        </button>
      </div>
    </div>
  );
};
