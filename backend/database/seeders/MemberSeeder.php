<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Member;
use App\Models\SavingsAccount;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = [
            [
                'full_name' => 'John Mukasa',
                'gender' => 'male',
                'marital_status' => 'married',
                'nationality' => 'Ugandan',
                'village' => 'Nakawa',
                'district' => 'Kampala',
                'mobile_contact' => '+256 700 123 456',
                'email' => 'john.mukasa@example.com',
                'national_id' => 'CM90001234567',
                'category' => 'staff',
                'next_of_kin' => [
                    'name' => 'Mary Mukasa',
                    'residence' => 'Nakawa, Kampala',
                    'contact' => '+256 700 123 457',
                    'relationship' => 'spouse',
                ],
                'occupation' => 'Teacher',
                'source_of_income' => 'salaried',
                'organization' => 'Kitovu Hospital',
                'job_title' => 'Senior Teacher',
                'bank_details' => [
                    'account_name' => 'John Mukasa',
                    'account_number' => '1234567890',
                    'bank_name' => 'Stanbic Bank',
                    'bank_location' => 'Kampala Road Branch',
                ],
                'monthly_savings' => 50000,
            ],
            [
                'full_name' => 'Sarah Namukasa',
                'gender' => 'female',
                'marital_status' => 'single',
                'nationality' => 'Ugandan',
                'village' => 'Ntinda',
                'district' => 'Kampala',
                'mobile_contact' => '+256 701 234 567',
                'email' => 'sarah.namukasa@example.com',
                'national_id' => 'CF90002345678',
                'category' => 'nursing_school',
                'next_of_kin' => [
                    'name' => 'Peter Namukasa',
                    'residence' => 'Ntinda, Kampala',
                    'contact' => '+256 701 234 568',
                    'relationship' => 'sibling',
                ],
                'occupation' => 'Nurse',
                'source_of_income' => 'salaried',
                'organization' => 'Kitovu Hospital Nursing School',
                'job_title' => 'Registered Nurse',
                'bank_details' => [
                    'account_name' => 'Sarah Namukasa',
                    'account_number' => '2345678901',
                    'bank_name' => 'Centenary Bank',
                    'bank_location' => 'Ntinda Branch',
                ],
                'monthly_savings' => 75000,
            ],
            [
                'full_name' => 'David Okello',
                'gender' => 'male',
                'marital_status' => 'married',
                'nationality' => 'Ugandan',
                'village' => 'Bugolobi',
                'district' => 'Kampala',
                'mobile_contact' => '+256 702 345 678',
                'email' => 'david.okello@example.com',
                'national_id' => 'CM90003456789',
                'category' => 'act_program',
                'next_of_kin' => [
                    'name' => 'Grace Okello',
                    'residence' => 'Bugolobi, Kampala',
                    'contact' => '+256 702 345 679',
                    'relationship' => 'spouse',
                ],
                'occupation' => 'Accountant',
                'source_of_income' => 'salaried',
                'organization' => 'Kitovu Hospital ACT Program',
                'job_title' => 'Senior Accountant',
                'bank_details' => [
                    'account_name' => 'David Okello',
                    'account_number' => '3456789012',
                    'bank_name' => 'DFCU Bank',
                    'bank_location' => 'Bugolobi Branch',
                ],
                'monthly_savings' => 100000,
            ],
            [
                'full_name' => 'Rebecca Nabirye',
                'gender' => 'female',
                'marital_status' => 'divorced',
                'nationality' => 'Ugandan',
                'village' => 'Muyenga',
                'district' => 'Kampala',
                'mobile_contact' => '+256 703 456 789',
                'email' => 'rebecca.nabirye@example.com',
                'national_id' => 'CF90004567890',
                'category' => 'non_hospital_staff',
                'next_of_kin' => [
                    'name' => 'James Nabirye',
                    'residence' => 'Muyenga, Kampala',
                    'contact' => '+256 703 456 790',
                    'relationship' => 'parent',
                ],
                'occupation' => 'Business Owner',
                'source_of_income' => 'self-employed',
                'organization' => 'Nabirye Enterprises',
                'job_title' => 'Managing Director',
                'bank_details' => [
                    'account_name' => 'Rebecca Nabirye',
                    'account_number' => '4567890123',
                    'bank_name' => 'Bank of Africa',
                    'bank_location' => 'Muyenga Branch',
                ],
                'monthly_savings' => 150000,
            ],
            [
                'full_name' => 'Patrick Ssemakula',
                'gender' => 'male',
                'marital_status' => 'single',
                'nationality' => 'Ugandan',
                'village' => 'Kololo',
                'district' => 'Kampala',
                'mobile_contact' => '+256 704 567 890',
                'email' => 'patrick.ssemakula@example.com',
                'national_id' => 'CM90005678901',
                'category' => 'hc_staff',
                'next_of_kin' => [
                    'name' => 'Agnes Ssemakula',
                    'residence' => 'Kololo, Kampala',
                    'contact' => '+256 704 567 891',
                    'relationship' => 'parent',
                ],
                'occupation' => 'Health Center Staff',
                'source_of_income' => 'salaried',
                'organization' => 'Kitovu Health Center',
                'job_title' => 'Senior Health Worker',
                'bank_details' => [
                    'account_name' => 'Patrick Ssemakula',
                    'account_number' => '5678901234',
                    'bank_name' => 'Standard Chartered Bank',
                    'bank_location' => 'Kololo Branch',
                ],
                'monthly_savings' => 120000,
            ],
        ];

        $this->command->info('Creating members...');

        foreach ($members as $index => $memberData) {
            DB::beginTransaction();
            try {
                // Check if user already exists
                $existingUser = User::where('email', $memberData['email'])->first();
                if ($existingUser) {
                    $this->command->warn("User {$memberData['email']} already exists. Skipping...");
                    DB::rollBack();
                    continue;
                }

                // Generate member number
                $lastMember = Member::orderBy('id', 'desc')->first();
                $nextNumber = $lastMember ? intval(substr($lastMember->member_number, 3)) + 1 : 1;
                $memberNumber = 'MEM' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

                // Split full name
                $nameParts = explode(' ', $memberData['full_name'], 2);
                $firstName = $nameParts[0];
                $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

                // Create user account
                $user = User::create([
                    'name' => $memberData['full_name'],
                    'email' => $memberData['email'],
                    'password' => Hash::make('password123'),
                    'role' => 'member',
                    'status' => 'active',
                ]);

                // Prepare employment info
                $employmentInfo = [
                    'occupation' => $memberData['occupation'],
                    'source_of_income' => $memberData['source_of_income'],
                    'organization' => $memberData['organization'],
                    'job_title' => $memberData['job_title'],
                    'marital_status' => $memberData['marital_status'],
                    'nationality' => $memberData['nationality'],
                    'village' => $memberData['village'],
                    'district' => $memberData['district'],
                    'next_of_kin' => $memberData['next_of_kin'],
                    'bank_details' => $memberData['bank_details'],
                    'fees' => [
                        'entrance_fee' => 10000,
                        'passbook_fee' => 5000,
                        'monthly_savings' => $memberData['monthly_savings'],
                        'monthly_savings_words' => $this->numberToWords($memberData['monthly_savings']) . ' Shillings Only',
                    ],
                ];

                // Add referee for members after the first one
                if ($index > 0) {
                    $previousMember = Member::orderBy('id', 'desc')->first();
                    $employmentInfo['referee'] = [
                        'name' => $previousMember->first_name . ' ' . $previousMember->last_name,
                        'contact' => $previousMember->phone,
                        'member_number' => $previousMember->member_number,
                    ];
                }

                // Create member
                $member = Member::create([
                    'user_id' => $user->id,
                    'member_number' => $memberNumber,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'national_id' => $memberData['national_id'],
                    'email' => $memberData['email'],
                    'phone' => $memberData['mobile_contact'],
                    'address' => $memberData['village'] . ', ' . $memberData['district'],
                    'employment_info' => $employmentInfo,
                    'status' => 'active',
                    'category' => $memberData['category'],
                ]);

                // Create savings account with initial balance
                $initialBalance = $memberData['monthly_savings'] * rand(3, 12); // 3-12 months of savings
                SavingsAccount::create([
                    'member_id' => $member->id,
                    'account_number' => 'SAV' . str_pad($member->id, 8, '0', STR_PAD_LEFT),
                    'balance' => $initialBalance,
                ]);

                DB::commit();
                $this->command->info("✓ Created member: {$memberData['full_name']} ({$memberNumber}) with balance UGX " . number_format($initialBalance));
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error("✗ Failed to create member {$memberData['full_name']}: " . $e->getMessage());
            }
        }

        $this->command->info('');
        $this->command->info('Member seeding completed!');
        $this->command->info('All members have password: password123');
    }

    /**
     * Convert number to words (simplified for Ugandan Shillings)
     */
    private function numberToWords($number): string
    {
        $words = [
            0 => 'Zero',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            20 => 'Twenty',
            30 => 'Thirty',
            40 => 'Forty',
            50 => 'Fifty',
            60 => 'Sixty',
            70 => 'Seventy',
            80 => 'Eighty',
            90 => 'Ninety',
            100 => 'One Hundred',
            1000 => 'One Thousand',
        ];

        if ($number < 10) {
            return $words[$number];
        } elseif ($number < 100) {
            $tens = floor($number / 10) * 10;
            $ones = $number % 10;
            return $words[$tens] . ($ones > 0 ? ' ' . $words[$ones] : '');
        } elseif ($number < 1000) {
            $hundreds = floor($number / 100);
            $remainder = $number % 100;
            return $words[$hundreds] . ' Hundred' . ($remainder > 0 ? ' and ' . $this->numberToWords($remainder) : '');
        } else {
            $thousands = floor($number / 1000);
            $remainder = $number % 1000;
            return $this->numberToWords($thousands) . ' Thousand' . ($remainder > 0 ? ' ' . $this->numberToWords($remainder) : '');
        }
    }
}
