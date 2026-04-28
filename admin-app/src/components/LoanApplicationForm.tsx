import React, { useState, useEffect } from 'react';
import { apiClient } from '../api/client';
import { Member } from '../types';

interface LoanApplicationFormProps {
  onSubmit: (data: any) => Promise<void>;
  onCancel: () => void;
  initialData?: any;
  isEdit?: boolean;
}

export const LoanApplicationForm: React.FC<LoanApplicationFormProps> = ({
  onSubmit,
  onCancel,
  initialData,
  isEdit = false,
}) => {
  const [formData, setFormData] = useState({
    member_id: initialData?.member_id || '',
    amount: initialData?.amount || '',
    term_months: initialData?.term_months || '',
    purpose: initialData?.purpose || '',
    guarantor_ids: initialData?.guarantor_ids || [],
  });

  const [members, setMembers] = useState<Member[]>([]);
  const [guarantors, setGuarantors] = useState<Member[]>([]);
  const [eligibility, setEligibility] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchMembers();
  }, []);

  useEffect(() => {
    if (formData.member_id) {
      fetchEligibility();
      fetchGuarantors();
    }
  }, [formData.member_id]);

  const fetchMembers = async () => {
    try {
      const response = await apiClient.get<{ data: Member[] }>('/members', {
        params: { per_page: 100 },
      });
      setMembers(response.data.data);
    } catch (err: any) {
      setError('Failed to fetch members');
    }
  };

  const fetchEligibility = async () => {
    try {
      const response = await apiClient.get<any>(
        `/loans/eligibility/${formData.member_id}`
      );
      setEligibility(response.data);
    } catch (err: any) {
      console.error('Failed to fetch eligibility');
    }
  };

  const fetchGuarantors = async () => {
    try {
      const response = await apiClient.get<{ data: Member[] }>(
        `/members/${formData.member_id}/potential-guarantors`
      );
      setGuarantors(response.data.data);
    } catch (err: any) {
      console.error('Failed to fetch guarantors');
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleGuarantorToggle = (guarantorId: number) => {
    setFormData((prev) => ({
      ...prev,
      guarantor_ids: prev.guarantor_ids.includes(guarantorId)
        ? prev.guarantor_ids.filter((id: number) => id !== guarantorId)
        : [...prev.guarantor_ids, guarantorId],
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);
    try {
      await onSubmit(formData);
    } catch (err: any) {
      setError(err.message || 'Failed to submit loan application');
    } finally {
      setIsLoading(false);
    }
  };

  const selectedMember = members.find((m) => m.id === parseInt(formData.member_id));

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Member
        </label>
        <select
          name="member_id"
          value={formData.member_id}
          onChange={handleChange}
          required
          disabled={isEdit}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
        >
          <option value="">Select a member</option>
          {members.map((member) => (
            <option key={member.id} value={member.id}>
              {member.first_name} {member.last_name} ({member.member_number})
            </option>
          ))}
        </select>
      </div>

      {eligibility && (
        <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
          <h4 className="font-semibold text-blue-900 mb-2">Loan Eligibility</h4>
          <div className="grid grid-cols-2 gap-2 text-sm">
            <div>
              <p className="text-blue-700">Max Loan Amount:</p>
              <p className="font-bold text-blue-900">
                ${eligibility.max_loan_amount?.toFixed(2) || '0.00'}
              </p>
            </div>
            <div>
              <p className="text-blue-700">Savings Balance:</p>
              <p className="font-bold text-blue-900">
                ${eligibility.savings_balance?.toFixed(2) || '0.00'}
              </p>
            </div>
          </div>
        </div>
      )}

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Loan Amount
        </label>
        <input
          type="number"
          name="amount"
          value={formData.amount}
          onChange={handleChange}
          required
          step="0.01"
          min="0"
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        {eligibility && formData.amount && (
          <p className="text-xs text-gray-600 mt-1">
            {parseFloat(formData.amount) > eligibility.max_loan_amount
              ? '⚠️ Amount exceeds maximum eligible loan'
              : '✓ Amount is within eligible range'}
          </p>
        )}
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Term (Months)
        </label>
        <input
          type="number"
          name="term_months"
          value={formData.term_months}
          onChange={handleChange}
          required
          min="1"
          max="60"
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Purpose
        </label>
        <textarea
          name="purpose"
          value={formData.purpose}
          onChange={handleChange}
          required
          rows={3}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      {guarantors.length > 0 && (
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Select Guarantors
          </label>
          <div className="space-y-2 max-h-40 overflow-y-auto border border-gray-300 rounded-md p-3">
            {guarantors.map((guarantor) => (
              <label key={guarantor.id} className="flex items-center">
                <input
                  type="checkbox"
                  checked={formData.guarantor_ids.includes(guarantor.id)}
                  onChange={() => handleGuarantorToggle(guarantor.id)}
                  className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span className="ml-2 text-sm text-gray-700">
                  {guarantor.first_name} {guarantor.last_name}
                </span>
              </label>
            ))}
          </div>
        </div>
      )}

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
          className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {isLoading ? 'Submitting...' : 'Submit Application'}
        </button>
      </div>
    </form>
  );
};
