<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->uuid('task_list_id')->nullable()->after('user_id');
            $table->foreign('task_list_id')->references('id')->on('task_lists')->nullOnDelete();
            $table->index('task_list_id');
        });

        // Task backfill removed; a later migration drops this domain.
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['task_list_id']);
            $table->dropIndex(['task_list_id']);
            $table->dropColumn('task_list_id');
        });
    }
};
