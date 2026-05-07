<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Task;
use App\Models\TaskRecurrence;
use App\Models\User;
use App\Models\UserIdentity;
use App\Models\UserPhone;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo user (fully onboarded)
        $user = User::factory()->active()->create([
            'google_subject' => 'demo-google-subject',
            'email' => 'demo@zaid.app',
            'full_name' => 'Zaid Demo User',
            'avatar_url' => null,
        ]);

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_subject' => 'demo-google-subject',
            'provider_email' => 'demo@zaid.app',
        ]);

        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+628123456789',
            'phone_local' => '08123456789',
            'country_code' => 'ID',
            'is_primary' => true,
            'is_verified' => true,
            'verified_at' => now(),
            'linked_for_whatsapp_at' => now(),
        ]);

        UserSetting::query()->create([
            'user_id' => $user->id,
            'theme' => 'light',
            'timezone' => 'Asia/Jakarta',
            'default_task_time' => '09:00:00',
            'reminder_offset_minutes' => 30,
            'reminder_enabled' => true,
        ]);

        // Sample tasks
        $today = now();

        $task1 = Task::query()->create([
            'user_id' => $user->id,
            'source_channel' => 'app_manual',
            'title' => 'Standup meeting',
            'description' => 'Daily standup dengan tim',
            'status' => 'pending',
            'scheduled_date' => $today->format('Y-m-d'),
            'scheduled_time' => '09:00:00',
            'timezone' => 'Asia/Jakarta',
            'is_recurring' => true,
        ]);

        TaskRecurrence::query()->create([
            'task_id' => $task1->id,
            'recurrence_type' => 'daily',
            'interval_value' => 1,
        ]);

        Task::query()->create([
            'user_id' => $user->id,
            'source_channel' => 'app_manual',
            'title' => 'Review laporan penjualan',
            'description' => 'Cek laporan mingguan',
            'status' => 'pending',
            'scheduled_date' => $today->format('Y-m-d'),
            'scheduled_time' => '14:00:00',
            'timezone' => 'Asia/Jakarta',
        ]);

        Task::query()->create([
            'user_id' => $user->id,
            'source_channel' => 'whatsapp',
            'title' => 'Follow up client ABC',
            'status' => 'pending',
            'scheduled_date' => $today->addDays(1)->format('Y-m-d'),
            'scheduled_time' => '10:00:00',
            'timezone' => 'Asia/Jakarta',
        ]);

        $task4 = Task::query()->create([
            'user_id' => $user->id,
            'source_channel' => 'app_prompt',
            'title' => 'Laporan bulanan',
            'description' => 'Submit laporan ke management',
            'status' => 'pending',
            'scheduled_date' => $today->endOfMonth()->format('Y-m-d'),
            'scheduled_time' => '17:00:00',
            'timezone' => 'Asia/Jakarta',
            'is_recurring' => true,
        ]);

        TaskRecurrence::query()->create([
            'task_id' => $task4->id,
            'recurrence_type' => 'monthly',
            'interval_value' => 1,
            'day_of_month' => 28,
        ]);

        Task::factory()->count(5)->create(['user_id' => $user->id]);

        // Provisional user (not yet onboarded)
        User::factory()->create([
            'google_subject' => 'provisional-google-subject',
            'email' => 'provisional@zaid.app',
            'full_name' => 'Provisional User',
            'status' => UserStatus::Provisional->value,
        ]);
    }
}
