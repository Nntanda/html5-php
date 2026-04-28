import React, { useState, useEffect } from 'react';
import { apiClient } from '../api/client';

interface LoanGuarantor {
  id: number;
  guarantor_member_id: number;
  guarantor_name: string;
  guaranteed_amount: number;
  status: 'pending' | 'approved' | 'rejected';
  approval_date: string | null;
}

interface LoanApprovalFormProps {
  loanId: number;
  onSubmit: (action: 'approve' | 'reject', notes?: string) => Promise<void>;
  onCancel: () => void;
}

export const LoanApprovalForm: React.FC<LoanApprovalFormProps> = ({
  loanId,
  onSubmit,
  onCancel,
}) => {
  const [guarantors, setGuarantors] = useState<LoanGuarantor[]>([]);
  const [notes, setNotes] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchGuarantors();
  }, [loanId]);

  const fetchGuarantors = async () => {
    try {
      const response = await apiClient.get<{ data: LoanGuarantor[] }>(
        `/loans/${loanId}/guarantors`
      );
      setGuarantors(response.data.data);
    } catch (err: any) {
      setError('Failed to fetch guarantor information');
    }
  };

  const handleSubmit = async (submitAction: 'approve' | 'reject') => {
    setIsLoading(true);
    setError(null);
    try {
      await onSubmit(submitAction, notes);
    } catch (err: any) {
      setError(err.message || 'Failed to process loan');
    } finally {
      setIsLoading(false);
    }
  };

  const allGuarantorsApproved = guarantors.every((g) => g.status === 'approved');
  const canApprove = allGuarantorsApproved;

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      <div>
        <h3 className="font-semibold text-gray-800 mb-3">Guarantor Status</h3>
        <div className="space-y-2">
          {guarantors.length === 0 ? (
            <p className="text-gray-600 text-sm">No guarantors assigned</p>
          ) : (
            guarantors.map((guarantor) => (
              <div
                key={guarantor.id}
                className="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200"
              >
                <div>
                  <p className="font-medium text-gray-900">
                    {guarantor.guarantor_name}
                  </p>
                  <p className="text-sm text-gray-600">
                    Guaranteed Amount: ${guarantor.guaranteed_amount.toFixed(2)}
                  </p>
                </div>
                <span
                  className={`px-3 py-1 rounded-full text-xs font-medium ${
                    guarantor.status === 'approved'
                      ? 'bg-green-100 text-green-800'
                      : guarantor.status === 'rejected'
                      ? 'bg-red-100 text-red-800'
                      : 'bg-yellow-100 text-yellow-800'
                  }`}
                >
                  {guarantor.status}
                </span>
              </div>
            ))
          )}
        </div>
      </div>

      {!canApprove && (
        <div className="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded text-sm">
          ⚠️ All guarantors must approve before loan can be approved
        </div>
      )}

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Notes
        </label>
        <textarea
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          rows={3}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Add any notes about this loan approval..."
        />
      </div>

      <div className="flex justify-end gap-3 pt-4">
        <button
          type="button"
          onClick={() => onCancel()}
          disabled={isLoading}
          className="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 disabled:opacity-50"
        >
          Cancel
        </button>
        <button
          type="button"
          onClick={() => handleSubmit('reject')}
          disabled={isLoading}
          className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50"
        >
          {isLoading ? 'Processing...' : 'Reject'}
        </button>
        <button
          type="button"
          onClick={() => handleSubmit('approve')}
          disabled={isLoading || !canApprove}
          className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
        >
          {isLoading ? 'Processing...' : 'Approve'}
        </button>
      </div>
    </form>
  );
};
