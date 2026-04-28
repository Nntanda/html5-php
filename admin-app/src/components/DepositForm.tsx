import React, { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { apiClient } from '../api/client';
import { FormInput } from './FormInput';
import { FormSelect } from './FormSelect';

const depositSchema = z.object({
  member_id: z.string().min(1, 'Member is required'),
  amount: z.string().min(1, 'Amount is required').refine(
    (val) => !isNaN(parseFloat(val)) && parseFloat(val) > 0,
    'Amount must be a positive number'
  ),
  deposit_date: z.string().min(1, 'Deposit date is required'),
  source: z.string().min(1, 'Payment method is required'),
  reference: z.string().optional(),
  description: z.string().optional(),
});

type DepositFormData = z.infer<typeof depositSchema>;

interface Member {
  id: number;
  first_name: string;
  last_name: string;
  member_number: string;
  category: string;
}

interface DepositFormProps {
  onSubmit: (data: any) => Promise<void>;
  onCancel: () => void;
}

export const DepositForm: React.FC<DepositFormProps> = ({
  onSubmit,
  onCancel,
}) => {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [members, setMembers] = useState<Member[]>([]);
  const [isLoadingMembers, setIsLoadingMembers] = useState(false);
  const [selectedMember, setSelectedMember] = useState<Member | null>(null);
  const [evidenceFile, setEvidenceFile] = useState<File | null>(null);
  const [receiptFile, setReceiptFile] = useState<File | null>(null);

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm<DepositFormData>({
    resolver: zodResolver(depositSchema),
    defaultValues: {
      deposit_date: new Date().toISOString().split('T')[0], // Today's date
    },
  });

  const watchedMemberId = watch('member_id');

  useEffect(() => {
    if (watchedMemberId) {
      const member = members.find(m => m.id.toString() === watchedMemberId);
      setSelectedMember(member || null);
    }
  }, [watchedMemberId, members]);

  useEffect(() => {
    const fetchMembers = async () => {
      setIsLoadingMembers(true);
      try {
        const response = await apiClient.get<{
          data: Member[];
        }>('/members', { params: { per_page: 100, status: 'active' } });
        setMembers(response.data.data);
      } catch (err: any) {
        setError('Failed to load members');
      } finally {
        setIsLoadingMembers(false);
      }
    };
    fetchMembers();
  }, []);

  const onSubmitForm = async (data: DepositFormData) => {
    setIsSubmitting(true);
    setError(null);
    try {
      // Check if member is non-hospital staff and evidence is required
      if (selectedMember?.category === 'non_hospital_staff' && !evidenceFile) {
        setError('Evidence file is required for non-hospital staff deposits');
        setIsSubmitting(false);
        return;
      }

      // Create FormData for file upload
      const formData = new FormData();
      formData.append('member_id', data.member_id);
      formData.append('amount', data.amount);
      formData.append('payment_method', data.source);
      formData.append('deposit_date', data.deposit_date);
      if (data.reference) formData.append('reference', data.reference);
      if (data.description) formData.append('description', data.description);
      if (evidenceFile) formData.append('evidence_file', evidenceFile);
      if (receiptFile) formData.append('receipt_file', receiptFile);

      await apiClient.post('/savings/deposit', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      alert('Deposit recorded successfully');
      onCancel();
    } catch (err: any) {
      setError(err.response?.data?.message || err.message || 'An error occurred');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmitForm)}>
      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {error}
        </div>
      )}

      <FormSelect
        label="Member"
        name="member_id"
        register={register}
        error={errors.member_id}
        required
        disabled={isLoadingMembers}
        options={members.map((member) => ({
          value: member.id.toString(),
          label: `${member.member_number} - ${member.first_name} ${member.last_name}`,
        }))}
      />

      <FormInput
        label="Amount (UGX)"
        name="amount"
        type="number"
        placeholder="0"
        register={register}
        error={errors.amount}
        required
      />

      <FormInput
        label="Deposit Date"
        name="deposit_date"
        type="date"
        register={register}
        error={errors.deposit_date}
        required
      />

      <FormSelect
        label="Payment Method"
        name="source"
        register={register}
        error={errors.source}
        required
        options={[
          { value: 'cash', label: 'Cash' },
          { value: 'bank_transfer', label: 'Bank Transfer' },
          { value: 'mobile_money', label: 'Mobile Money' },
          { value: 'check', label: 'Check' },
        ]}
      />

      <FormInput
        label="Reference (Optional)"
        name="reference"
        placeholder="e.g., Check #, Transfer ID, Transaction Reference"
        register={register}
        error={errors.reference}
      />

      <FormInput
        label="Description (Optional)"
        name="description"
        placeholder="Additional notes about the deposit"
        register={register}
        error={errors.description}
      />

      {/* Bank Receipt Upload */}
      <div className="mb-4">
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Bank Receipt / Proof of Payment (Optional)
        </label>
        <input
          type="file"
          accept=".jpg,.jpeg,.png,.pdf"
          onChange={(e) => setReceiptFile(e.target.files?.[0] || null)}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
        />
        <p className="text-sm text-gray-500 mt-1">
          Upload bank receipt, mobile money confirmation, or other proof of payment. Max 2MB. Formats: JPG, PNG, PDF
        </p>
        {receiptFile && (
          <p className="text-sm text-green-600 mt-1">
            Selected: {receiptFile.name}
          </p>
        )}
      </div>

      {/* Evidence File for Non-Hospital Staff */}
      {selectedMember?.category === 'non_hospital_staff' && (
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Evidence File <span className="text-red-500">*</span>
          </label>
          <input
            type="file"
            accept=".jpg,.jpeg,.png,.pdf"
            onChange={(e) => setEvidenceFile(e.target.files?.[0] || null)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            required
          />
          <p className="text-sm text-gray-500 mt-1">
            Required for non-hospital staff deposits. Provide evidence of income source. Max 2MB. Formats: JPG, PNG, PDF
          </p>
          {evidenceFile && (
            <p className="text-sm text-green-600 mt-1">
              Selected: {evidenceFile.name}
            </p>
          )}
        </div>
      )}

      <div className="flex justify-end gap-3 mt-6">
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
          disabled={isSubmitting}
          className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {isSubmitting ? 'Processing...' : 'Record Deposit'}
        </button>
      </div>
    </form>
  );
};
