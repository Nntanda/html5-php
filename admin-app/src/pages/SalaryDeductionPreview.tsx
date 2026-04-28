import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { apiClient } from '../api/client';

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

interface PreviewData {
  records: PreviewRecord[];
  stats: {
    total: number;
    valid: number;
    warnings: number;
    errors: number;
  };
  file: File;
}

export const SalaryDeductionPreview: React.FC = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const previewData = location.state as PreviewData;

  const [isUploading, setIsUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [error, setError] = useState<string | null>(null);
  const [bankReceipt, setBankReceipt] = useState<File | null>(null);

  useEffect(() => {
    if (!previewData) {
      navigate('/savings');
    }
  }, [previewData, navigate]);

  if (!previewData) {
    return null;
  }

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

  const handleUpload = async () => {
    setIsUploading(true);
    setError(null);
    setUploadProgress(0);

    try {
      const formData = new FormData();
      formData.append('file', previewData.file);
      if (bankReceipt) {
        formData.append('bank_receipt', bankReceipt);
      }

      const response = await apiClient.post('/savings/upload-deductions', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        onUploadProgress: (progressEvent: any) => {
          const progress = Math.round(
            (progressEvent.loaded / progressEvent.total) * 100
          );
          setUploadProgress(progress);
        },
      });

      // Navigate to history page with success message
      navigate('/savings/deduction-history', { 
        state: { 
          uploadSuccess: true, 
          summary: response.data.data 
        } 
      });

    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to upload file');
    } finally {
      setIsUploading(false);
      setUploadProgress(0);
    }
  };

  const totalAmount = previewData.records
    .filter(record => record.status !== 'error')
    .reduce((sum, record) => sum + record.amount, 0);

  return (
    <div className="min-h-screen bg-gray-50 py-6">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <button
                onClick={() => navigate('/savings')}
                className="mr-4 p-2 text-gray-600 hover:text-gray-900 rounded-md hover:bg-gray-100"
              >
                <span>←</span>
              </button>
              <div>
                <h1 className="text-2xl font-bold text-gray-900">Salary Deduction Preview</h1>
                <p className="text-sm text-gray-600">Review data before uploading</p>
              </div>
            </div>
          </div>
        </div>

        {error && (
          <div className="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {error}
          </div>
        )}

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div className="bg-white p-4 rounded-lg shadow border">
            <div className="text-center">
              <div className="text-2xl font-bold text-gray-900">{previewData.stats.total}</div>
              <div className="text-sm text-gray-500">Total Records</div>
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow border">
            <div className="text-center">
              <div className="text-2xl font-bold text-green-600">{previewData.stats.valid}</div>
              <div className="text-sm text-gray-500">Valid</div>
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow border">
            <div className="text-center">
              <div className="text-2xl font-bold text-yellow-600">{previewData.stats.warnings}</div>
              <div className="text-sm text-gray-500">Warnings</div>
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow border">
            <div className="text-center">
              <div className="text-2xl font-bold text-red-600">{previewData.stats.errors}</div>
              <div className="text-sm text-gray-500">Errors</div>
            </div>
          </div>
        </div>

        {/* Total Amount */}
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-lg font-medium text-blue-900">Total Deduction Amount</h3>
              <p className="text-sm text-blue-700">Sum of all valid and warning records</p>
            </div>
            <div className="text-2xl font-bold text-blue-900">
              {formatCurrency(totalAmount)}
            </div>
          </div>
        </div>

        {/* Bank Receipt Upload */}
        <div className="bg-white rounded-lg shadow border p-6 mb-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Bank Receipt (Optional)</h3>
          <div className="border-2 border-dashed border-gray-300 rounded-lg p-4">
            <input
              type="file"
              accept=".jpg,.jpeg,.png,.pdf"
              onChange={(e) => setBankReceipt(e.target.files?.[0] || null)}
              className="hidden"
              id="bank-receipt-upload"
            />
            <label
              htmlFor="bank-receipt-upload"
              className="cursor-pointer flex flex-col items-center"
            >
              <div className="text-3xl text-gray-400 mb-2">📤</div>
              <p className="text-sm text-gray-600">
                {bankReceipt ? bankReceipt.name : 'Upload bank receipt or proof of salary deduction'}
              </p>
              <p className="text-xs text-gray-500 mt-1">
                Supported formats: JPG, PNG, PDF (Max 2MB)
              </p>
            </label>
          </div>
          {bankReceipt && (
            <div className="mt-2 flex items-center justify-between bg-green-50 p-2 rounded">
              <span className="text-sm text-green-700">✓ {bankReceipt.name}</span>
              <button
                onClick={() => setBankReceipt(null)}
                className="text-red-600 hover:text-red-800"
              >
                <span className="text-red-600">❌</span>
              </button>
            </div>
          )}
        </div>

        {/* Preview Table */}
        <div className="bg-white rounded-lg shadow border overflow-hidden mb-6">
          <div className="px-6 py-4 border-b border-gray-200">
            <h3 className="text-lg font-medium text-gray-900">Data Preview</h3>
          </div>
          <div className="overflow-x-auto max-h-96">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50 sticky top-0">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Row</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member Number</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member Name</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {previewData.records.map((record, index) => (
                  <tr key={index} className={`${getStatusColor(record.status)}`}>
                    <td className="px-4 py-3 text-sm text-gray-900">{record.row}</td>
                    <td className="px-4 py-3">
                      <div className="flex items-center">
                        {getStatusIcon(record.status)}
                      </div>
                    </td>
                    <td className="px-4 py-3 text-sm text-gray-900">{record.member_number}</td>
                    <td className="px-4 py-3 text-sm text-gray-900">
                      {record.member_name || (record.status === 'error' ? 'Not Found' : 'Loading...')}
                    </td>
                    <td className="px-4 py-3 text-sm text-gray-900">
                      {record.amount > 0 ? formatCurrency(record.amount) : 'Invalid'}
                    </td>
                    <td className="px-4 py-3 text-sm text-gray-900">{record.source}</td>
                    <td className="px-4 py-3 text-sm text-gray-900">{record.reference}</td>
                    <td className="px-4 py-3 text-sm text-gray-600">{record.message || 'OK'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Upload Progress */}
        {isUploading && (
          <div className="bg-white rounded-lg shadow border p-6 mb-6">
            <div className="flex justify-between mb-2">
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

        {/* Action Buttons */}
        <div className="flex justify-between items-center bg-white rounded-lg shadow border p-6">
          <button
            onClick={() => navigate('/savings')}
            disabled={isUploading}
            className="inline-flex items-center px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 disabled:opacity-50"
          >
            <span className="mr-2">←</span>
            Back to Upload
          </button>

          <div className="flex gap-3">
            <button
              onClick={() => navigate('/savings/deduction-history')}
              className="inline-flex items-center px-4 py-2 text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200"
            >
              View History
            </button>
            <button
              onClick={handleUpload}
              disabled={isUploading || previewData.stats.errors > 0}
              className="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
            >
              {isUploading ? (
                <>
                  <span className="mr-2 animate-spin">🔄</span>
                  Uploading...
                </>
              ) : (
                <>
                  <span className="mr-2">📤</span>
                  Confirm Upload
                </>
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};