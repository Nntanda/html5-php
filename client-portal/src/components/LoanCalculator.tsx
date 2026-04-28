import React, { useState } from 'react';
import { apiClient } from '../api/client';

interface CalculationResult {
  loan_amount: number;
  interest_rate: number;
  term_months: number;
  monthly_repayment: number;
  total_repayment: number;
  total_interest: number;
}

export const LoanCalculator: React.FC = () => {
  const [amount, setAmount] = useState('');
  const [termMonths, setTermMonths] = useState('12');
  const [result, setResult] = useState<CalculationResult | null>(null);
  const [isCalculating, setIsCalculating] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleCalculate = async () => {
    if (!amount || parseFloat(amount) <= 0) {
      setError('Please enter a valid loan amount');
      return;
    }

    setIsCalculating(true);
    setError(null);

    try {
      const response = await apiClient.post<CalculationResult>('/loans/calculate', {
        amount: parseFloat(amount),
        term_months: parseInt(termMonths),
      });

      setResult(response.data);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to calculate loan');
    } finally {
      setIsCalculating(false);
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

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <h2 className="text-2xl font-bold text-gray-800 mb-6">Loan Calculator</h2>

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {error}
        </div>
      )}

      <div className="space-y-4 mb-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Loan Amount (UGX)
          </label>
          <input
            type="number"
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            placeholder="Enter loan amount"
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Loan Term (Months)
          </label>
          <select
            value={termMonths}
            onChange={(e) => setTermMonths(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
          >
            <option value="6">6 months</option>
            <option value="12">12 months</option>
            <option value="18">18 months</option>
            <option value="24">24 months</option>
            <option value="36">36 months</option>
          </select>
        </div>

        <button
          onClick={handleCalculate}
          disabled={isCalculating}
          className="w-full bg-green-600 text-white py-2 rounded-md hover:bg-green-700 disabled:opacity-50 font-medium"
        >
          {isCalculating ? 'Calculating...' : 'Calculate'}
        </button>
      </div>

      {result && (
        <div className="border-t pt-6">
          <h3 className="text-lg font-bold text-gray-800 mb-4">Calculation Results</h3>
          
          <div className="space-y-3">
            <div className="flex justify-between items-center p-3 bg-green-50 rounded-lg">
              <span className="text-sm text-gray-700">Monthly Repayment</span>
              <span className="text-xl font-bold text-green-600">
                {formatCurrency(result.monthly_repayment)}
              </span>
            </div>

            <div className="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
              <span className="text-sm text-gray-700">Total Repayment</span>
              <span className="text-lg font-semibold text-gray-900">
                {formatCurrency(result.total_repayment)}
              </span>
            </div>

            <div className="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
              <span className="text-sm text-gray-700">Total Interest</span>
              <span className="text-lg font-semibold text-gray-900">
                {formatCurrency(result.total_interest)}
              </span>
            </div>

            <div className="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
              <span className="text-sm text-gray-700">Interest Rate</span>
              <span className="text-lg font-semibold text-gray-900">
                {result.interest_rate}% per annum
              </span>
            </div>
          </div>

          <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p className="text-sm text-blue-800">
              <strong>Note:</strong> This is an estimate based on reducing balance method. 
              Actual repayment may vary based on payment schedule and any penalties.
            </p>
          </div>
        </div>
      )}
    </div>
  );
};