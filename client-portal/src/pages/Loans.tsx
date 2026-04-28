import React, { useState, useEffect } from 'react';
import { apiClient } from '../api/client';
import { Loan } from '../types';
import { LoanCalculator } from '../components/LoanCalculator';

interface LoanWithDetails extends Loan {
  outstanding_balance?: number;
  next_payment_date?: string;
  repayment_schedule?: any[];
  payment_history?: any[];
}

interface LoanEligibility {
  max_loan_amount: number;
  available_loan_limit: number;
  can_apply: boolean;
}

interface GuarantorRequest {
  id: number;
  loan_id: number;
  loan_number: string;
  loan_amount: number;
  guarantor_member_id: number;
  guaranteed_amount: number;
  status: 'pending' | 'approved' | 'rejected';
  created_at: string;
}

type TabType = 'apply' | 'active' | 'guarantor' | 'history' | 'calculator';

export const Loans: React.FC = () => {
  const [activeTab, setActiveTab] = useState<TabType>('active');
  const [loans, setLoans] = useState<LoanWithDetails[]>([]);
  const [guarantorRequests, setGuarantorRequests] = useState<GuarantorRequest[]>([]);
  const [eligibility, setEligibility] = useState<LoanEligibility | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [selectedLoan, setSelectedLoan] = useState<LoanWithDetails | null>(null);
  const [isDetailsOpen, setIsDetailsOpen] = useState(false);

  // Form state
  const [formData, setFormData] = useState({
    amount: '',
    purpose: '',
    term_months: '',
    guarantors: [] as Array<{ member_id: number; guaranteed_amount: number }>,
  });
  const [formError, setFormError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [potentialGuarantors, setPotentialGuarantors] = useState<any[]>([]);

  useEffect(() => {
    fetchLoansData();
  }, []);

  const fetchLoansData = async () => {
    setIsLoading(true);
    setError(null);
    try {
      const meResponse = await apiClient.get<{ user: any }>('/me');
      const currentUser = meResponse.data.user;

      // Fetch member's loans
      const loansResponse = await apiClient.get<{ data: LoanWithDetails[] }>(
        `/loans?member_id=${currentUser.id}`
      );
      setLoans(loansResponse.data.data);

      // Fetch loan eligibility
      const eligibilityResponse = await apiClient.get<LoanEligibility>(
        `/members/${currentUser.id}/loan-eligibility`
      );
      setEligibility(eligibilityResponse.data);

      // Fetch potential guarantors
      const guarantorsResponse = await apiClient.get<{ data: any[] }>(
        `/members/${currentUser.id}/potential-guarantors`
      );
      setPotentialGuarantors(guarantorsResponse.data.data);

      // Fetch pending guarantor requests
      const guarantorResponse = await apiClient.get<{ data: GuarantorRequest[] }>(
        `/members/${currentUser.id}/guarantor-requests`
      );
      setGuarantorRequests(guarantorResponse.data.data);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to load loans data');
      console.error('Loans error:', err);
    } finally {
      setIsLoading(false);
    }
  };

  const handleApplyLoan = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError(null);
    setIsSubmitting(true);

    try {
      // Get current user info
      const meResponse = await apiClient.get<{ user: any }>('/me');
      const currentUser = meResponse.data.user;

      await apiClient.post('/loans/apply', {
        member_id: currentUser.id,
        amount: parseFloat(formData.amount),
        purpose: formData.purpose,
        term_months: parseInt(formData.term_months),
        guarantors: formData.guarantors || [],
      });

      setFormData({ amount: '', purpose: '', term_months: '', guarantors: [] });
      setActiveTab('active');
      await fetchLoansData();
    } catch (err: any) {
      setFormError(err.response?.data?.message || 'Failed to submit loan application');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleGuarantorAction = async (
    requestId: number,
    action: 'approve' | 'reject'
  ) => {
    try {
      await apiClient.put(`/loans/guarantors/${requestId}`, { status: action });
      await fetchLoansData();
    } catch (err: any) {
      setError(err.response?.data?.message || `Failed to ${action} guarantor request`);
    }
  };

  const openLoanDetails = async (loan: LoanWithDetails) => {
    try {
      const response = await apiClient.get<{ loan: LoanWithDetails }>(
        `/loans/${loan.id}`
      );
      setSelectedLoan(response.data.loan);
      setIsDetailsOpen(true);
    } catch (err: any) {
      setError('Failed to fetch loan details');
    }
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-UG', {
      style: 'currency',
      currency: 'UGX',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-KE');
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

  const activeLoanCount = loans?.filter(l => l.status === 'active')?.length || 0;
  const pendingGuarantorCount = guarantorRequests?.filter(r => r.status === 'pending')?.length || 0;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-gray-800">My Loans</h1>
        <p className="text-gray-600 mt-2">View and manage your loan applications</p>
      </div>

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      <div className="bg-white rounded-lg shadow mb-6">
        <div className="flex border-b overflow-x-auto">
          <button
            onClick={() => setActiveTab('active')}
            className={`px-6 py-3 font-medium whitespace-nowrap ${
              activeTab === 'active'
                ? 'border-b-2 border-green-600 text-green-600'
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Active Loans {activeLoanCount > 0 && `(${activeLoanCount})`}
          </button>
          <button
            onClick={() => setActiveTab('apply')}
            className={`px-6 py-3 font-medium whitespace-nowrap ${
              activeTab === 'apply'
                ? 'border-b-2 border-green-600 text-green-600'
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Apply for Loan
          </button>
          <button
            onClick={() => setActiveTab('guarantor')}
            className={`px-6 py-3 font-medium whitespace-nowrap ${
              activeTab === 'guarantor'
                ? 'border-b-2 border-green-600 text-green-600'
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Guarantor Requests {pendingGuarantorCount > 0 && `(${pendingGuarantorCount})`}
          </button>
          <button
            onClick={() => setActiveTab('history')}
            className={`px-6 py-3 font-medium whitespace-nowrap ${
              activeTab === 'history'
                ? 'border-b-2 border-green-600 text-green-600'
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Loan History
          </button>
          <button
            onClick={() => setActiveTab('calculator')}
            className={`px-6 py-3 font-medium whitespace-nowrap ${
              activeTab === 'calculator'
                ? 'border-b-2 border-green-600 text-green-600'
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Calculator
          </button>
        </div>
      </div>

      {isLoading ? (
        <div className="bg-white rounded-lg shadow p-6 text-center text-gray-600">
          Loading loans data...
        </div>
      ) : activeTab === 'apply' ? (
        <LoanApplicationTab
          eligibility={eligibility}
          formData={formData}
          setFormData={setFormData}
          formError={formError}
          isSubmitting={isSubmitting}
          onSubmit={handleApplyLoan}
          formatCurrency={formatCurrency}
          potentialGuarantors={potentialGuarantors}
        />
      ) : activeTab === 'active' ? (
        <ActiveLoansTab
          loans={loans?.filter(l => l.status === 'active') || []}
          onViewDetails={openLoanDetails}
          formatCurrency={formatCurrency}
          formatDate={formatDate}
          getStatusColor={getStatusColor}
        />
      ) : activeTab === 'guarantor' ? (
        <GuarantorTab
          requests={guarantorRequests}
          onApprove={(id) => handleGuarantorAction(id, 'approve')}
          onReject={(id) => handleGuarantorAction(id, 'reject')}
          formatCurrency={formatCurrency}
          formatDate={formatDate}
        />
      ) : activeTab === 'history' ? (
        <LoanHistoryTab
          loans={loans}
          onViewDetails={openLoanDetails}
          formatCurrency={formatCurrency}
          formatDate={formatDate}
          getStatusColor={getStatusColor}
        />
      ) : activeTab === 'calculator' ? (
        <LoanCalculator />
      ) : (
        <LoanHistoryTab
          loans={loans}
          onViewDetails={openLoanDetails}
          formatCurrency={formatCurrency}
          formatDate={formatDate}
          getStatusColor={getStatusColor}
        />
      )}

      {isDetailsOpen && selectedLoan && (
        <LoanDetailsModal
          loan={selectedLoan}
          onClose={() => {
            setIsDetailsOpen(false);
            setSelectedLoan(null);
          }}
          formatCurrency={formatCurrency}
          formatDate={formatDate}
          getStatusColor={getStatusColor}
        />
      )}
    </div>
  );
};


// Loan Application Tab Component
const LoanApplicationTab: React.FC<{
  eligibility: any;
  formData: any;
  setFormData: any;
  formError: string | null;
  isSubmitting: boolean;
  onSubmit: (e: React.FormEvent) => void;
  formatCurrency: (value: number) => string;
  potentialGuarantors: any[];
}> = ({
  eligibility,
  formData,
  setFormData,
  formError,
  isSubmitting,
  onSubmit,
  formatCurrency,
  potentialGuarantors,
}) => {
  const [selectedGuarantorId, setSelectedGuarantorId] = useState('');
  const [guaranteedAmount, setGuaranteedAmount] = useState('');

  const addGuarantor = () => {
    if (!selectedGuarantorId || !guaranteedAmount) {
      return;
    }

    const amount = parseFloat(guaranteedAmount);
    if (amount <= 0) {
      return;
    }

    const guarantor = potentialGuarantors.find(g => g.id === parseInt(selectedGuarantorId));
    if (!guarantor) {
      return;
    }

    // Check if guarantor already added
    if ((formData.guarantors || []).some((g: any) => g.member_id === guarantor.id)) {
      return;
    }

    setFormData({
      ...formData,
      guarantors: [
        ...(formData.guarantors || []),
        { member_id: guarantor.id, guaranteed_amount: amount, guarantor_name: guarantor.full_name }
      ]
    });

    setSelectedGuarantorId('');
    setGuaranteedAmount('');
  };

  const removeGuarantor = (memberId: number) => {
    setFormData({
      ...formData,
      guarantors: (formData.guarantors || []).filter((g: any) => g.member_id !== memberId)
    });
  };

  const totalGuaranteed = (formData.guarantors || []).reduce((sum: number, g: any) => sum + g.guaranteed_amount, 0);
  const loanAmount = parseFloat(formData.amount) || 0;
  const isFullyGuaranteed = totalGuaranteed >= loanAmount;

  if (!eligibility) {
    return (
      <div className="bg-white rounded-lg shadow p-6 text-center text-gray-600">
        Unable to load eligibility information
      </div>
    );
  }

  if (!eligibility.can_apply) {
    return (
      <div className="bg-white rounded-lg shadow p-6">
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
          <p className="text-yellow-800 font-medium">You are not eligible to apply for a loan at this time.</p>
          <p className="text-yellow-700 text-sm mt-2">
            Please ensure you have sufficient savings and no overdue loans.
          </p>
          {eligibility.errors && eligibility.errors.length > 0 && (
            <ul className="mt-3 text-sm text-yellow-700 text-left list-disc list-inside">
              {eligibility.errors.map((error: string, idx: number) => (
                <li key={idx}>{error}</li>
              ))}
            </ul>
          )}
        </div>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div className="lg:col-span-2">
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-bold text-gray-800 mb-6">Loan Application Form</h2>

          {formError && (
            <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
              {formError}
            </div>
          )}

          <form onSubmit={onSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Loan Amount (UGX)
              </label>
              <input
                type="number"
                step="100"
                min="0"
                max={eligibility.available_loan_limit}
                value={formData.amount}
                onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                required
              />
              <p className="text-xs text-gray-500 mt-1">
                Maximum: {formatCurrency(eligibility.available_loan_limit)}
              </p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Loan Purpose
              </label>
              <select
                value={formData.purpose}
                onChange={(e) => setFormData({ ...formData, purpose: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                required
              >
                <option value="">Select purpose</option>
                <option value="Business">Business</option>
                <option value="Education">Education</option>
                <option value="Medical">Medical</option>
                <option value="Home">Home</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Loan Term (Months)
              </label>
              <select
                value={formData.term_months}
                onChange={(e) => setFormData({ ...formData, term_months: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                required
              >
                <option value="">Select term</option>
                <option value="6">6 months</option>
                <option value="12">12 months</option>
                <option value="24">24 months</option>
                <option value="36">36 months</option>
              </select>
            </div>

            <div className="border-t pt-4">
              <h3 className="text-lg font-semibold text-gray-800 mb-3">Guarantors (Minimum 2 required)</h3>
              
              {(formData.guarantors || []).length > 0 && (
                <div className="mb-4 space-y-2">
                  {(formData.guarantors || []).map((g: any) => (
                    <div key={g.member_id} className="flex justify-between items-center p-3 bg-gray-50 rounded">
                      <div>
                        <p className="font-medium text-gray-900">{g.guarantor_name}</p>
                        <p className="text-sm text-gray-600">Guaranteed: {formatCurrency(g.guaranteed_amount)}</p>
                      </div>
                      <button
                        type="button"
                        onClick={() => removeGuarantor(g.member_id)}
                        className="text-red-600 hover:text-red-800 text-sm"
                      >
                        Remove
                      </button>
                    </div>
                  ))}
                  <div className="p-3 bg-blue-50 rounded">
                    <p className="text-sm text-gray-700">
                      Total Guaranteed: {formatCurrency(totalGuaranteed)} / {formatCurrency(loanAmount)}
                    </p>
                    {isFullyGuaranteed && (
                      <p className="text-sm text-green-600 mt-1">✓ Loan amount fully guaranteed</p>
                    )}
                  </div>
                </div>
              )}

              <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Select Guarantor
                  </label>
                  <select
                    value={selectedGuarantorId}
                    onChange={(e) => setSelectedGuarantorId(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                  >
                    <option value="">Choose a guarantor</option>
                    {(potentialGuarantors || [])
                      .filter(g => !(formData.guarantors || []).some((fg: any) => fg.member_id === g.id))
                      .map((guarantor) => (
                        <option key={guarantor.id} value={guarantor.id}>
                          {guarantor.full_name} ({guarantor.member_number}) - Available: {formatCurrency(guarantor.available_capacity)}
                        </option>
                      ))}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Amount
                  </label>
                  <input
                    type="number"
                    step="100"
                    min="0"
                    value={guaranteedAmount}
                    onChange={(e) => setGuaranteedAmount(e.target.value)}
                    placeholder="Amount"
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                  />
                </div>
              </div>
              <button
                type="button"
                onClick={addGuarantor}
                disabled={!selectedGuarantorId || !guaranteedAmount}
                className="mt-2 w-full bg-gray-200 text-gray-800 py-2 rounded-md hover:bg-gray-300 disabled:opacity-50 font-medium"
              >
                Add Guarantor
              </button>
            </div>

            <button
              type="submit"
              disabled={isSubmitting || (formData.guarantors || []).length < 2 || !isFullyGuaranteed}
              className="w-full bg-green-600 text-white py-2 rounded-md hover:bg-green-700 disabled:opacity-50 font-medium"
            >
              {isSubmitting ? 'Submitting...' : 'Submit Application'}
            </button>
            {(formData.guarantors || []).length < 2 && (
              <p className="text-sm text-red-600 text-center">At least 2 guarantors required</p>
            )}
            {!isFullyGuaranteed && loanAmount > 0 && (
              <p className="text-sm text-red-600 text-center">Total guaranteed amount must equal or exceed loan amount</p>
            )}
          </form>
        </div>
      </div>

      <div>
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-bold text-gray-800 mb-4">Your Eligibility</h3>
          <div className="space-y-4">
            <div className="bg-blue-50 p-4 rounded-lg">
              <p className="text-sm text-gray-600">Max Loan Amount</p>
              <p className="text-2xl font-bold text-blue-600 mt-1">
                {formatCurrency(eligibility.max_loan_amount)}
              </p>
            </div>
            <div className="bg-green-50 p-4 rounded-lg">
              <p className="text-sm text-gray-600">Available Limit</p>
              <p className="text-2xl font-bold text-green-600 mt-1">
                {formatCurrency(eligibility.available_loan_limit)}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

// Active Loans Tab Component
const ActiveLoansTab: React.FC<{
  loans: LoanWithDetails[];
  onViewDetails: (loan: LoanWithDetails) => void;
  formatCurrency: (value: number) => string;
  formatDate: (date: string) => string;
  getStatusColor: (status: string) => string;
}> = ({ loans, onViewDetails, formatCurrency, formatDate, getStatusColor }) => {
  if (loans.length === 0) {
    return (
      <div className="bg-white rounded-lg shadow p-6 text-center text-gray-600">
        <p>No active loans</p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {loans.map((loan) => (
        <div key={loan.id} className="bg-white rounded-lg shadow p-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <h3 className="text-lg font-bold text-gray-800">{loan.loan_number}</h3>
              <p className="text-sm text-gray-600">{loan.purpose}</p>
            </div>
            <span className={`px-3 py-1 rounded text-xs font-medium ${getStatusColor(loan.status)}`}>
              {loan.status}
            </span>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div>
              <p className="text-xs text-gray-600">Loan Amount</p>
              <p className="text-lg font-bold text-gray-900">{formatCurrency(loan.amount)}</p>
            </div>
            <div>
              <p className="text-xs text-gray-600">Outstanding Balance</p>
              <p className="text-lg font-bold text-gray-900">
                {formatCurrency(loan.outstanding_balance || 0)}
              </p>
            </div>
            <div>
              <p className="text-xs text-gray-600">Interest Rate</p>
              <p className="text-lg font-bold text-gray-900">{loan.interest_rate}%</p>
            </div>
            <div>
              <p className="text-xs text-gray-600">Next Payment</p>
              <p className="text-lg font-bold text-gray-900">
                {loan.next_payment_date ? formatDate(loan.next_payment_date) : '-'}
              </p>
            </div>
          </div>

          <button
            onClick={() => onViewDetails(loan)}
            className="text-green-600 hover:text-green-800 font-medium text-sm"
          >
            View Details & Schedule →
          </button>
        </div>
      ))}
    </div>
  );
};

// Guarantor Requests Tab Component
const GuarantorTab: React.FC<{
  requests: GuarantorRequest[];
  onApprove: (id: number) => void;
  onReject: (id: number) => void;
  formatCurrency: (value: number) => string;
  formatDate: (date: string) => string;
}> = ({ requests, onApprove, onReject, formatCurrency, formatDate }) => {
  const pendingRequests = (requests || []).filter(r => r.status === 'pending');
  const processedRequests = (requests || []).filter(r => r.status !== 'pending');

  return (
    <div className="space-y-6">
      {pendingRequests.length > 0 && (
        <div>
          <h2 className="text-lg font-bold text-gray-800 mb-4">Pending Requests</h2>
          <div className="space-y-4">
            {pendingRequests.map((request) => (
              <div key={request.id} className="bg-white rounded-lg shadow p-6">
                <div className="flex justify-between items-start mb-4">
                  <div>
                    <h3 className="text-lg font-bold text-gray-800">{request.loan_number}</h3>
                    <p className="text-sm text-gray-600">Loan Amount: {formatCurrency(request.loan_amount)}</p>
                  </div>
                  <span className="px-3 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                    Pending
                  </span>
                </div>

                <div className="mb-4">
                  <p className="text-sm text-gray-600">Guaranteed Amount</p>
                  <p className="text-xl font-bold text-gray-900">{formatCurrency(request.guaranteed_amount)}</p>
                </div>

                <div className="flex gap-3">
                  <button
                    onClick={() => onApprove(request.id)}
                    className="flex-1 bg-green-600 text-white py-2 rounded-md hover:bg-green-700 font-medium"
                  >
                    Approve
                  </button>
                  <button
                    onClick={() => onReject(request.id)}
                    className="flex-1 bg-red-600 text-white py-2 rounded-md hover:bg-red-700 font-medium"
                  >
                    Reject
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {processedRequests.length > 0 && (
        <div>
          <h2 className="text-lg font-bold text-gray-800 mb-4">Processed Requests</h2>
          <div className="space-y-4">
            {processedRequests.map((request) => (
              <div key={request.id} className="bg-white rounded-lg shadow p-6">
                <div className="flex justify-between items-start">
                  <div>
                    <h3 className="text-lg font-bold text-gray-800">{request.loan_number}</h3>
                    <p className="text-sm text-gray-600">
                      {formatDate(request.created_at)}
                    </p>
                  </div>
                  <span
                    className={`px-3 py-1 rounded text-xs font-medium ${
                      request.status === 'approved'
                        ? 'bg-green-100 text-green-800'
                        : 'bg-red-100 text-red-800'
                    }`}
                  >
                    {request.status}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {(requests || []).length === 0 && (
        <div className="bg-white rounded-lg shadow p-6 text-center text-gray-600">
          <p>No guarantor requests</p>
        </div>
      )}
    </div>
  );
};

// Loan History Tab Component
const LoanHistoryTab: React.FC<{
  loans: LoanWithDetails[];
  onViewDetails: (loan: LoanWithDetails) => void;
  formatCurrency: (value: number) => string;
  formatDate: (date: string) => string;
  getStatusColor: (status: string) => string;
}> = ({ loans, onViewDetails, formatCurrency, formatDate, getStatusColor }) => {
  if (loans.length === 0) {
    return (
      <div className="bg-white rounded-lg shadow p-6 text-center text-gray-600">
        <p>No loan history</p>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg shadow overflow-hidden">
      <table className="w-full">
        <thead className="bg-gray-50 border-b">
          <tr>
            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Loan Number</th>
            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Amount</th>
            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Applied Date</th>
            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Action</th>
          </tr>
        </thead>
        <tbody>
          {loans.map((loan) => (
            <tr key={loan.id} className="border-b hover:bg-gray-50">
              <td className="px-6 py-4 text-sm font-medium text-gray-900">{loan.loan_number}</td>
              <td className="px-6 py-4 text-sm text-gray-900">{formatCurrency(loan.amount)}</td>
              <td className="px-6 py-4 text-sm">
                <span className={`px-2 py-1 rounded text-xs font-medium ${getStatusColor(loan.status)}`}>
                  {loan.status}
                </span>
              </td>
              <td className="px-6 py-4 text-sm text-gray-900">{formatDate(loan.application_date)}</td>
              <td className="px-6 py-4 text-sm">
                <button
                  onClick={() => onViewDetails(loan)}
                  className="text-green-600 hover:text-green-800 font-medium"
                >
                  View
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

// Loan Details Modal Component
const LoanDetailsModal: React.FC<{
  loan: LoanWithDetails;
  onClose: () => void;
  formatCurrency: (value: number) => string;
  formatDate: (date: string) => string;
  getStatusColor: (status: string) => string;
}> = ({ loan, onClose, formatCurrency, formatDate, getStatusColor }) => {
  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div className="sticky top-0 bg-white border-b p-6 flex justify-between items-center">
          <h2 className="text-2xl font-bold text-gray-800">Loan Details</h2>
          <button
            onClick={onClose}
            className="text-gray-500 hover:text-gray-700 text-2xl"
          >
            ×
          </button>
        </div>

        <div className="p-6 space-y-6">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-gray-600">Loan Number</p>
              <p className="text-lg font-semibold text-gray-900">{loan.loan_number}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Status</p>
              <span className={`inline-block px-3 py-1 rounded text-sm font-medium ${getStatusColor(loan.status)}`}>
                {loan.status}
              </span>
            </div>
            <div>
              <p className="text-sm text-gray-600">Loan Amount</p>
              <p className="text-lg font-semibold text-gray-900">{formatCurrency(loan.amount)}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Interest Rate</p>
              <p className="text-lg font-semibold text-gray-900">{loan.interest_rate}%</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Term</p>
              <p className="text-lg font-semibold text-gray-900">{loan.term_months} months</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Purpose</p>
              <p className="text-lg font-semibold text-gray-900">{loan.purpose}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Application Date</p>
              <p className="text-lg font-semibold text-gray-900">{formatDate(loan.application_date)}</p>
            </div>
            {loan.outstanding_balance !== undefined && (
              <div>
                <p className="text-sm text-gray-600">Outstanding Balance</p>
                <p className="text-lg font-semibold text-gray-900">
                  {formatCurrency(loan.outstanding_balance)}
                </p>
              </div>
            )}
          </div>

          {loan.repayment_schedule && loan.repayment_schedule.length > 0 && (
            <div>
              <h3 className="text-lg font-bold text-gray-800 mb-4">Repayment Schedule</h3>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-4 py-2 text-left text-gray-700">Due Date</th>
                      <th className="px-4 py-2 text-left text-gray-700">Principal</th>
                      <th className="px-4 py-2 text-left text-gray-700">Interest</th>
                      <th className="px-4 py-2 text-left text-gray-700">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    {loan.repayment_schedule.slice(0, 5).map((schedule: any, idx: number) => (
                      <tr key={idx} className="border-b">
                        <td className="px-4 py-2">{formatDate(schedule.due_date)}</td>
                        <td className="px-4 py-2">{formatCurrency(schedule.principal)}</td>
                        <td className="px-4 py-2">{formatCurrency(schedule.interest)}</td>
                        <td className="px-4 py-2 font-semibold">{formatCurrency(schedule.total)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              {loan.repayment_schedule.length > 5 && (
                <p className="text-sm text-gray-600 mt-2">
                  ... and {loan.repayment_schedule.length - 5} more payments
                </p>
              )}
            </div>
          )}

          {loan.payment_history && loan.payment_history.length > 0 && (
            <div>
              <h3 className="text-lg font-bold text-gray-800 mb-4">Payment History</h3>
              <div className="space-y-2">
                {loan.payment_history.map((payment: any, idx: number) => (
                  <div key={idx} className="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                      <p className="font-medium text-gray-900">{formatDate(payment.payment_date)}</p>
                      <p className="text-sm text-gray-600">{payment.reference}</p>
                    </div>
                    <p className="font-semibold text-gray-900">{formatCurrency(payment.amount)}</p>
                  </div>
                ))}
              </div>
            </div>
          )}

          <div className="flex justify-end gap-3 pt-4 border-t">
            <button
              onClick={onClose}
              className="px-6 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 font-medium"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
