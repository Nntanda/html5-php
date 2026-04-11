<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_no', 20)->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('national_id', 20)->unique();
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('employer')->nullable();
            $table->string('branch')->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_phone', 20)->nullable();
            $table->string('next_of_kin_relationship')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->date('joined_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
