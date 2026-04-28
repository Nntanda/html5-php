import React, { useState, useEffect } from 'react';
import { useAuthStore } from '../store/authStore';
import { apiClient } from '../api/client';
import { SavingsTransaction, Notification } from '../types';

interface MemberSummary {
  member: {
    id: number;
    member_number: string;
    full_name: string;
    status: string;
  };
  savings: {
    total_balance: number;
    account_number: string | null;
  };
  loans: {
    active_loans_count: number;
    total_loans_amount: number;
    total_outstanding_balance: number;
  };
  guarantor: {
    total_exposure: number;
    active_guarantees_count: number;
  };
  loan_eligibility: {
    max_loan_amount: number;
    available_loan_limit: number;
    can_apply: boolean;
  };
}

export const Dashboard: React.FC = () => {
  const { user } = useAuthStore();
  const [summary, setSummary] = useState<MemberSummary | null>(null);
  const [transactions, setTransactions] = useState<SavingsTransaction[]>([]);
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchDashboardData = async () => {
      try {
        setIsLoading(true);
        setError(null);

        // Fetch member summary using the new endpoint
        try {
          const summaryResponse = await apiClient.get<MemberSummary>('/member/summary');
          setSummary(summaryResponse.data);
        } catch (err: any) {
          console.error('Failed to fetch member summary:', err);
          // Don't set error here, just log it - user can still see other data
        }

        // Fetch recent transactions
        try {
          const transactionsResponse = await apiClient.get<{ data: SavingsTransaction[] }>(
            '/savings/transactions',
            { params: { limit: 5 } }
          );
          setTransactions(transactionsResponse.data.data || []);
        } catch (err) {
          console.log('Could not fetch transactions');
        }

        // Fetch notifications
        try {
          const notificationsResponse = await apiClient.get<{ data: Notification[] }>(
            '/notifications',
            { params: { limit: 5 } }
          );
          setNotifications(notificationsResponse.data.data || []);
        } catch (err) {
          console.log('Could not fetch notifications');
        }
      } catch (err: any) {
        const errorMsg = err.response?.data?.message || 'Failed to load dashboard data';
        setError(errorMsg);
        console.error('Dashboard error:', err);
      } finally {
        setIsLoading(false);
      }
    };

    if (user?.id) {
      fetchDashboardData();
    }
  }, [user?.id]);

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-UG', {
      style: 'currency',
      currency: 'UGX',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value);
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-screen">
        <p className="text-gray-600">Loading dashboard...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-gray-800">Dashboard</h1>
        <p className="text-gray-600 mt-2">
          Welcome back, {user?.name}! Here's your account overview.
        </p>
      </div>

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {/* Savings Balance Card */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-600 text-sm font-medium">Savings Balance</p>
              <p className="text-3xl font-bold text-green-600 mt-2">
                {summary ? formatCurrency(summary.savings.total_balance) : 'UGX 0'}
              </p>
            </div>
            <div className="text-4xl">💰</div>
          </div>
        </div>

        {/* Active Loans Card */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-600 text-sm font-medium">Active Loans</p>
              <p className="text-3xl font-bold text-blue-600 mt-2">
                {summary?.loans.active_loans_count || 0}
              </p>
            </div>
            <div className="text-4xl">📋</div>
          </div>
        </div>

        {/* Outstanding Balance Card */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-600 text-sm font-medium">Outstanding Balance</p>
              <p className="text-3xl font-bold text-orange-600 mt-2">
                {summary ? formatCurrency(summary.loans.total_outstanding_balance) : 'UGX 0'}
              </p>
            </div>
            <div className="text-4xl">📊</div>
          </div>
        </div>

        {/* Member Since Card */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-600 text-sm font-medium">Member Since</p>
              <p className="text-lg font-bold text-gray-800 mt-2">
                {user?.created_at
                  ? new Date(user.created_at).toLocaleDateString()
                  : 'N/A'}
              </p>
            </div>
            <div className="text-4xl">📅</div>
          </div>
        </div>
      </div>

      {/* Loan Eligibility Card */}
      {summary && (
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-bold text-gray-800 mb-4">Loan Eligibility</h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-blue-50 p-4 rounded-lg">
              <p className="text-sm text-gray-600">Max Loan Amount</p>
              <p className="text-2xl font-bold text-blue-600 mt-2">
                {formatCurrency(summary.loan_eligibility.max_loan_amount)}
              </p>
            </div>
            <div className="bg-green-50 p-4 rounded-lg">
              <p className="text-sm text-gray-600">Available Limit</p>
              <p className="text-2xl font-bold text-green-600 mt-2">
                {formatCurrency(summary.loan_eligibility.available_loan_limit)}
              </p>
            </div>
            <div className="bg-purple-50 p-4 rounded-lg">
              <p className="text-sm text-gray-600">Guarantor Exposure</p>
              <p className="text-2xl font-bold text-purple-600 mt-2">
                {formatCurrency(summary.guarantor.total_exposure)}
              </p>
            </div>
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Recent Transactions */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-bold text-gray-800 mb-4">Recent Transactions</h2>
          {transactions.length === 0 ? (
            <div className="text-center py-8 text-gray-600">
              <p>No transactions yet</p>
            </div>
          ) : (
            <div className="space-y-3">
              {transactions.map((transaction) => (
                <div key={transaction.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div>
                    <p className="font-medium text-gray-900">
                      {transaction.type === 'deposit' ? '📥 Deposit' : '📤 Withdrawal'}
                    </p>
                    <p className="text-sm text-gray-600">
                      {new Date(transaction.transaction_date).toLocaleDateString()}
                    </p>
                  </div>
                  <p className={`font-bold ${transaction.type === 'deposit' ? 'text-green-600' : 'text-red-600'}`}>
                    {transaction.type === 'deposit' ? '+' : '-'}{formatCurrency(transaction.amount)}
                  </p>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Notifications */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-bold text-gray-800 mb-4">Notifications</h2>
          {notifications.length === 0 ? (
            <div className="text-center py-8 text-gray-600">
              <p>No notifications</p>
            </div>
          ) : (
            <div className="space-y-3">
              {notifications.map((notification) => (
                <div
                  key={notification.id}
                  className={`p-3 rounded-lg border-l-4 ${
                    notification.status === 'sent'
                      ? 'bg-blue-50 border-blue-400'
                      : 'bg-yellow-50 border-yellow-400'
                  }`}
                >
                  <p className="font-medium text-gray-900">{notification.subject}</p>
                  <p className="text-sm text-gray-600 mt-1">{notification.message}</p>
                  <p className="text-xs text-gray-500 mt-2">
                    {new Date(notification.created_at).toLocaleDateString()}
                  </p>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
