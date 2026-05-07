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
        Schema::create('otp_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_phone_id');
            $table->uuid('phone_verification_id')->nullable();
            $table->string('attempt_type');
            $table->string('status');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('user_phone_id')->references('id')->on('user_phones')->cascadeOnDelete();
            $table->foreign('phone_verification_id')->references('id')->on('phone_verifications')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_attempts');
    }
};
