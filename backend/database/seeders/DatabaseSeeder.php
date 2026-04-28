<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Super Admin user
        User::updateOrCreate(
            ['email' => 'admin@sacco.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );

        // Create Loan Officer user
        User::updateOrCreate(
            ['email' => 'loan@sacco.local'],
            [
                'name' => 'Loan Officer',
                'password' => Hash::make('password'),
                'role' => 'loan_officer',
                'status' => 'active',
            ]
        );

        // Create Accountant user
        User::updateOrCreate(
            ['email' => 'accountant@sacco.local'],
            [
                'name' => 'Accountant',
                'password' => Hash::make('password'),
                'role' => 'accountant',
                'status' => 'active',
            ]
        );

        // Create system configuration
        $configs = [
            ['key' => 'loan_multiplier', 'value' => '3', 'description' => 'Loan amount multiplier based on savings', 'type' => 'number', 'updated_at' => now()],
            ['key' => 'min_guarantors', 'value' => '2', 'description' => 'Minimum number of guarantors required', 'type' => 'number', 'updated_at' => now()],
            ['key' => 'interest_rate', 'value' => '12', 'description' => 'Annual interest rate percentage', 'type' => 'number', 'updated_at' => now()],
            ['key' => 'currency', 'value' => 'UGX', 'description' => 'System currency', 'type' => 'string', 'updated_at' => now()],
            ['key' => 'sms_enabled', 'value' => 'false', 'description' => 'Enable SMS notifications', 'type' => 'boolean', 'updated_at' => now()],
        ];

        foreach ($configs as $config) {
            DB::table('system_config')->updateOrInsert(
                ['key' => $config['key']],
                $config
            );
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('');
        $this->command->info('Default users created:');
        $this->command->info('Super Admin: admin@sacco.local / password');
        $this->command->info('Loan Officer: loan@sacco.local / password');
        $this->command->info('Accountant: accountant@sacco.local / password');
        $this->command->info('');

        // Seed members
        $this->call(MemberSeeder::class);
    }
}
