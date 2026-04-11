<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_approved', 15, 2)->nullable();
            $table->unsignedTinyInteger('duration_months');
            $table->decimal('interest_rate', 5, 2)->default(12.00); // Annual %
            $table->text('purpose');
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'disbursed', 'completed', 'defaulted'])->default('pending');
            $table->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
