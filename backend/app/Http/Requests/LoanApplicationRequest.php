<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoanApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'member_id' => 'required|exists:members,id',
            'amount' => 'required|numeric|min:1000|max:10000000',
            'term_months' => 'required|integer|min:1|max:60',
            'purpose' => 'required|string|max:500',
            'guarantors' => 'sometimes|array|min:1',
            'guarantors.*.member_id' => 'required|exists:members,id',
            'guarantors.*.guaranteed_amount' => 'required|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'member_id.required' => 'Member is required',
            'member_id.exists' => 'Selected member does not exist',
            'amount.required' => 'Loan amount is required',
            'amount.numeric' => 'Loan amount must be a number',
            'amount.min' => 'Minimum loan amount is 1,000',
            'amount.max' => 'Maximum loan amount is 10,000,000',
            'term_months.required' => 'Loan term is required',
            'term_months.integer' => 'Loan term must be a whole number',
            'term_months.min' => 'Minimum loan term is 1 month',
            'term_months.max' => 'Maximum loan term is 60 months',
            'purpose.required' => 'Loan purpose is required',
            'guarantors.*.member_id.required' => 'Guarantor member is required',
            'guarantors.*.member_id.exists' => 'Selected guarantor does not exist',
            'guarantors.*.guaranteed_amount.required' => 'Guaranteed amount is required',
        ];
    }
}
