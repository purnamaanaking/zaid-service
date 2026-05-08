<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_calendar_connections', function (Blueprint $table) {
            $table->string('watch_channel_id')->nullable()->after('sync_token');
            $table->string('watch_resource_id')->nullable()->after('watch_channel_id');
            $table->timestamp('watch_expiry')->nullable()->after('watch_resource_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_connections', function (Blueprint $table) {
            $table->dropColumn(['watch_channel_id', 'watch_resource_id', 'watch_expiry']);
        });
    }
};
