<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanGuarantor;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanGuarantor>
 */
class LoanGuarantorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'guarantor_member_id' => Member::factory(),
            'guaranteed_amount' => $this->faker->numberBetween(500000, 2000000),
            'status' => LoanGuarantor::STATUS_PENDING,
        ];
    }

    /**
     * Indicate that the guarantor has accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => LoanGuarantor::STATUS_ACCEPTED,
            'approval_date' => now()->toDateString(),
        ]);
    }

    /**
     * Indicate that the guarantor has rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => LoanGuarantor::STATUS_REJECTED,
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }
}
