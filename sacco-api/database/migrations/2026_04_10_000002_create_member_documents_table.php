<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('type'); // national_id, passport_photo, employment_letter, etc.
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_documents');
    }
};
