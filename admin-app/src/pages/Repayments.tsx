import React, { useState } from 'react';
import { Modal } from '../components/Modal';
import { RepaymentForm } from '../components/RepaymentForm';
import { RepaymentUpload } from '../components/RepaymentUpload';

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

export const Repayments: React.FC = () => {
  const [isManualModalOpen, setIsManualModalOpen] = useState(false);
  const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);
  const [uploadSummary, setUploadSummary] = useState<UploadSummary | null>(null);

  const handleManualRepaymentSuccess = () => {
    setIsManualModalOpen(false);
  };

  const handleUploadSuccess = (summary: UploadSummary) => {
    setUploadSummary(summary);
    setIsUploadModalOpen(false);
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
    <div>
      <h1 className="text-3xl font-bold text-gray-800 mb-6">Loan Repayments</h1>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {/* Manual Repayment Card */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-start justify-between mb-4">
            <div>
              <h2 className="text-xl font-semibold text-gray-800 mb-2">
                Manual Repayment
              </h2>
              <p className="text-gray-600 text-sm">
                Record individual loan repayments
              </p>
            </div>
            <span className="text-3xl">💳</span>
          </div>
          <p className="text-gray-600 mb-4">
            Process individual repayments for specific loans. Enter the loan details and payment amount to generate a repayment receipt.
          </p>
          <button
            onClick={() => setIsManualModalOpen(true)}
            className="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
          >
            Record Repayment
          </button>
        </div>

        {/* Automatic Repayment Card */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-start justify-between mb-4">
            <div>
              <h2 className="text-xl font-semibold text-gray-800 mb-2">
                Automatic Repayment Upload
              </h2>
              <p className="text-gray-600 text-sm">
                Batch process loan repayments via CSV
              </p>
            </div>
            <span className="text-3xl">📤</span>
          </div>
          <p className="text-gray-600 mb-4">
            Upload a CSV file with multiple loan repayments for batch processing. The system will match transactions to loans and show a summary.
          </p>
          <button
            onClick={() => setIsUploadModalOpen(true)}
            className="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors"
          >
            Upload CSV
          </button>
        </div>
      </div>

      {/* Upload Summary */}
      {uploadSummary && (
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-xl font-semibold text-gray-800">
              Upload Processing Summary
            </h2>
            <button
              onClick={() => setUploadSummary(null)}
              className="text-gray-500 hover:text-gray-700 text-2xl leading-none"
            >
              ×
            </button>
          </div>

          {/* Summary Stats */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div className="bg-blue-50 rounded-lg p-4">
              <p className="text-sm text-gray-600">Total Records</p>
              <p className="text-2xl font-bold text-blue-600">
                {uploadSummary.total_records}
              </p>
            </div>
            <div className="bg-green-50 rounded-lg p-4">
              <p className="text-sm text-gray-600">Matched</p>
              <p className="text-2xl font-bold text-green-600">
                {uploadSummary.matched}
              </p>
            </div>
            <div className="bg-yellow-50 rounded-lg p-4">
              <p className="text-sm text-gray-600">Unmatched</p>
              <p className="text-2xl font-bold text-yellow-600">
                {uploadSummary.unmatched}
              </p>
            </div>
            <div className="bg-purple-50 rounded-lg p-4">
              <p className="text-sm text-gray-600">Successful</p>
              <p className="text-2xl font-bold text-purple-600">
                {uploadSummary.successful}
              </p>
            </div>
          </div>

          {/* Matched Transactions */}
          {uploadSummary.matched_transactions && uploadSummary.matched_transactions.length > 0 && (
            <div className="mb-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-3">
                ✓ Matched Transactions ({uploadSummary.matched_transactions.length})
              </h3>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead className="bg-gray-50 border-b">
                    <tr>
                      <th className="px-4 py-2 text-left font-semibold text-gray-700">
                        Loan Number
                      </th>
                      <th className="px-4 py-2 text-left font-semibold text-gray-700">
                        Member
                      </th>
                      <th className="px-4 py-2 text-right font-semibold text-gray-700">
                        Amount
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {uploadSummary.matched_transactions.map((tx, idx) => (
                      <tr key={idx} className="border-b hover:bg-gray-50">
                        <td className="px-4 py-2 text-gray-900">
                          {tx.loan_number}
                        </td>
                        <td className="px-4 py-2 text-gray-900">
                          {tx.member_name}
                        </td>
                        <td className="px-4 py-2 text-right text-gray-900">
                          {formatCurrency(tx.amount)}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* Unmatched Transactions */}
          {uploadSummary.unmatched_transactions && uploadSummary.unmatched_transactions.length > 0 && (
            <div className="mb-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-3">
                ⚠ Unmatched Transactions ({uploadSummary.unmatched_transactions.length})
              </h3>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead className="bg-gray-50 border-b">
                    <tr>
                      <th className="px-4 py-2 text-left font-semibold text-gray-700">
                        Row
                      </th>
                      <th className="px-4 py-2 text-left font-semibold text-gray-700">
                        Loan Number
                      </th>
                      <th className="px-4 py-2 text-right font-semibold text-gray-700">
                        Amount
                      </th>
                      <th className="px-4 py-2 text-left font-semibold text-gray-700">
                        Reason
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {uploadSummary.unmatched_transactions.map((tx, idx) => (
                      <tr key={idx} className="border-b hover:bg-gray-50">
                        <td className="px-4 py-2 text-gray-900">{tx.row}</td>
                        <td className="px-4 py-2 text-gray-900">
                          {tx.loan_number}
                        </td>
                        <td className="px-4 py-2 text-right text-gray-900">
                          {formatCurrency(tx.amount)}
                        </td>
                        <td className="px-4 py-2 text-gray-600 text-sm">
                          {tx.reason}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* Errors */}
          {uploadSummary.errors && uploadSummary.errors.length > 0 && (
            <div className="mb-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-3">
                ✗ Errors ({uploadSummary.errors.length})
              </h3>
              <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                <ul className="space-y-2">
                  {uploadSummary.errors.map((err, idx) => (
                    <li key={idx} className="text-sm text-red-700">
                      <strong>Row {err.row}:</strong> {err.error}
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          )}
        </div>
      )}

      {/* Modals */}
      <Modal
        isOpen={isManualModalOpen}
        title="Record Manual Repayment"
        onClose={() => setIsManualModalOpen(false)}
        size="md"
      >
        <RepaymentForm
          onSubmit={async (data) => {
            handleManualRepaymentSuccess();
          }}
          onCancel={() => setIsManualModalOpen(false)}
        />
      </Modal>

      <Modal
        isOpen={isUploadModalOpen}
        title="Upload Loan Repayments"
        onClose={() => setIsUploadModalOpen(false)}
        size="md"
      >
        <RepaymentUpload
          onSuccess={handleUploadSuccess}
          onCancel={() => setIsUploadModalOpen(false)}
        />
      </Modal>
    </div>
  );
};
