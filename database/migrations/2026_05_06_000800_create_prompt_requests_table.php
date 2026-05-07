<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('channel');
            $table->text('raw_text');
            $table->text('normalized_text')->nullable();
            $table->string('intent')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->string('parse_status');
            $table->json('extracted_entities')->nullable();
            $table->json('execution_summary')->nullable();
            $table->string('execution_status')->default('pending');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'created_at']);
            $table->index(['channel', 'execution_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_requests');
    }
};
