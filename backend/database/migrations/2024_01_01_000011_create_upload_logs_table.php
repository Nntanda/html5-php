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
        Schema::create('upload_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('upload_type'); // 'salary_deductions', 'loan_repayments', etc.
            $table->string('file_name');
            $table->integer('total_records');
            $table->integer('successful_records');
            $table->integer('failed_records');
            $table->decimal('total_amount_processed', 15, 2)->default(0);
            $table->string('salary_period')->nullable();
            $table->json('errors')->nullable();
            $table->json('summary')->nullable();
            $table->string('status'); // 'completed', 'failed', 'partial'
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('upload_type');
            $table->index('created_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_logs');
    }
};
