import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { User, UserRole, UserStatus } from '../types';
import { FormInput } from './FormInput';
import { FormSelect } from './FormSelect';

const userSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters'),
  email: z.string().email('Invalid email address'),
  password: z.string().optional(),
  role: z.enum(['SuperAdmin', 'LoanOfficer', 'Accountant', 'Member']),
  status: z.enum(['active', 'inactive', 'suspended']),
});

type UserFormData = z.infer<typeof userSchema>;

interface UserFormProps {
  initialData?: User;
  onSubmit: (data: any) => Promise<void>;
  onCancel: () => void;
  isEdit?: boolean;
}

export const UserForm: React.FC<UserFormProps> = ({
  initialData,
  onSubmit,
  onCancel,
  isEdit = false,
}) => {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<UserFormData>({
    resolver: zodResolver(userSchema),
    defaultValues: initialData
      ? {
          name: initialData.name,
          email: initialData.email,
          role: initialData.role,
          status: initialData.status,
        }
      : undefined,
  });

  const onSubmitForm = async (data: UserFormData) => {
    setIsSubmitting(true);
    setError(null);
    try {
      await onSubmit(data);
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

      <FormInput
        label="Name"
        name="name"
        register={register}
        error={errors.name}
        required
      />

      <FormInput
        label="Email"
        name="email"
        type="email"
        register={register}
        error={errors.email}
        required
      />

      {!isEdit && (
        <FormInput
          label="Password"
          name="password"
          type="password"
          register={register}
          error={errors.password}
          required
        />
      )}

      <FormSelect
        label="Role"
        name="role"
        register={register}
        error={errors.role}
        required
        options={[
          { value: 'SuperAdmin', label: 'Super Admin' },
          { value: 'LoanOfficer', label: 'Loan Officer' },
          { value: 'Accountant', label: 'Accountant' },
          { value: 'Member', label: 'Member' },
        ]}
      />

      <FormSelect
        label="Status"
        name="status"
        register={register}
        error={errors.status}
        required
        options={[
          { value: 'active', label: 'Active' },
          { value: 'inactive', label: 'Inactive' },
          { value: 'suspended', label: 'Suspended' },
        ]}
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
          {isSubmitting ? 'Saving...' : isEdit ? 'Update User' : 'Create User'}
        </button>
      </div>
    </form>
  );
};
