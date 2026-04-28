import React, { useState, useRef } from 'react';
import { apiClient } from '../api/client';

interface UploadSummary {
  total_records: number;
  matched: number;
  unmatched: number;
  successful: number;
  failed: number;
  errors: Array<{
    row: number;
    error: string;
  }>;
  matched_transactions?: Array<{
    loan_number: string;
    member_name: string;
    amount: number;
  }>;
  unmatched_transactions?: Array<{
    row: number;
    loan_number: string;
    amount: number;
    reason: string;
  }>;
}

interface RepaymentUploadProps {
  onSuccess: (summary: UploadSummary) => void;
  onCancel: () => void;
}

export const RepaymentUpload: React.FC<RepaymentUploadProps> = ({
  onSuccess,
  onCancel,
}) => {
  const [isUploading, setIsUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

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

  const downloadTemplate = () => {
    const template = 'loan_number,amount,payment_date,reference\n' +
      'LOAN001,5000,2024-01-15,Salary-Jan\n' +
      'LOAN002,3000,2024-01-15,Salary-Jan\n';
    
    const blob = new Blob([template], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'loan_repayments_template.csv';
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
        '/loans/repayments/upload-deductions',
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
    <div>
      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {error}
        </div>
      )}

      <div className="mb-6">
        <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
          <input
            ref={fileInputRef}
            type="file"
            accept=".csv"
            onChange={handleFileSelect}
            disabled={isUploading}
            className="hidden"
          />
          
          <div className="mb-4">
            <svg
              className="mx-auto h-12 w-12 text-gray-400"
              stroke="currentColor"
              fill="none"
              viewBox="0 0 48 48"
            >
              <path
                d="M28 8H12a4 4 0 00-4 4v20a4 4 0 004 4h24a4 4 0 004-4V20m-8-12v12m0 0l-4-4m4 4l4-4"
                strokeWidth={2}
                strokeLinecap="round"
                strokeLinejoin="round"
              />
            </svg>
          </div>

          <p className="text-gray-600 mb-2">
            {selectedFile ? selectedFile.name : 'Drag and drop your CSV file here, or click to select'}
          </p>

          <button
            type="button"
            onClick={() => fileInputRef.current?.click()}
            disabled={isUploading}
            className="text-blue-600 hover:text-blue-800 font-medium disabled:opacity-50"
          >
            Select File
          </button>
        </div>
      </div>

      {isUploading && (
        <div className="mb-6">
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

      <div className="mb-6">
        <button
          type="button"
          onClick={downloadTemplate}
          disabled={isUploading}
          className="text-blue-600 hover:text-blue-800 font-medium disabled:opacity-50"
        >
          ↓ Download CSV Template
        </button>
      </div>

      <div className="flex justify-end gap-3">
        <button
          type="button"
          onClick={onCancel}
          disabled={isUploading}
          className="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 disabled:opacity-50"
        >
          Cancel
        </button>
        <button
          type="button"
          onClick={handleUpload}
          disabled={isUploading || !selectedFile}
          className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {isUploading ? 'Uploading...' : 'Upload'}
        </button>
      </div>
    </div>
  );
};
