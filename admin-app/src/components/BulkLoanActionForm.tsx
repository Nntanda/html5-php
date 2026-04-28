import React, { useState } from 'react';

interface BulkLoanActionFormProps {
  action: 'approve' | 'reject';
  loanCount: number;
  onSubmit: (data: any) => Promise<void>;
  onCancel: () => void;
}

export const BulkLoanActionForm: React.FC<BulkLoanActionFormProps> = ({
  action,
  loanCount,
  onSubmit,
  onCancel,
}) => {
  const [comment, setComment] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setError(null);

    try {
      const data = action === 'approve' 
        ? { approval_comment: comment }
        : { rejection_reason: comment };
      
      await onSubmit(data);
    } catch (err: any) {
      setError(err.message || `Failed to ${action} loans`);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p className="text-blue-800 font-medium">
          You are about to {action} {loanCount} loan(s).
        </p>
        <p className="text-blue-700 text-sm mt-1">
          This action cannot be undone. Please confirm your decision.
        </p>
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          {action === 'approve' ? 'Approval Comment' : 'Rejection Reason'}
          {action === 'reject' && <span className="text-red-500">*</span>}
        </label>
        <textarea
          value={comment}
          onChange={(e) => setComment(e.target.value)}
          rows={3}
          required={action === 'reject'}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder={
            action === 'approve' 
              ? 'Add any notes about this bulk approval...' 
              : 'Provide reason for rejection...'
          }
        />
      </div>

      <div className="flex justify-end gap-3 pt-4">
        <button
          type="button"
          onClick={onCancel}
          disabled={isSubmitting}
          className="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 disabled:opacity-50"
        >
          Cancel
        </button>
        <button
          type="submit"
          disabled={isSubmitting || (action === 'reject' && !comment.trim())}
          className={`px-4 py-2 text-white rounded-md disabled:opacity-50 ${
            action === 'approve'
              ? 'bg-green-600 hover:bg-green-700'
              : 'bg-red-600 hover:bg-red-700'
          }`}
        >
          {isSubmitting ? 'Processing...' : `${action === 'approve' ? 'Approve' : 'Reject'} ${loanCount} Loan(s)`}
        </button>
      </div>
    </form>
  );
};