<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'loan_number' => Loan::generateLoanNumber(),
            'amount' => $this->faker->numberBetween(500000, 5000000),
            'interest_rate' => 15,
            'term_months' => $this->faker->numberBetween(6, 36),
            'purpose' => $this->faker->sentence(),
            'status' => Loan::STATUS_PENDING,
            'application_date' => now()->toDateString(),
            'monthly_repayment' => $this->faker->numberBetween(50000, 200000),
            'outstanding_balance' => $this->faker->numberBetween(500000, 5000000),
        ];
    }

    /**
     * Indicate that the loan is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Loan::STATUS_ACTIVE,
            'approval_date' => now()->subDays(30)->toDateString(),
            'disbursement_date' => now()->subDays(30)->toDateString(),
            'first_repayment_date' => now()->addMonth()->toDateString(),
        ]);
    }

    /**
     * Indicate that the loan is approved.
     */
    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Loan::STATUS_APPROVED_PENDING_DISBURSEMENT,
            'approval_date' => now()->toDateString(),
        ]);
    }

    /**
     * Indicate that the loan is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Loan::STATUS_OVERDUE,
            'approval_date' => now()->subMonths(3)->toDateString(),
            'disbursement_date' => now()->subMonths(3)->toDateString(),
            'first_repayment_date' => now()->subMonths(2)->toDateString(),
        ]);
    }
}
