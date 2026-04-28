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
        Schema::table('savings_transactions', function (Blueprint $table) {
            // Add status and approval fields first
            $table->enum('status', ['pending', 'approved', 'rejected', 'disputed', 'cancelled'])->default('approved')->after('reversed_at');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('rejection_reason');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->string('evidence_file')->nullable()->after('approved_at');
            $table->string('receipt_file')->nullable()->after('evidence_file');
            
            // Dispute fields
            $table->string('dispute_reason')->nullable()->after('receipt_file');
            $table->text('dispute_description')->nullable()->after('dispute_reason');
            $table->timestamp('disputed_at')->nullable()->after('dispute_description');
            $table->unsignedBigInteger('disputed_by')->nullable()->after('disputed_at');
            
            // Cancellation fields
            $table->timestamp('cancelled_at')->nullable()->after('disputed_by');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            
            // Related transaction field
            $table->unsignedBigInteger('related_transaction_id')->nullable()->after('cancelled_by');
            
            // Add foreign key constraints
            $table->foreign('disputed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('related_transaction_id')->references('id')->on('savings_transactions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['disputed_by']);
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['related_transaction_id']);
            
            $table->dropColumn([
                'status',
                'rejection_reason',
                'approved_by',
                'approved_at',
                'evidence_file',
                'receipt_file',
                'dispute_reason',
                'dispute_description',
                'disputed_at',
                'disputed_by',
                'cancelled_at',
                'cancelled_by',
                'related_transaction_id',
            ]);
        });
    }
};