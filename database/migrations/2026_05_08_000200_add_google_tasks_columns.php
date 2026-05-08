<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_calendar_connections', function (Blueprint $table) {
            $table->string('google_task_list_id')->nullable()->after('google_calendar_summary');
            $table->text('tasks_sync_token')->nullable()->after('sync_token');
        });

        Schema::table('calendar_event_links', function (Blueprint $table) {
            $table->string('link_type')->default('calendar_event')->after('user_calendar_connection_id');
            // google_event_id is reused for google task id too, rename would break things
            // so we keep it as-is and use link_type to distinguish
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_connections', function (Blueprint $table) {
            $table->dropColumn(['google_task_list_id', 'tasks_sync_token']);
        });

        Schema::table('calendar_event_links', function (Blueprint $table) {
            $table->dropColumn('link_type');
        });
    }
};
