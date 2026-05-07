<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prompt_request_id');
            $table->string('action_type');
            $table->string('target_entity_type')->default('task');
            $table->uuid('target_entity_id')->nullable();
            $table->unsignedInteger('execution_order')->default(1);
            $table->string('status')->default('pending');
            $table->json('payload')->nullable();
            $table->json('result_payload')->nullable();
            $table->timestamps();

            $table->foreign('prompt_request_id')->references('id')->on('prompt_requests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_actions');
    }
};
