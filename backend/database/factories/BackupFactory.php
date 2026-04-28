<?php

namespace Database\Factories;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Backup>
 */
class BackupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $timestamp = $this->faker->dateTime();

        return [
            'filename' => 'backup_' . $timestamp->format('Y-m-d_H-i-s') . '.sql',
            'file_path' => storage_path('backups/backup_' . $timestamp->format('Y-m-d_H-i-s') . '.sql'),
            'file_size' => $this->faker->numberBetween(1000000, 100000000),
            'status' => $this->faker->randomElement([
                Backup::STATUS_COMPLETED,
                Backup::STATUS_FAILED,
                Backup::STATUS_PENDING,
            ]),
            'backup_type' => $this->faker->randomElement([
                Backup::TYPE_MANUAL,
                Backup::TYPE_SCHEDULED,
            ]),
            'created_by' => User::factory(),
            'notes' => $this->faker->optional()->sentence(),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /**
     * Indicate that the backup is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Backup::STATUS_COMPLETED,
        ]);
    }

    /**
     * Indicate that the backup is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Backup::STATUS_FAILED,
        ]);
    }

    /**
     * Indicate that the backup is manual.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'backup_type' => Backup::TYPE_MANUAL,
        ]);
    }

    /**
     * Indicate that the backup is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'backup_type' => Backup::TYPE_SCHEDULED,
        ]);
    }
}
