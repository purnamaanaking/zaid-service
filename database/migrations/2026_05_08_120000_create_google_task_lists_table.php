<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_task_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_calendar_connection_id');
            $table->string('google_task_list_id');
            $table->string('title')->nullable();
            $table->boolean('is_default')->default(false);
            $table->text('sync_token')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('user_calendar_connection_id')->references('id')->on('user_calendar_connections')->cascadeOnDelete();
            $table->unique(['user_calendar_connection_id', 'google_task_list_id'], 'google_task_lists_connection_list_unique');
            $table->index(['user_calendar_connection_id', 'is_default'], 'google_task_lists_connection_default_idx');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->string('google_task_list_id')->nullable()->after('source_prompt_request_id');
            $table->string('google_task_list_title')->nullable()->after('google_task_list_id');
        });

        Schema::table('calendar_event_links', function (Blueprint $table) {
            $table->string('google_task_list_id')->nullable()->after('google_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_event_links', function (Blueprint $table) {
            $table->dropColumn('google_task_list_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['google_task_list_id', 'google_task_list_title']);
        });

        Schema::dropIfExists('google_task_lists');
    }
};
