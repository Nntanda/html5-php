import React, { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { apiClient } from '../api/client';
import { FormSelect } from './FormSelect';

const savingsAccountSchema = z.object({
  member_id: z.string().min(1, 'Member is required'),
});

type SavingsAccountFormData = z.infer<typeof savingsAccountSchema>;

interface Member {
  id: number;
  first_name: string;
  last_name: string;
  member_number: string;
}

interface SavingsAccountFormProps {
  onSubmit: (data: any) => Promise<void>;
  onCancel: () => void;
}

export const SavingsAccountForm: React.FC<SavingsAccountFormProps> = ({
  onSubmit,
  onCancel,
}) => {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [members, setMembers] = useState<Member[]>([]);
  const [isLoadingMembers, setIsLoadingMembers] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<SavingsAccountFormData>({
    resolver: zodResolver(savingsAccountSchema),
  });

  useEffect(() => {
    const fetchMembers = async () => {
      setIsLoadingMembers(true);
      try {
        const response = await apiClient.get<{
          data: Member[];
        }>('/members', { params: { per_page: 100 } });
        setMembers(response.data.data);
      } catch (err: any) {
        setError('Failed to load members');
      } finally {
        setIsLoadingMembers(false);
      }
    };
    fetchMembers();
  }, []);

  const onSubmitForm = async (data: SavingsAccountFormData) => {
    setIsSubmitting(true);
    setError(null);
    try {
      await onSubmit({
        member_id: parseInt(data.member_id),
      });
    } catch (err: any) {
      setError(err.message || 'An error occurred');
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
          {isSubmitting ? 'Creating...' : 'Create Account'}
        </button>
      </div>
    </form>
  );
};
