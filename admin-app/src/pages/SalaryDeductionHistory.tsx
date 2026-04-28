import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { apiClient } from '../api/client';

interface UploadHistory {
  id: string;
  upload_type: string;
  file_name: string;
  total_records: number;
  successful_records: number;
  failed_records: number;
  total_amount: number;
  upload_date: string;
  uploaded_by: {
    name: string;
  };
  status: 'completed' | 'processing' | 'failed';
  bank_receipt?: string;
}

export const SalaryDeductionHistory: React.FC = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const [uploads, setUploads] = useState<UploadHistory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedPeriod, setSelectedPeriod] = useState('all');
  const [searchTerm, setSearchTerm] = useState('');

  // Check for success message from upload
  const uploadSuccess = location.state?.uploadSuccess;
  const uploadSummary = location.state?.summary;

  useEffect(() => {
    fetchUploadHistory();
  }, []);

  const fetchUploadHistory = async () => {
    try {
      setLoading(true);
      const response = await apiClient.get('/savings/upload-history', {
        params: {
          type: 'salary_deductions',
          period: selectedPeriod !== 'all' ? selectedPeriod : undefined,
          search: searchTerm || undefined,
        }
      });
      setUploads(response.data.data || []);
    } catch (err: any) {
      setError('Failed to fetch upload history');
      console.error('Failed to fetch upload history:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (selectedPeriod !== 'all' || searchTerm) {
      const timeoutId = setTimeout(() => {
        fetchUploadHistory();
      }, 500);
      return () => clearTimeout(timeoutId);
    }
  }, [selectedPeriod, searchTerm]);

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
      case 'completed':
        return <span className="text-green-600">✅</span>;
      case 'processing':
        return <span className="text-yellow-600">⏰</span>;
      case 'failed':
        return <span className="text-red-600">❌</span>;
      default:
        return <span className="text-gray-600">📄</span>;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed':
        return 'text-green-600 bg-green-100';
      case 'processing':
        return 'text-yellow-600 bg-yellow-100';
      case 'failed':
        return 'text-red-600 bg-red-100';
      default:
        return 'text-gray-600 bg-gray-100';
    }
  };

  const handleViewDetails = (uploadId: string) => {
    navigate(`/savings/deduction-history/${uploadId}`);
  };

  const handleDownloadReceipt = async (receiptPath: string, fileName: string) => {
    try {
      const response = await apiClient.get(`/savings/download-receipt/${receiptPath}`, {
        responseType: 'blob'
      });
      
      const blob = new Blob([response.data]);
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = fileName;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Failed to download receipt:', error);
    }
  };

  const totalUploads = uploads.length;
  const totalAmount = uploads.reduce((sum, upload) => sum + upload.total_amount, 0);
  const totalRecords = uploads.reduce((sum, upload) => sum + upload.successful_records, 0);

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 py-6">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="animate-pulse">
            <div className="h-8 bg-gray-200 rounded w-1/3 mb-6"></div>
            <div className="space-y-4">
              {[...Array(5)].map((_, i) => (
                <div key={i} className="h-20 bg-gray-200 rounded"></div>
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  }

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
                <h1 className="text-2xl font-bold text-gray-900">Salary Deduction History</h1>
                <p className="text-sm text-gray-600">View all previous salary deduction uploads</p>
              </div>
            </div>
          </div>
        </div>

        {/* Success Message */}
        {uploadSuccess && (
          <div className="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <div className="flex items-center">
              <span className="text-green-600 mr-2">✅</span>
              <div>
                <p className="font-medium">Upload completed successfully!</p>
                {uploadSummary && (
                  <p className="text-sm">
                    Processed {uploadSummary.successful} records successfully, {uploadSummary.failed} failed.
                  </p>
                )}
              </div>
            </div>
          </div>
        )}

        {error && (
          <div className="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {error}
          </div>
        )}

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div className="bg-white p-4 rounded-lg shadow border">
            <div className="flex items-center">
              <span className="text-3xl text-blue-600 mr-3">📄</span>
              <div>
                <p className="text-sm text-gray-500">Total Uploads</p>
                <p className="text-2xl font-bold text-gray-900">{totalUploads}</p>
              </div>
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow border">
            <div className="flex items-center">
              <span className="text-3xl text-green-600 mr-3">👤</span>
              <div>
                <p className="text-sm text-gray-500">Total Records</p>
                <p className="text-2xl font-bold text-gray-900">{totalRecords}</p>
              </div>
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow border">
            <div className="flex items-center">
              <span className="text-3xl text-purple-600 mr-3">💰</span>
              <div>
                <p className="text-sm text-gray-500">Total Amount</p>
                <p className="text-2xl font-bold text-gray-900">{formatCurrency(totalAmount)}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow border p-4 mb-6">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <input
                type="text"
                placeholder="Search by file name or uploader..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div>
              <select
                value={selectedPeriod}
                onChange={(e) => setSelectedPeriod(e.target.value)}
                className="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="all">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="quarter">This Quarter</option>
              </select>
            </div>
          </div>
        </div>

        {/* Upload History Table */}
        <div className="bg-white rounded-lg shadow border overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-200">
            <h3 className="text-lg font-medium text-gray-900">Upload History</h3>
          </div>
          
          {uploads.length === 0 ? (
            <div className="text-center py-12">
              <div className="text-5xl text-gray-400 mx-auto w-fit">📄</div>
              <h3 className="mt-2 text-sm font-medium text-gray-900">No uploads found</h3>
              <p className="mt-1 text-sm text-gray-500">
                {searchTerm || selectedPeriod !== 'all' 
                  ? 'Try adjusting your search or filter criteria.'
                  : 'No salary deduction uploads have been made yet.'
                }
              </p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">File Name</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Upload Date</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Uploaded By</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Records</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {uploads.map((upload) => (
                    <tr key={upload.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          {getStatusIcon(upload.status)}
                          <span className={`ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(upload.status)}`}>
                            {upload.status}
                          </span>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">{upload.file_name}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center text-sm text-gray-900">
                          <span className="mr-1 text-gray-400">📅</span>
                          {new Date(upload.upload_date).toLocaleDateString()}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {upload.uploaded_by.name}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">
                          <span className="text-green-600 font-medium">{upload.successful_records}</span>
                          {upload.failed_records > 0 && (
                            <span className="text-red-600"> / {upload.failed_records} failed</span>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {formatCurrency(upload.total_amount)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {upload.bank_receipt ? (
                          <button
                            onClick={() => handleDownloadReceipt(upload.bank_receipt!, `receipt_${upload.file_name}`)}
                            className="text-blue-600 hover:text-blue-900"
                          >
                            <span>📥</span>
                          </button>
                        ) : (
                          <span className="text-gray-400 text-sm">No receipt</span>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button
                          onClick={() => handleViewDetails(upload.id)}
                          className="text-blue-600 hover:text-blue-900 mr-3"
                        >
                          <span>👁️</span>
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};