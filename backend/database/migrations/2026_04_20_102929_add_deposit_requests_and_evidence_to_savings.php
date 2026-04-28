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
        // Add fields to savings_transactions for approval workflow and evidence
        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved')->after('type');
            $table->string('evidence_file')->nullable()->after('description');
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('reversed_by');
            $table->timestamp('approved_at')->nullable()->after('reversed_at');
            $table->text('rejection_reason')->nullable()->after('approved_at');
        });

        // Create withdrawal_requests table
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained('savings_accounts')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money']);
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->index('member_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status', 'evidence_file', 'approved_by', 'approved_at', 'rejection_reason']);
        });
        
        Schema::dropIfExists('withdrawal_requests');
    }
};
