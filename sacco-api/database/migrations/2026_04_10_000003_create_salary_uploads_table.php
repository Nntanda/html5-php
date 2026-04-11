<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->unsignedTinyInteger('period_month'); // 1-12
            $table->unsignedSmallInteger('period_year');
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('processed_records')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_uploads');
    }
};
