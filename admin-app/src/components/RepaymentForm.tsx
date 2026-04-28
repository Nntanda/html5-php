import React, { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { apiClient } from '../api/client';
import { FormInput } from './FormInput';
import { FormSelect } from './FormSelect';

const repaymentSchema = z.object({
  loan_id: z.string().min(1, 'Loan is required'),
  amount: z.string().min(1, 'Amount is required').refine(
    (val) => !isNaN(parseFloat(val)) && parseFloat(val) > 0,
    'Amount must be a positive number'
  ),
  reference: z.string().optional(),
});

type RepaymentFormData = z.infer<typeof repaymentSchema>;

interface Loan {
  id: number;
  loan_number: string;
  member_name: string;
  amount: number;
  outstanding_balance: number;
  status: string;
}

interface RepaymentReceipt {
  id: number;
  loan_id: number;
  amount: number;
  payment_date: string;
  reference: string;
  outstanding_balance: number;
}

interface RepaymentFormProps {
  onSubmit: (data: any) => Promise<void>;
  onCancel: () => void;
}

export const RepaymentForm: React.FC<RepaymentFormProps> = ({
  onSubmit,
  onCancel,
}) => {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loans, setLoans] = useState<Loan[]>([]);
  const [isLoadingLoans, setIsLoadingLoans] = useState(false);
  const [selectedLoan, setSelectedLoan] = useState<Loan | null>(null);
  const [receipt, setReceipt] = useState<RepaymentReceipt | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
    watch,
    reset,
  } = useForm<RepaymentFormData>({
    resolver: zodResolver(repaymentSchema),
  });

  const loanId = watch('loan_id');

  useEffect(() => {
    const fetchLoans = async () => {
      setIsLoadingLoans(true);
      try {
        const response = await apiClient.get<{
          data: Loan[];
        }>('/loans', { params: { per_page: 100, status: 'active' } });
        setLoans(response.data.data);
      } catch (err: any) {
        setError('Failed to load loans');
      } finally {
        setIsLoadingLoans(false);
      }
    };
    fetchLoans();
  }, []);

  useEffect(() => {
    if (loanId) {
      const loan = loans.find((l) => l.id.toString() === loanId);
      setSelectedLoan(loan || null);
    } else {
      setSelectedLoan(null);
    }
  }, [loanId, loans]);

  const onSubmitForm = async (data: RepaymentFormData) => {
    setIsSubmitting(true);
    setError(null);
    try {
      const response = await apiClient.post<{ data: RepaymentReceipt }>(
        `/loans/${data.loan_id}/repayments`,
        {
          amount: parseFloat(data.amount),
          reference: data.reference || '',
        }
      );
      setReceipt(response.data.data);
      reset();
      setSelectedLoan(null);
    } catch (err: any) {
      setError(err.response?.data?.message || 'An error occurred');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (receipt) {
    return (
      <div>
        <div className="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
          <h3 className="text-lg font-semibold text-green-800 mb-4">
            ✓ Repayment Recorded Successfully
          </h3>
          <div className="space-y-3">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <p className="text-sm text-gray-600">Repayment ID</p>
                <p className="text-lg font-semibold text-gray-900">
                  {receipt.id}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Payment Date</p>
                <p className="text-lg font-semibold text-gray-900">
                  {new Date(receipt.payment_date).toLocaleDateString()}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Amount Paid</p>
                <p className="text-lg font-semibold text-gray-900">
                  {new Intl.NumberFormat('en-UG', {
                    style: 'currency',
                    currency: 'UGX',
                    minimumFractionDigits: 0,
                  }).format(receipt.amount)}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Outstanding Balance</p>
                <p className="text-lg font-semibold text-gray-900">
                  {new Intl.NumberFormat('en-UG', {
                    style: 'currency',
                    currency: 'UGX',
                    minimumFractionDigits: 0,
                  }).format(receipt.outstanding_balance)}
                </p>
              </div>
              {receipt.reference && (
                <div className="col-span-2">
                  <p className="text-sm text-gray-600">Reference</p>
                  <p className="text-lg font-semibold text-gray-900">
                    {receipt.reference}
                  </p>
                </div>
              )}
            </div>
          </div>
        </div>

        <div className="flex justify-end gap-3">
          <button
            type="button"
            onClick={() => {
              setReceipt(null);
              onCancel();
            }}
            className="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
          >
            Close
          </button>
          <button
            type="button"
            onClick={() => setReceipt(null)}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            Record Another Repayment
          </button>
        </div>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit(onSubmitForm)}>
      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {error}
        </div>
      )}

      <FormSelect
        label="Loan"
        name="loan_id"
        register={register}
        error={errors.loan_id}
        required
        disabled={isLoadingLoans}
        options={loans.map((loan) => ({
          value: loan.id.toString(),
          label: `${loan.loan_number} - ${loan.member_name}`,
        }))}
      />

      {selectedLoan && (
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-gray-600">Loan Amount</p>
              <p className="text-lg font-semibold text-gray-900">
                {new Intl.NumberFormat('en-UG', {
                  style: 'currency',
                  currency: 'UGX',
                  minimumFractionDigits: 0,
                }).format(selectedLoan.amount)}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Outstanding Balance</p>
              <p className="text-lg font-semibold text-gray-900">
                {new Intl.NumberFormat('en-UG', {
                  style: 'currency',
                  currency: 'UGX',
                  minimumFractionDigits: 0,
                }).format(selectedLoan.outstanding_balance)}
              </p>
            </div>
          </div>
        </div>
      )}

      <FormInput
        label="Repayment Amount"
        name="amount"
        type="number"
        placeholder="0.00"
        register={register}
        error={errors.amount}
        required
      />

      <FormInput
        label="Reference (Optional)"
        name="reference"
        placeholder="e.g., Check #, Transfer ID"
        register={register}
        error={errors.reference}
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
          {isSubmitting ? 'Processing...' : 'Record Repayment'}
        </button>
      </div>
    </form>
  );
};
