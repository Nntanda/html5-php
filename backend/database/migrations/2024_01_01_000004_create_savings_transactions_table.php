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
        Schema::create('savings_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('savings_accounts')->onDelete('cascade');
            $table->enum('type', ['salary_savings', 'direct_deposit', 'withdrawal', 'reversal']);
            $table->decimal('amount', 15, 2);
            $table->enum('source', ['salary', 'cash', 'bank_transfer', 'mobile_money'])->nullable();
            $table->string('reference')->unique();
            $table->date('transaction_date');
            $table->text('description')->nullable();
            $table->string('salary_period')->nullable();
            $table->string('employer_reference')->nullable();
            $table->boolean('is_reversed')->default(false);
            $table->foreignId('reversed_by')->nullable()->constrained('users');
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
            
            $table->index('account_id');
            $table->index('type');
            $table->index('transaction_date');
            $table->index('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_transactions');
    }
};
