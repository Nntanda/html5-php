<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\SavingsAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavingsAccountFactory extends Factory
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
            'account_number' => SavingsAccount::generateAccountNumber(),
            'balance' => fake()->numberBetween(0, 1000000),
        ];
    }
}
