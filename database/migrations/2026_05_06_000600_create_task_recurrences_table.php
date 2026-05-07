<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_recurrences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id')->unique();
            $table->string('recurrence_type');
            $table->unsignedInteger('interval_value')->default(1);
            $table->string('day_of_week')->nullable();
            $table->unsignedInteger('day_of_month')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('occurrence_limit')->nullable();
            $table->json('rrule_payload')->nullable();
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_recurrences');
    }
};
