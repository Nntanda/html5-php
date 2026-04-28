<?php

namespace Database\Factories;

use App\Models\UploadLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UploadLog>
 */
class UploadLogFactory extends Factory
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
            'upload_type' => UploadLog::TYPE_SALARY_DEDUCTIONS,
            'file_name' => 'salary_deductions_' . now()->format('YmdHis') . '.csv',
            'total_records' => $this->faker->numberBetween(10, 100),
            'successful_records' => $this->faker->numberBetween(5, 100),
            'failed_records' => $this->faker->numberBetween(0, 10),
            'total_amount_processed' => $this->faker->numberBetween(100000, 1000000),
            'salary_period' => $this->faker->monthName . ' ' . now()->year,
            'status' => UploadLog::STATUS_COMPLETED,
        ];
    }

    /**
     * Indicate that the upload was completed successfully.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UploadLog::STATUS_COMPLETED,
            'failed_records' => 0,
        ]);
    }

    /**
     * Indicate that the upload failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UploadLog::STATUS_FAILED,
            'successful_records' => 0,
            'error_message' => 'Upload processing failed',
        ]);
    }

    /**
     * Indicate that the upload was partially successful.
     */
    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UploadLog::STATUS_PARTIAL,
        ]);
    }
}
