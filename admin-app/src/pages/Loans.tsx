import React, { useState, useEffect } from 'react';
import { apiClient } from '../api/client';
import { useAuthStore } from '../store/authStore';
import { Loan } from '../types';
import { Modal } from '../components/Modal';
import { LoanApplicationForm } from '../components/LoanApplicationForm';
import { LoanApprovalForm } from '../components/LoanApprovalForm';
import { LoanDisbursementForm } from '../components/LoanDisbursementForm';
import { LoanCalculator } from '../components/LoanCalculator';
import { BulkLoanActionForm } from '../components/BulkLoanActionForm';

interface LoanWithDetails extends Loan {
  member_name?: string;
  guarantor_count?: number;
  outstanding_balance?: number;
  next_payment_date?: string;
}

type TabType = 'applications' | 'approval' | 'disbursement' | 'tracking';

export const Loans: React.FC = () => {
  const { user } = useAuthStore();
  const [activeTab, setActiveTab] = useState<TabType>('applications');
  const [loans, setLoans] = useState<LoanWithDetails[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState<string>('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isDetailsModalOpen, setIsDetailsModalOpen] = useState(false);
  const [isActionModalOpen, setIsActionModalOpen] = useState(false);
  const [isCalculatorModalOpen, setIsCalculatorModalOpen] = useState(false);
  const [selectedLoan, setSelectedLoan] = useState<LoanWithDetails | null>(null);
  const [selectedLoans, setSelectedLoans] = useState<number[]>([]);
  const [isBulkActionModalOpen, setIsBulkActionModalOpen] = useState(false);
  const [bulkAction, setBulkAction] = useState<'approve' | 'reject' | null>(null);

  useEffect(() => {
    fetchLoans(1);
  }, [activeTab, searchTerm, filterStatus]);

  const fetchLoans = async (page = 1) => {
    setIsLoading(true);
    setError(null);
    try {
      const params: any = { page, per_page: 10 };
      if (searchTerm) params.search = searchTerm;

      let endpoint = '/loans';
      if (activeTab === 'approval') {
        endpoint = '/loans?status=approved&pending_approval=true';
      } else if (activeTab === 'disbursement') {
        endpoint = '/loans?status=approved';
      } else if (activeTab === 'applications') {
        params.status = 'pending';
      } else if (activeTab === 'tracking') {
        if (filterStatus) params.status = filterStatus;
      }

      const response = await apiClient.get<{
        data: LoanWithDetails[];
        meta: { current_page: number; last_page: number };
      }>(endpoint, { params });

      setLoans(response.data.data);
      setTotalPages(response.data.meta.last_page);
      setCurrentPage(page);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to fetch loans');
    } finally {
      setIsLoading(false);
    }
  };

  const handleCreateLoan = async (data: any) => {
    try {
      await apiClient.post('/loans/apply', data);
      setIsCreateModalOpen(false);
      fetchLoans(currentPage);
    } catch (err: any) {
      throw new Error(err.response?.data?.message || 'Failed to submit loan application');
    }
  };

  const handleApproveLoan = async (action: 'approve' | 'reject', notes?: string) => {
    if (!selectedLoan) return;
    try {
      const endpoint = action === 'approve' ? 'approve' : 'reject';
      await apiClient.put(`/loans/${selectedLoan.id}/${endpoint}`, { notes });
      setIsActionModalOpen(false);
      setSelectedLoan(null);
      fetchLoans(currentPage);
    } catch (err: any) {
      throw new Error(err.response?.data?.message || `Failed to ${action} loan`);
    }
  };

  const handleDisburseLoan = async () => {
    if (!selectedLoan) return;
    try {
      await apiClient.put(`/loans/${selectedLoan.id}/disburse`);
      setIsActionModalOpen(false);
      setSelectedLoan(null);
      fetchLoans(currentPage);
    } catch (err: any) {
      throw new Error(err.response?.data?.message || 'Failed to disburse loan');
    }
  };

  const openLoanDetails = async (loan: LoanWithDetails) => {
    try {
      const response = await apiClient.get<{ loan: LoanWithDetails }>(
        `/loans/${loan.id}`
      );
      setSelectedLoan(response.data.loan);
      setIsDetailsModalOpen(true);
    } catch (err: any) {
      setError('Failed to fetch loan details');
    }
  };

  const openActionModal = (loan: LoanWithDetails) => {
    setSelectedLoan(loan);
    setIsActionModalOpen(true);
  };

  const handleBulkAction = async (action: 'approve' | 'reject', data: any) => {
    try {
      const endpoint = action === 'approve' ? '/loans/bulk-approve' : '/loans/bulk-reject';
      await apiClient.post(endpoint, {
        loan_ids: selectedLoans,
        ...data,
      });
      setIsBulkActionModalOpen(false);
      setSelectedLoans([]);
      setBulkAction(null);
      fetchLoans(currentPage);
    } catch (err: any) {
      throw new Error(err.response?.data?.message || `Failed to ${action} loans`);
    }
  };

  const toggleLoanSelection = (loanId: number) => {
    setSelectedLoans(prev => 
      prev.includes(loanId) 
        ? prev.filter(id => id !== loanId)
        : [...prev, loanId]
    );
  };

  const selectAllLoans = () => {
    if (selectedLoans.length === loans.length) {
      setSelectedLoans([]);
    } else {
      setSelectedLoans(loans.map(loan => loan.id));
    }
  };

  const openBulkAction = (action: 'approve' | 'reject') => {
    setBulkAction(action);
    setIsBulkActionModalOpen(true);
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-UG', {
      style: 'currency',
      currency: 'UGX',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value);
  };

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      pending: 'bg-yellow-100 text-yellow-800',
      approved: 'bg-blue-100 text-blue-800',
      rejected: 'bg-red-100 text-red-800',
      disbursed: 'bg-green-100 text-green-800',
      active: 'bg-green-100 text-green-800',
      paid: 'bg-gray-100 text-gray-800',
      overdue: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  const canViewApprovalQueue = user?.role === 'LoanOfficer' || user?.role === 'SuperAdmin';
  const canViewDisbursementQueue = user?.role === 'Accountant' || user?.role === 'SuperAdmin';

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-800">Loans</h1>
        <div className="flex gap-3">
          <button
            onClick={() => setIsCalculatorModalOpen(true)}
            className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700"
          >
            Calculator
          </button>
          {activeTab === 'applications' && (
            <button
              onClick={() => setIsCreateModalOpen(true)}
              className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
            >
              + New Application
            </button>
          )}
        </div>
      </div>

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {error}
        </div>
      )}

      <div className="bg-white rounded-lg shadow mb-6">
        <div className="flex border-b">
          <button
            onClick={() => {
              setActiveTab('applications');
              setCurrentPage(1);
            }}
            className={`px-6 py-3 font-medium ${
              activeTab === 'applications'
                ? 'border-b-2 border-blue-600 text-blue-600'
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Applications
          </button>
          {canViewApprovalQueue && (
            <button
              onClick={() => {
                setActiveTab('approval');
                setCurrentPage(1);
              }}
              className={`px-6 py-3 font-medium ${
                activeTab === 'approval'
                  ? 'border-b-2 border-blue-600 text-blue-600'
                  : 'text-gray-600 hover:text-gray-800'
              }`}
            >
              Approval Queue
            </button>
          )}
          {canViewDisbursementQueue && (
            <button
              onClick={() => {
                setActiveTab('disbursement');
                setCurrentPage(1);
              }}
              className={`px-6 py-3 font-medium ${
                activeTab === 'disbursement'
                  ? 'border-b-2 border-blue-600 text-blue-600'
                  : 'text-gray-600 hover:text-gray-800'
              }`}
            >
              Disbursement Queue
            </button>
          )}
          <button
            onClick={() => {
              setActiveTab('tracking');
              setCurrentPage(1);
            }}
            className={`px-6 py-3 font-medium ${
              activeTab === 'tracking'
                ? 'border-b-2 border-blue-600 text-blue-600'
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Tracking
          </button>
        </div>

        <div className="p-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <input
              type="text"
              placeholder="Search by loan number or member..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            {activeTab === 'tracking' && (
              <select
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="paid">Paid</option>
                <option value="overdue">Overdue</option>
              </select>
            )}
          </div>

          {/* Bulk Actions */}
          {activeTab === 'approval' && selectedLoans.length > 0 && (
            <div className="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg flex justify-between items-center">
              <span className="text-sm text-blue-800">
                {selectedLoans.length} loan(s) selected
              </span>
              <div className="flex gap-2">
                <button
                  onClick={() => openBulkAction('approve')}
                  className="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700"
                >
                  Bulk Approve
                </button>
                <button
                  onClick={() => openBulkAction('reject')}
                  className="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700"
                >
                  Bulk Reject
                </button>
                <button
                  onClick={() => setSelectedLoans([])}
                  className="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700"
                >
                  Clear
                </button>
              </div>
            </div>
          )}
        </div>
      </div>

      <div className="bg-white rounded-lg shadow overflow-hidden">
        {isLoading ? (
          <div className="p-6 text-center text-gray-500">Loading loans...</div>
        ) : loans.length === 0 ? (
          <div className="p-6 text-center text-gray-500">No loans found</div>
        ) : (
          <>
            <table className="w-full">
              <thead className="bg-gray-50 border-b">
                <tr>
                  {activeTab === 'approval' && (
                    <th className="px-6 py-3 text-left">
                      <input
                        type="checkbox"
                        checked={selectedLoans.length === loans.length && loans.length > 0}
                        onChange={selectAllLoans}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                    </th>
                  )}
                  <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                    Loan Number
                  </th>
                  <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                    Member
                  </th>
                  <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                    Amount
                  </th>
                  <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                    Status
                  </th>
                  {activeTab === 'tracking' && (
                    <>
                      <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                        Balance
                      </th>
                      <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                        Next Payment
                      </th>
                    </>
                  )}
                  <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                {loans.map((loan) => (
                  <tr key={loan.id} className="border-b hover:bg-gray-50">
                    {activeTab === 'approval' && (
                      <td className="px-6 py-4">
                        <input
                          type="checkbox"
                          checked={selectedLoans.includes(loan.id)}
                          onChange={() => toggleLoanSelection(loan.id)}
                          className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                      </td>
                    )}
                    <td className="px-6 py-4 text-sm font-medium text-gray-900">
                      {loan.loan_number}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      {loan.member_name}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      {formatCurrency(loan.amount)}
                    </td>
                    <td className="px-6 py-4 text-sm">
                      <span
                        className={`px-2 py-1 rounded text-xs font-medium ${getStatusColor(
                          loan.status
                        )}`}
                      >
                        {loan.status}
                      </span>
                    </td>
                    {activeTab === 'tracking' && (
                      <>
                        <td className="px-6 py-4 text-sm text-gray-900">
                          {formatCurrency(loan.outstanding_balance || 0)}
                        </td>
                        <td className="px-6 py-4 text-sm text-gray-900">
                          {loan.next_payment_date
                            ? new Date(loan.next_payment_date).toLocaleDateString()
                            : '-'}
                        </td>
                      </>
                    )}
                    <td className="px-6 py-4 text-sm">
                      <button
                        onClick={() => openLoanDetails(loan)}
                        className="text-green-600 hover:text-green-800 mr-3"
                      >
                        View
                      </button>
                      {activeTab === 'approval' && (
                        <button
                          onClick={() => openActionModal(loan)}
                          className="text-blue-600 hover:text-blue-800"
                        >
                          Review
                        </button>
                      )}
                      {activeTab === 'disbursement' && (
                        <button
                          onClick={() => openActionModal(loan)}
                          className="text-blue-600 hover:text-blue-800"
                        >
                          Disburse
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>

            {totalPages > 1 && (
              <div className="px-6 py-4 border-t flex justify-between items-center">
                <button
                  onClick={() => fetchLoans(currentPage - 1)}
                  disabled={currentPage === 1}
                  className="px-3 py-1 border border-gray-300 rounded disabled:opacity-50"
                >
                  Previous
                </button>
                <span className="text-sm text-gray-600">
                  Page {currentPage} of {totalPages}
                </span>
                <button
                  onClick={() => fetchLoans(currentPage + 1)}
                  disabled={currentPage === totalPages}
                  className="px-3 py-1 border border-gray-300 rounded disabled:opacity-50"
                >
                  Next
                </button>
              </div>
            )}
          </>
        )}
      </div>

      <Modal
        isOpen={isCreateModalOpen}
        title="New Loan Application"
        onClose={() => setIsCreateModalOpen(false)}
        size="md"
      >
        <LoanApplicationForm
          onSubmit={handleCreateLoan}
          onCancel={() => setIsCreateModalOpen(false)}
        />
      </Modal>

      <Modal
        isOpen={isDetailsModalOpen}
        title="Loan Details"
        onClose={() => {
          setIsDetailsModalOpen(false);
          setSelectedLoan(null);
        }}
        size="lg"
      >
        {selectedLoan && (
          <div className="space-y-6">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <p className="text-sm text-gray-600">Loan Number</p>
                <p className="text-lg font-semibold text-gray-900">
                  {selectedLoan.loan_number}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Status</p>
                <p className="text-lg font-semibold text-gray-900">
                  <span
                    className={`px-2 py-1 rounded text-xs font-medium ${getStatusColor(
                      selectedLoan.status
                    )}`}
                  >
                    {selectedLoan.status}
                  </span>
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Loan Amount</p>
                <p className="text-lg font-semibold text-gray-900">
                  {formatCurrency(selectedLoan.amount)}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Interest Rate</p>
                <p className="text-lg font-semibold text-gray-900">
                  {selectedLoan.interest_rate}%
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Term</p>
                <p className="text-lg font-semibold text-gray-900">
                  {selectedLoan.term_months} months
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Purpose</p>
                <p className="text-lg font-semibold text-gray-900">
                  {selectedLoan.purpose}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Application Date</p>
                <p className="text-lg font-semibold text-gray-900">
                  {new Date(selectedLoan.application_date).toLocaleDateString()}
                </p>
              </div>
              {selectedLoan.approval_date && (
                <div>
                  <p className="text-sm text-gray-600">Approval Date</p>
                  <p className="text-lg font-semibold text-gray-900">
                    {new Date(selectedLoan.approval_date).toLocaleDateString()}
                  </p>
                </div>
              )}
            </div>

            <div className="flex justify-end gap-3">
              <button
                onClick={() => {
                  setIsDetailsModalOpen(false);
                  setSelectedLoan(null);
                }}
                className="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
              >
                Close
              </button>
            </div>
          </div>
        )}
      </Modal>

      <Modal
        isOpen={isCalculatorModalOpen}
        title="Loan Calculator"
        onClose={() => setIsCalculatorModalOpen(false)}
        size="md"
      >
        <LoanCalculator onClose={() => setIsCalculatorModalOpen(false)} />
      </Modal>

      <Modal
        isOpen={isBulkActionModalOpen}
        title={`Bulk ${bulkAction === 'approve' ? 'Approve' : 'Reject'} Loans`}
        onClose={() => {
          setIsBulkActionModalOpen(false);
          setBulkAction(null);
        }}
        size="md"
      >
        {bulkAction && (
          <BulkLoanActionForm
            action={bulkAction}
            loanCount={selectedLoans.length}
            onSubmit={(data) => handleBulkAction(bulkAction, data)}
            onCancel={() => {
              setIsBulkActionModalOpen(false);
              setBulkAction(null);
            }}
          />
        )}
      </Modal>

      <Modal
        isOpen={isActionModalOpen}
        title={
          activeTab === 'approval'
            ? 'Review Loan Application'
            : 'Disburse Loan'
        }
        onClose={() => {
          setIsActionModalOpen(false);
          setSelectedLoan(null);
        }}
        size="md"
      >
        {selectedLoan && activeTab === 'approval' && (
          <LoanApprovalForm
            loanId={selectedLoan.id}
            onSubmit={handleApproveLoan}
            onCancel={() => {
              setIsActionModalOpen(false);
              setSelectedLoan(null);
            }}
          />
        )}
        {selectedLoan && activeTab === 'disbursement' && (
          <LoanDisbursementForm
            loanId={selectedLoan.id}
            loanAmount={selectedLoan.amount}
            onSubmit={handleDisburseLoan}
            onCancel={() => {
              setIsActionModalOpen(false);
              setSelectedLoan(null);
            }}
          />
        )}
      </Modal>
    </div>
  );
};
