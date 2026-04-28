import React, { useState, useEffect } from 'react';
import { apiClient } from '../api/client';

interface RepaymentSchedule {
  month: number;
  payment_date: string;
  principal: number;
  interest: number;
  total_payment: number;
  balance: number;
}

interface LoanDisbursementFormProps {
  loanId: number;
  loanAmount: number;
  onSubmit: () => Promise<void>;
  onCancel: () => void;
}

export const LoanDisbursementForm: React.FC<LoanDisbursementFormProps> = ({
  loanId,
  loanAmount,
  onSubmit,
  onCancel,
}) => {
  const [repaymentSchedule, setRepaymentSchedule] = useState<RepaymentSchedule[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [showSchedule, setShowSchedule] = useState(false);

  useEffect(() => {
    fetchRepaymentSchedule();
  }, [loanId]);

  const fetchRepaymentSchedule = async () => {
    try {
      const response = await apiClient.get<{ data: RepaymentSchedule[] }>(
        `/loans/${loanId}/repayment-schedule`
      );
      setRepaymentSchedule(response.data.data);
    } catch (err: any) {
      setError('Failed to fetch repayment schedule');
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);
    try {
      await onSubmit();
    } catch (err: any) {
      setError(err.message || 'Failed to disburse loan');
    } finally {
      setIsLoading(false);
    }
  };

  const totalInterest = repaymentSchedule.reduce((sum, s) => sum + s.interest, 0);
  const totalPayment = repaymentSchedule.reduce((sum, s) => sum + s.total_payment, 0);

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
        <h4 className="font-semibold text-blue-900 mb-3">Disbursement Summary</h4>
        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <p className="text-blue-700">Loan Amount:</p>
            <p className="font-bold text-blue-900">${loanAmount.toFixed(2)}</p>
          </div>
          <div>
            <p className="text-blue-700">Total Interest:</p>
            <p className="font-bold text-blue-900">${totalInterest.toFixed(2)}</p>
          </div>
          <div>
            <p className="text-blue-700">Total Repayment:</p>
            <p className="font-bold text-blue-900">${totalPayment.toFixed(2)}</p>
          </div>
          <div>
            <p className="text-blue-700">Repayment Months:</p>
            <p className="font-bold text-blue-900">{repaymentSchedule.length}</p>
          </div>
        </div>
      </div>

      <div>
        <button
          type="button"
          onClick={() => setShowSchedule(!showSchedule)}
          className="text-blue-600 hover:text-blue-800 text-sm font-medium"
        >
          {showSchedule ? '▼' : '▶'} View Repayment Schedule
        </button>

        {showSchedule && (
          <div className="mt-3 overflow-x-auto border border-gray-300 rounded-lg">
            <table className="w-full text-sm">
              <thead className="bg-gray-100 border-b">
                <tr>
                  <th className="px-3 py-2 text-left font-semibold text-gray-700">
                    Month
                  </th>
                  <th className="px-3 py-2 text-left font-semibold text-gray-700">
                    Payment Date
                  </th>
                  <th className="px-3 py-2 text-right font-semibold text-gray-700">
                    Principal
                  </th>
                  <th className="px-3 py-2 text-right font-semibold text-gray-700">
                    Interest
                  </th>
                  <th className="px-3 py-2 text-right font-semibold text-gray-700">
                    Total Payment
                  </th>
                  <th className="px-3 py-2 text-right font-semibold text-gray-700">
                    Balance
                  </th>
                </tr>
              </thead>
              <tbody>
                {repaymentSchedule.map((schedule, index) => (
                  <tr key={index} className="border-b hover:bg-gray-50">
                    <td className="px-3 py-2 text-gray-900">{schedule.month}</td>
                    <td className="px-3 py-2 text-gray-900">
                      {new Date(schedule.payment_date).toLocaleDateString()}
                    </td>
                    <td className="px-3 py-2 text-right text-gray-900">
                      ${schedule.principal.toFixed(2)}
                    </td>
                    <td className="px-3 py-2 text-right text-gray-900">
                      ${schedule.interest.toFixed(2)}
                    </td>
                    <td className="px-3 py-2 text-right font-medium text-gray-900">
                      ${schedule.total_payment.toFixed(2)}
                    </td>
                    <td className="px-3 py-2 text-right text-gray-900">
                      ${schedule.balance.toFixed(2)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <div className="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded text-sm">
        ⚠️ Disbursement will transfer the loan amount to the member's savings account
      </div>

      <div className="flex justify-end gap-3 pt-4">
        <button
          type="button"
          onClick={onCancel}
          disabled={isLoading}
          className="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 disabled:opacity-50"
        >
          Cancel
        </button>
        <button
          type="submit"
          disabled={isLoading}
          className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
        >
          {isLoading ? 'Processing...' : 'Disburse Loan'}
        </button>
      </div>
    </form>
  );
};
