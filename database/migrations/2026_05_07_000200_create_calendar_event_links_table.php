<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_event_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id');
            $table->uuid('user_calendar_connection_id');
            $table->string('google_event_id');
            $table->string('google_event_etag')->nullable();
            $table->string('remote_status')->nullable();
            $table->timestamp('remote_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_synced_payload_hash')->nullable();
            $table->string('sync_status')->default('pending');
            $table->text('sync_error')->nullable();
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreign('user_calendar_connection_id')->references('id')->on('user_calendar_connections')->cascadeOnDelete();
            $table->unique(['task_id'], 'calendar_event_links_task_unique');
            $table->unique(['user_calendar_connection_id', 'google_event_id'], 'calendar_event_links_remote_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_links');
    }
};
