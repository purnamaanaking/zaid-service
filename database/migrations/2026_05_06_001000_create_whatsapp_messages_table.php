<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->uuid('prompt_request_id')->nullable();
            $table->string('direction');
            $table->string('wa_message_id')->unique();
            $table->string('sender_phone_e164');
            $table->string('recipient_phone_e164');
            $table->text('message_text')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->string('processing_status')->default('received');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('prompt_request_id')->references('id')->on('prompt_requests')->nullOnDelete();
            $table->index(['sender_phone_e164', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
