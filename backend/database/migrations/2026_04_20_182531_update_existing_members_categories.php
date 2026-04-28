<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Member;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $members = [
            ['email' => 'john.mukasa@example.com', 'category' => 'staff'],
            ['email' => 'sarah.namukasa@example.com', 'category' => 'nursing_school'],
            ['email' => 'david.okello@example.com', 'category' => 'act_program'],
            ['email' => 'rebecca.nabirye@example.com', 'category' => 'non_hospital_staff'],
            ['email' => 'patrick.ssemakula@example.com', 'category' => 'hc_staff']
        ];

        foreach ($members as $memberData) {
            $member = Member::where('email', $memberData['email'])->first();
            if ($member) {
                $member->update(['category' => $memberData['category']]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all categories to null
        Member::whereNotNull('category')->update(['category' => null]);
    }
};
