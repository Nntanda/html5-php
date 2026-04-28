<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_config', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('description')->nullable();
            $table->enum('type', ['string', 'number', 'boolean', 'json'])->default('string');
            $table->timestamp('updated_at');
            
            $table->index('key');
        });

        // Insert default configuration values
        DB::table('system_config')->insert([
            [
                'key' => 'loan_interest_rate',
                'value' => '12.00',
                'description' => 'Default loan interest rate (percentage)',
                'type' => 'number',
                'updated_at' => now()
            ],
            [
                'key' => 'late_payment_penalty_rate',
                'value' => '2.00',
                'description' => 'Late payment penalty rate (percentage)',
                'type' => 'number',
                'updated_at' => now()
            ],
            [
                'key' => 'max_loan_to_savings_ratio',
                'value' => '3.00',
                'description' => 'Maximum loan-to-savings ratio',
                'type' => 'number',
                'updated_at' => now()
            ],
            [
                'key' => 'min_guarantor_savings_percentage',
                'value' => '50.00',
                'description' => 'Minimum guarantor savings requirement (percentage)',
                'type' => 'number',
                'updated_at' => now()
            ],
            [
                'key' => 'loan_approval_threshold',
                'value' => '5000000',
                'description' => 'Loan amount requiring Super Admin approval (UGX)',
                'type' => 'number',
                'updated_at' => now()
            ],
            [
                'key' => 'session_timeout_hours',
                'value' => '8',
                'description' => 'Session timeout duration in hours',
                'type' => 'number',
                'updated_at' => now()
            ],
            [
                'key' => 'currency',
                'value' => 'UGX',
                'description' => 'System currency',
                'type' => 'string',
                'updated_at' => now()
            ],
            [
                'key' => 'min_guarantors_required',
                'value' => '2',
                'description' => 'Minimum number of guarantors required for loans above threshold',
                'type' => 'number',
                'updated_at' => now()
            ],
            [
                'key' => 'guarantor_threshold_amount',
                'value' => '1000000',
                'description' => 'Loan amount requiring guarantors (UGX)',
                'type' => 'number',
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_config');
    }
};
