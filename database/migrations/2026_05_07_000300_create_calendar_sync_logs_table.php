<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('task_id')->nullable();
            $table->uuid('user_calendar_connection_id')->nullable();
            $table->uuid('calendar_event_link_id')->nullable();
            $table->string('direction');
            $table->string('action');
            $table->string('status');
            $table->json('context')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('task_id')->references('id')->on('tasks')->nullOnDelete();
            $table->foreign('user_calendar_connection_id')->references('id')->on('user_calendar_connections')->nullOnDelete();
            $table->foreign('calendar_event_link_id')->references('id')->on('calendar_event_links')->nullOnDelete();
            $table->index(['user_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_sync_logs');
    }
};
