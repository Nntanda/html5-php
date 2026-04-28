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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->string('loan_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('term_months');
            $table->text('purpose');
            $table->enum('status', [
                'pending',
                'guarantors_approved',
                'approved_pending_disbursement',
                'active',
                'closed',
                'rejected',
                'overdue'
            ])->default('pending');
            $table->enum('disbursement_method', ['bank_transfer', 'mobile_money', 'cash', 'cheque'])->nullable();
            $table->date('application_date');
            $table->date('approval_date')->nullable();
            $table->date('disbursement_date')->nullable();
            $table->date('first_repayment_date')->nullable();
            $table->decimal('outstanding_balance', 15, 2)->nullable();
            $table->decimal('monthly_repayment', 15, 2)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('disbursed_by')->nullable()->constrained('users');
            $table->text('approval_comment')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->index('loan_number');
            $table->index('member_id');
            $table->index('status');
            $table->index('application_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
