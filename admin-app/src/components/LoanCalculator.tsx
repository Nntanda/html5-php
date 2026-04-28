import React, { useState } from 'react';
import { apiClient } from '../api/client';

interface LoanCalculatorProps {
  onClose?: () => void;
}

interface CalculationResult {
  loan_amount: number;
  interest_rate: number;
  term_months: number;
  monthly_repayment: number;
  total_repayment: number;
  total_interest: number;
}

export const LoanCalculator: React.FC<LoanCalculatorProps> = ({ onClose }) => {
  const [amount, setAmount] = useState('');
  const [termMonths, setTermMonths] = useState('12');
  const [interestRate, setInterestRate] = useState('15');
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
        interest_rate: parseFloat(interestRate),
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
    <div className="bg-white rounded-lg shadow-lg p-6">
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-2xl font-bold text-gray-800">Loan Calculator</h2>
        {onClose && (
          <button
            onClick={onClose}
            className="text-gray-500 hover:text-gray-700 text-2xl"
          >
            ×
          </button>
        )}
      </div>

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
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Loan Term (Months)
          </label>
          <select
            value={termMonths}
            onChange={(e) => setTermMonths(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="6">6 months</option>
            <option value="12">12 months</option>
            <option value="18">18 months</option>
            <option value="24">24 months</option>
            <option value="36">36 months</option>
            <option value="48">48 months</option>
            <option value="60">60 months</option>
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Interest Rate (% per annum)
          </label>
          <input
            type="number"
            step="0.1"
            value={interestRate}
            onChange={(e) => setInterestRate(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <button
          onClick={handleCalculate}
          disabled={isCalculating}
          className="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 font-medium"
        >
          {isCalculating ? 'Calculating...' : 'Calculate'}
        </button>
      </div>

      {result && (
        <div className="border-t pt-6">
          <h3 className="text-lg font-bold text-gray-800 mb-4">Calculation Results</h3>
          
          <div className="space-y-3">
            <div className="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
              <span className="text-sm text-gray-700">Monthly Repayment</span>
              <span className="text-xl font-bold text-blue-600">
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
              <span className="text-sm text-gray-700">Loan Amount</span>
              <span className="text-lg font-semibold text-gray-900">
                {formatCurrency(result.loan_amount)}
              </span>
            </div>
          </div>

          <div className="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p className="text-sm text-yellow-800">
              <strong>Note:</strong> This is an estimate based on reducing balance method. 
              Actual repayment may vary based on payment schedule and any penalties.
            </p>
          </div>
        </div>
      )}
    </div>
  );
};
