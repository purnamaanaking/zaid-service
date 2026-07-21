<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('reminders')->whereNotNull('task_id')->delete();
        DB::table('calendar_sync_logs')->delete();
        DB::table('calendar_event_links')->delete();

        Schema::table('reminders', function (Blueprint $table): void { $table->dropForeign(['task_id']); $table->dropColumn('task_id'); });
        Schema::table('calendar_sync_logs', function (Blueprint $table): void { $table->dropForeign(['task_id']); $table->dropColumn('task_id'); $table->uuid('calendar_event_id')->nullable()->after('user_id'); $table->foreign('calendar_event_id')->references('id')->on('calendar_events')->nullOnDelete(); });
        Schema::table('calendar_event_links', function (Blueprint $table): void { $table->dropForeign(['task_id']); $table->dropUnique('calendar_event_links_task_unique'); $table->dropColumn('task_id'); $table->uuid('calendar_event_id')->nullable()->after('id'); $table->foreign('calendar_event_id')->references('id')->on('calendar_events')->cascadeOnDelete(); $table->unique('calendar_event_id'); });
        Schema::table('user_calendar_connections', function (Blueprint $table): void { $table->dropColumn(['google_task_list_id', 'tasks_sync_token']); });

        Schema::dropIfExists('google_task_lists');
        Schema::dropIfExists('task_changes');
        Schema::dropIfExists('task_recurrences');
        Schema::table('tasks', function (Blueprint $table): void { $table->dropForeign(['task_list_id']); $table->dropIndex(['task_list_id']); });
        Schema::dropIfExists('task_lists');
        Schema::dropIfExists('tasks');
    }

    public function down(): void
    {
        throw new RuntimeException('Task data deletion cannot be reversed.');
    }
};
