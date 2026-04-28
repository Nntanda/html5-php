<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
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
            'type' => $this->faker->randomElement([
                Notification::TYPE_LOAN_APPLICATION_SUBMITTED,
                Notification::TYPE_GUARANTOR_REQUEST,
                Notification::TYPE_LOAN_APPROVED,
                Notification::TYPE_LOAN_REJECTED,
                Notification::TYPE_LOAN_DISBURSED,
                Notification::TYPE_REPAYMENT_RECEIVED,
                Notification::TYPE_PAYMENT_OVERDUE,
                Notification::TYPE_PAYMENT_REMINDER,
            ]),
            'channel' => $this->faker->randomElement([
                Notification::CHANNEL_EMAIL,
                Notification::CHANNEL_SMS,
                Notification::CHANNEL_IN_APP,
            ]),
            'subject' => $this->faker->sentence(),
            'message' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement([
                Notification::STATUS_PENDING,
                Notification::STATUS_SENT,
                Notification::STATUS_FAILED,
            ]),
            'is_read' => $this->faker->boolean(),
            'sent_at' => $this->faker->optional()->dateTime(),
            'read_at' => $this->faker->optional()->dateTime(),
            'retry_count' => $this->faker->numberBetween(0, 3),
        ];
    }
}
