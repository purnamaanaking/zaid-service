<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->string('location')->nullable()->after('description');
            $table->json('participants')->nullable()->after('location');
            $table->string('category')->nullable()->after('participants');
            $table->string('priority')->nullable()->after('category');
            $table->string('color')->nullable()->after('priority');
            $table->json('recurrence')->nullable()->after('color');
            $table->string('status')->default('scheduled')->after('all_day');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropColumn(['location', 'participants', 'category', 'priority', 'color', 'recurrence', 'status']);
        });
    }
};
