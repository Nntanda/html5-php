import React, { useState, useEffect } from 'react';
import { apiClient } from '../api/client';
import { Modal } from '../components/Modal';
import { DepositForm } from '../components/DepositForm';
import { SalaryDeductionUpload } from '../components/SalaryDeductionUpload';

interface SavingsStats {
  total_accounts: number;
  active_accounts: number;
  pending_accounts: number;
  total_balance: number;
  average_balance: number;
  total_deposits_today: number;
  total_withdrawals_today: number;
}

interface SavingsAccount {
  id: string;
  account_number: string;
  balance: number;
  status: 'pending' | 'active' | 'suspended' | 'closed';
  member: {
    id: string;
    member_number: string;
    first_name: string;
    last_name: string;
    category: string;
  };
  created_at: string;
}

interface PendingTransaction {
  id: string;
  type: string;
  amount: number;
  status: string;
  transaction_date: string;
  member: {
    member_number: string;
    first_name: string;
    last_name: string;
  };
  evidence_file?: string;
}

export const Savings: React.FC = () => {
  const [stats, setStats] = useState<SavingsStats | null>(null);
  const [accounts, setAccounts] = useState<SavingsAccount[]>([]);
  const [pendingTransactions, setPendingTransactions] = useState<PendingTransaction[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [showDepositModal, setShowDepositModal] = useState(false);
  const [showUploadModal, setShowUploadModal] = useState(false);
  const [showPendingModal, setShowPendingModal] = useState(false);
  const [showDownloadModal, setShowDownloadModal] = useState(false);
  const [showAccountDetailsModal, setShowAccountDetailsModal] = useState(false);
  const [selectedAccount, setSelectedAccount] = useState<SavingsAccount | null>(null);

  // Auto-dismiss messages after 5 seconds
  useEffect(() => {
    if (successMessage) {
      const timer = setTimeout(() => setSuccessMessage(null), 5000);
      return () => clearTimeout(timer);
    }
  }, [successMessage]);

  useEffect(() => {
    if (error) {
      const timer = setTimeout(() => setError(null), 8000);
      return () => clearTimeout(timer);
    }
  }, [error]);

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      setLoading(true);
      setError(null);
      const [statsRes, accountsRes, pendingRes] = await Promise.all([
        apiClient.get('/savings/stats'),
        apiClient.get('/savings/accounts'),
        apiClient.get('/savings/transactions/pending')
      ]);
      
      setStats(statsRes.data);
      setAccounts(accountsRes.data.data || []);
      setPendingTransactions(pendingRes.data.data || []);
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 
                          err.message || 
                          'Unable to load savings data. Please check your connection and try again.';
      setError(errorMessage);
      console.error('Failed to fetch savings data:', err);
    } finally {
      setLoading(false);
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

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'active': return 'text-green-600 bg-green-100';
      case 'pending': return 'text-yellow-600 bg-yellow-100';
      case 'suspended': return 'text-red-600 bg-red-100';
      case 'closed': return 'text-gray-600 bg-gray-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const getCategoryLabel = (category: string) => {
    const labels = {
      staff: 'Staff',
      act_program: 'ACT Program',
      nursing_school: 'Nursing School',
      hc_staff: 'HC Staff',
      non_hospital_staff: 'Non Hospital Staff'
    };
    return labels[category as keyof typeof labels] || category;
  };

  const filteredAccounts = accounts.filter(account => {
    const matchesSearch = 
      account.member.member_number.toLowerCase().includes(searchTerm.toLowerCase()) ||
      `${account.member.first_name} ${account.member.last_name}`.toLowerCase().includes(searchTerm.toLowerCase()) ||
      account.account_number.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = statusFilter === 'all' || account.status === statusFilter;
    
    return matchesSearch && matchesStatus;
  });

  const handleApproveTransaction = async (transactionId: string) => {
    setError(null);
    try {
      await apiClient.post(`/savings/transactions/${transactionId}/approve`);
      setSuccessMessage('✓ Transaction approved successfully!');
      fetchData(); // Refresh data
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 
                          'Failed to approve transaction. Please try again.';
      setError(errorMessage);
      console.error('Failed to approve transaction:', err);
    }
  };

  const handleRejectTransaction = async (transactionId: string) => {
    const reason = prompt('Please provide a reason for rejection:');
    if (!reason) return;

    setError(null);
    try {
      await apiClient.post(`/savings/transactions/${transactionId}/reject`, {
        rejection_reason: reason
      });
      setSuccessMessage('✓ Transaction rejected successfully.');
      fetchData(); // Refresh data
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 
                          'Failed to reject transaction. Please try again.';
      setError(errorMessage);
      console.error('Failed to reject transaction:', err);
    }
  };

  const handleDownloadSalaryDeduction = () => {
    setShowDownloadModal(true);
  };

  const handleDownloadExcel = async () => {
    setError(null);
    try {
      const response = await apiClient.get('/savings/salary-deduction-report/excel', {
        responseType: 'blob'
      });
      
      const blob = new Blob([response.data], { 
        type: 'text/csv' 
      });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `salary_deduction_report_${new Date().toISOString().split('T')[0]}.csv`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
      setShowDownloadModal(false);
      setSuccessMessage('✓ Excel report downloaded successfully!');
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 
                          'Failed to download Excel report. Please try again.';
      setError(errorMessage);
      console.error('Failed to download Excel report:', err);
    }
  };

  const handleDownloadPDF = async () => {
    setError(null);
    try {
      const response = await apiClient.get('/savings/salary-deduction-report/pdf', {
        responseType: 'blob'
      });
      
      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `salary_deduction_report_${new Date().toISOString().split('T')[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
      setShowDownloadModal(false);
      setSuccessMessage('✓ PDF report downloaded successfully!');
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 
                          'Failed to download PDF report. Please try again.';
      setError(errorMessage);
      console.error('Failed to download PDF report:', err);
    }
  };

  const handleViewAccountDetails = (account: SavingsAccount) => {
    setSelectedAccount(account);
    setShowAccountDetailsModal(true);
  };

  if (loading) {
    return (
      <div className="space-y-4">
        <div className="sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3">
          <div className="animate-pulse">
            <div className="h-6 bg-gray-200 rounded w-1/4"></div>
          </div>
        </div>
        <div className="px-4">
          <div className="animate-pulse">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
              {[...Array(4)].map((_, i) => (
                <div key={i} className="h-16 bg-gray-200 rounded"></div>
              ))}
            </div>
            <div className="h-64 bg-gray-200 rounded"></div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Sticky Header */}
      <div className="sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <h1 className="text-xl font-semibold text-gray-900">Savings Management</h1>
          <div className="flex flex-col sm:flex-row gap-1.5 mt-2 sm:mt-0">
            <button
              onClick={() => setShowDepositModal(true)}
              className="inline-flex items-center px-2.5 py-1 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 focus:outline-none focus:ring-1 focus:ring-green-500"
            >
              <span className="mr-1">💰</span>
              Record Deposit
            </button>
            <button
              onClick={() => setShowPendingModal(true)}
              className="inline-flex items-center px-2.5 py-1 bg-yellow-600 text-white text-xs font-medium rounded hover:bg-yellow-700 focus:outline-none focus:ring-1 focus:ring-yellow-500"
            >
              <span className="mr-1">⏰</span>
              Pending Approvals
              <span className="ml-1.5 bg-yellow-800 text-yellow-100 text-xs px-1 py-0.5 rounded-full">
                {pendingTransactions.length}
              </span>
            </button>
            <button
              onClick={() => setShowUploadModal(true)}
              className="inline-flex items-center px-2.5 py-1 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <span className="mr-1">📤</span>
              Upload Salary Deductions
            </button>
            <button
              onClick={handleDownloadSalaryDeduction}
              className="inline-flex items-center px-2.5 py-1 bg-purple-600 text-white text-xs font-medium rounded hover:bg-purple-700 focus:outline-none focus:ring-1 focus:ring-purple-500"
            >
              <span className="mr-1">📥</span>
              Download Salary Deduction
            </button>
          </div>
        </div>
      </div>

      <div className="px-4 space-y-4">

      {/* Success Message */}
      {successMessage && (
        <div className="bg-green-50 border-l-4 border-green-500 p-4 rounded-md shadow-sm animate-fade-in">
          <div className="flex items-start">
            <div className="flex-shrink-0">
              <svg className="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
              </svg>
            </div>
            <div className="ml-3 flex-1">
              <p className="text-sm font-medium text-green-800">{successMessage}</p>
            </div>
            <button
              onClick={() => setSuccessMessage(null)}
              className="ml-3 flex-shrink-0 text-green-500 hover:text-green-700"
            >
              <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      )}

      {/* Error Message */}
      {error && (
        <div className="bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm animate-fade-in">
          <div className="flex items-start">
            <div className="flex-shrink-0">
              <svg className="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
              </svg>
            </div>
            <div className="ml-3 flex-1">
              <p className="text-sm font-medium text-red-800">{error}</p>
            </div>
            <button
              onClick={() => setError(null)}
              className="ml-3 flex-shrink-0 text-red-500 hover:text-red-700"
            >
              <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      )}

      {/* Statistics Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
          <div className="bg-white p-3 rounded-lg shadow border">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <span className="text-2xl text-green-600">💰</span>
              </div>
              <div className="ml-3">
                <p className="text-xs font-medium text-gray-500">Total Balance</p>
                <p className="text-base font-semibold text-gray-900">
                  {formatCurrency(stats.total_balance)}
                </p>
              </div>
            </div>
          </div>

          <div className="bg-white p-3 rounded-lg shadow border">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <span className="text-2xl text-blue-600">👥</span>
              </div>
              <div className="ml-3">
                <p className="text-xs font-medium text-gray-500">Active Accounts</p>
                <p className="text-base font-semibold text-gray-900">
                  {stats.active_accounts} / {stats.total_accounts}
                </p>
              </div>
            </div>
          </div>

          <div className="bg-white p-3 rounded-lg shadow border">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <span className="text-2xl text-green-600">↗️</span>
              </div>
              <div className="ml-3">
                <p className="text-xs font-medium text-gray-500">Deposits Today</p>
                <p className="text-base font-semibold text-gray-900">
                  {formatCurrency(stats.total_deposits_today)}
                </p>
              </div>
            </div>
          </div>

          <div className="bg-white p-3 rounded-lg shadow border">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <span className="text-2xl text-red-600">↘️</span>
              </div>
              <div className="ml-3">
                <p className="text-xs font-medium text-gray-500">Withdrawals Today</p>
                <p className="text-base font-semibold text-gray-900">
                  {formatCurrency(stats.total_withdrawals_today)}
                </p>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Members Savings Table */}
      <div className="bg-white shadow rounded-lg">
        <div className="px-4 py-3 border-b border-gray-200">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <h2 className="text-base font-medium text-gray-900">Members Savings Accounts</h2>
            <div className="flex flex-col sm:flex-row gap-2 mt-3 sm:mt-0">
              <div className="relative">
                <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">🔍</span>
                <input
                  type="text"
                  placeholder="Search members..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-9 pr-3 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                />
              </div>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="px-3 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
              >
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="suspended">Suspended</option>
                <option value="closed">Closed</option>
              </select>
            </div>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Member
                </th>
                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Account Number
                </th>
                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Category
                </th>
                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Balance
                </th>
                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Created
                </th>
                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredAccounts.map((account) => (
                <tr key={account.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 whitespace-nowrap">
                    <div>
                      <div className="text-sm font-medium text-gray-900">
                        {account.member.first_name} {account.member.last_name}
                      </div>
                      <div className="text-xs text-gray-500">
                        {account.member.member_number}
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                    {account.account_number}
                  </td>
                  <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                    {getCategoryLabel(account.member.category)}
                  </td>
                  <td className="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                    {formatCurrency(account.balance)}
                  </td>
                  <td className="px-4 py-3 whitespace-nowrap">
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(account.status)}`}>
                      {account.status}
                    </span>
                  </td>
                  <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    {new Date(account.created_at).toLocaleDateString()}
                  </td>
                  <td className="px-4 py-3 whitespace-nowrap text-sm font-medium">
                    <button 
                      onClick={() => handleViewAccountDetails(account)}
                      className="text-blue-600 hover:text-blue-900 mr-3"
                      title="View Account Details"
                    >
                      <span>👁️</span>
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {filteredAccounts.length === 0 && (
          <div className="text-center py-8">
            <div className="text-4xl text-gray-400 mx-auto w-fit">👥</div>
            <h3 className="mt-2 text-sm font-medium text-gray-900">No accounts found</h3>
            <p className="mt-1 text-sm text-gray-500">
              {searchTerm || statusFilter !== 'all' 
                ? 'Try adjusting your search or filter criteria.'
                : 'No savings accounts have been created yet.'
              }
            </p>
          </div>
        )}
      </div>
      </div>

      {/* Pending Approvals Modal */}
      <Modal
        isOpen={showPendingModal}
        onClose={() => setShowPendingModal(false)}
        title="Pending Approvals"
        size="lg"
      >
        <div className="space-y-3">
          {pendingTransactions.length === 0 ? (
            <div className="text-center py-6">
              <div className="text-4xl text-gray-400 mx-auto w-fit">⏰</div>
              <h3 className="mt-2 text-sm font-medium text-gray-900">No pending approvals</h3>
              <p className="mt-1 text-sm text-gray-500">All transactions have been processed.</p>
            </div>
          ) : (
            pendingTransactions.map((transaction) => (
              <div key={transaction.id} className="border rounded-lg p-3">
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="text-sm font-medium text-gray-900">
                      {transaction.member.first_name} {transaction.member.last_name}
                    </h4>
                    <p className="text-sm text-gray-500">
                      {transaction.member.member_number} • {transaction.type} • {formatCurrency(transaction.amount)}
                    </p>
                    <p className="text-xs text-gray-400">
                      {new Date(transaction.transaction_date).toLocaleDateString()}
                    </p>
                  </div>
                  <div className="flex space-x-2">
                    <button
                      onClick={() => handleApproveTransaction(transaction.id)}
                      className="inline-flex items-center px-2 py-1 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700"
                    >
                      <span className="mr-1">✅</span>
                      Approve
                    </button>
                    <button
                      onClick={() => handleRejectTransaction(transaction.id)}
                      className="inline-flex items-center px-2 py-1 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700"
                    >
                      <span className="mr-1">❌</span>
                      Reject
                    </button>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      </Modal>

      {/* Deposit Modal */}
      <Modal
        isOpen={showDepositModal}
        onClose={() => setShowDepositModal(false)}
        title="Record Deposit"
      >
        <DepositForm
          onSubmit={async () => {
            setShowDepositModal(false);
            fetchData();
          }}
          onCancel={() => setShowDepositModal(false)}
        />
      </Modal>

      {/* Upload Modal */}
      <Modal
        isOpen={showUploadModal}
        onClose={() => setShowUploadModal(false)}
        title="Upload Salary Deductions"
      >
        <SalaryDeductionUpload
          onSuccess={() => {
            setShowUploadModal(false);
            fetchData();
          }}
          onCancel={() => setShowUploadModal(false)}
        />
      </Modal>

      {/* Download Modal */}
      <Modal
        isOpen={showDownloadModal}
        onClose={() => setShowDownloadModal(false)}
        title="Download Salary Deduction Report"
      >
        <div className="space-y-4">
          <p className="text-sm text-gray-600">
            Choose the format for downloading the salary deduction report. The report includes member details, 
            account numbers, deduction amounts, and categories grouped by staff type.
          </p>
          
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
              onClick={handleDownloadExcel}
              className="flex items-center justify-center px-3 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700 focus:outline-none focus:ring-1 focus:ring-green-500"
            >
              <span className="mr-1.5">📥</span>
              Download Excel
            </button>
            
            <button
              onClick={handleDownloadPDF}
              className="flex items-center justify-center px-3 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700 focus:outline-none focus:ring-1 focus:ring-red-500"
            >
              <span className="mr-1.5">📥</span>
              Download PDF
            </button>
          </div>
          
          <div className="mt-4 p-3 bg-blue-50 rounded-md">
            <h4 className="text-sm font-medium text-blue-900 mb-2">Report Contents:</h4>
            <ul className="text-sm text-blue-800 space-y-1">
              <li>• Member Number & Name</li>
              <li>• Account Number</li>
              <li>• Monthly Deduction Amount</li>
              <li>• Category Group</li>
              <li>• Signature sections (PDF only)</li>
            </ul>
          </div>
          
          <div className="flex justify-end gap-2 mt-4">
            <button
              onClick={() => setShowDownloadModal(false)}
              className="px-3 py-1.5 text-gray-700 bg-gray-200 rounded text-sm hover:bg-gray-300"
            >
              Cancel
            </button>
          </div>
        </div>
      </Modal>

      {/* Account Details Modal */}
      <Modal
        isOpen={showAccountDetailsModal}
        onClose={() => {
          setShowAccountDetailsModal(false);
          setSelectedAccount(null);
        }}
        title="Account Details"
        size="lg"
      >
        {selectedAccount && (
          <div className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <h3 className="text-sm font-medium text-gray-500">Member Information</h3>
                <div className="mt-2 space-y-2">
                  <div>
                    <p className="text-xs text-gray-500">Name</p>
                    <p className="text-sm font-medium text-gray-900">
                      {selectedAccount.member.first_name} {selectedAccount.member.last_name}
                    </p>
                  </div>
                  <div>
                    <p className="text-xs text-gray-500">Member Number</p>
                    <p className="text-sm font-medium text-gray-900">{selectedAccount.member.member_number}</p>
                  </div>
                  <div>
                    <p className="text-xs text-gray-500">Category</p>
                    <p className="text-sm font-medium text-gray-900">{getCategoryLabel(selectedAccount.member.category)}</p>
                  </div>
                </div>
              </div>

              <div>
                <h3 className="text-sm font-medium text-gray-500">Account Information</h3>
                <div className="mt-2 space-y-2">
                  <div>
                    <p className="text-xs text-gray-500">Account Number</p>
                    <p className="text-sm font-medium text-gray-900">{selectedAccount.account_number}</p>
                  </div>
                  <div>
                    <p className="text-xs text-gray-500">Balance</p>
                    <p className="text-lg font-bold text-green-600">{formatCurrency(selectedAccount.balance)}</p>
                  </div>
                  <div>
                    <p className="text-xs text-gray-500">Status</p>
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(selectedAccount.status)}`}>
                      {selectedAccount.status}
                    </span>
                  </div>
                  <div>
                    <p className="text-xs text-gray-500">Created Date</p>
                    <p className="text-sm font-medium text-gray-900">
                      {new Date(selectedAccount.created_at).toLocaleDateString()}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="flex justify-end gap-2 pt-4 border-t">
              <button
                onClick={() => {
                  setShowAccountDetailsModal(false);
                  setSelectedAccount(null);
                }}
                className="px-4 py-2 text-gray-700 bg-gray-200 rounded text-sm hover:bg-gray-300"
              >
                Close
              </button>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
};