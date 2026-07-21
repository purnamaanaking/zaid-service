<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create(['email' => 'demo@zaid.app']);
        UserSetting::query()->create(['user_id' => $user->id, 'timezone' => 'Asia/Jakarta']);
    }
}
