<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanRepayment>
 */
class LoanRepaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->numberBetween(50000, 200000);
        $principalAmount = $amount * 0.7;
        $interestAmount = $amount * 0.3;

        return [
            'loan_id' => Loan::factory(),
            'amount' => $amount,
            'principal_amount' => $principalAmount,
            'interest_amount' => $interestAmount,
            'penalty_amount' => 0,
            'payment_date' => now()->toDateString(),
            'source' => LoanRepayment::SOURCE_MANUAL,
            'reference' => LoanRepayment::generateReference(),
            'recorded_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the repayment is from salary deduction.
     */
    public function salaryDeduction(): static
    {
        return $this->state(fn(array $attributes) => [
            'source' => LoanRepayment::SOURCE_SALARY_DEDUCTION,
        ]);
    }

    /**
     * Indicate that the repayment has a penalty.
     */
    public function withPenalty(): static
    {
        return $this->state(fn(array $attributes) => [
            'penalty_amount' => $attributes['amount'] * 0.05,
        ]);
    }
}
