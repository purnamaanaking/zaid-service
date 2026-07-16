<?php

use App\Models\TaskList;
use App\Models\User;
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

        User::query()->whereHas('tasks')->each(function (User $user): void {
            $list = TaskList::query()->create([
                'user_id' => $user->id,
                'name' => 'My Tasks',
                'position' => 0,
            ]);

            $user->tasks()->update(['task_list_id' => $list->id]);
        });
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
