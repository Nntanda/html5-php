<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement([
                AuditLog::ACTION_CREATE,
                AuditLog::ACTION_UPDATE,
                AuditLog::ACTION_DELETE,
                AuditLog::ACTION_APPROVE,
                AuditLog::ACTION_REJECT,
                AuditLog::ACTION_DISBURSE,
            ]),
            'entity_type' => $this->faker->randomElement([
                'Loan',
                'Member',
                'User',
                'SavingsTransaction',
                'LoanRepayment',
            ]),
            'entity_id' => $this->faker->numberBetween(1, 1000),
            'changes' => [
                'field' => 'value',
                'old_value' => 'old',
                'new_value' => 'new',
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}
